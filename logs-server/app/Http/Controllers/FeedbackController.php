<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Get all feedback (Admin only)
     */
    public function index(Request $request)
    {
        $query = Feedback::with('user:id,fname,mname,lname,email,student_id')
            ->orderBy('created_at', 'desc');

        // Filter by rating if provided
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $perPage = $request->get('per_page', 20);
        $feedback = $query->paginate($perPage);

        return response()->json($feedback);
    }

    /**
     * Get user's own feedback
     */
    public function getUserFeedback(Request $request)
    {
        $feedback = Feedback::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'feedback' => $feedback
        ]);
    }

    /**
     * Store new feedback for a specific transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_purpose' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:500',
        ]);

        $user = $request->user();

        // Normalize the date format to Y-m-d for consistent comparison
        $normalizedDate = \Carbon\Carbon::parse($request->transaction_date)->format('Y-m-d');

        // Log for debugging
        \Log::info('Feedback submission attempt', [
            'user_id' => $user->id,
            'purpose' => $request->transaction_purpose,
            'date_received' => $request->transaction_date,
            'date_normalized' => $normalizedDate,
        ]);

        // Verify that this transaction exists, belongs to user, and is completed
        $transaction = \App\Models\Transaction::where('user_id', $user->id)
            ->where('purpose', $request->transaction_purpose)
            ->whereDate('schedule_date', $normalizedDate)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            // Log available transactions for debugging
            $availableTransactions = \App\Models\Transaction::where('user_id', $user->id)
                ->where('status', 'completed')
                ->select('id', 'purpose', 'schedule_date', 'status')
                ->get();
            
            \Log::warning('Transaction not found for feedback', [
                'searched_purpose' => $request->transaction_purpose,
                'searched_date' => $normalizedDate,
                'available_completed_transactions' => $availableTransactions->toArray()
            ]);

            return response()->json([
                'message' => 'Transaction not found or not yet completed',
                'debug' => [
                    'searched_purpose' => $request->transaction_purpose,
                    'searched_date' => $normalizedDate,
                ]
            ], 404);
        }

        // Check if feedback already exists for THIS SPECIFIC transaction
        $existingFeedback = Feedback::where('user_id', $user->id)
            ->where('transaction_purpose', $request->transaction_purpose)
            ->where('transaction_date', $normalizedDate)
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'message' => 'You have already submitted feedback for this transaction',
            ], 422);
        }

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'transaction_purpose' => $request->transaction_purpose,
            'transaction_date' => $normalizedDate,
            'rating' => $request->rating,
            'message' => $request->message,
        ]);

        // Load user relationship
        $feedback->load('user:id,fname,mname,lname,email,student_id');

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback
        ], 201);
    }

    /**
     * Get completed transactions without feedback for the authenticated user
     */
    public function getCompletedTransactionsWithoutFeedback(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'transactions' => [],
                ], 401);
            }

            // Get all completed transactions
            $completedTransactions = \DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->orderBy('schedule_date', 'desc')
                ->select('id', 'purpose', 'schedule_date', 'time_slot', 'created_at')
                ->get();

            // Get all feedback for this user
            $userFeedback = \DB::table('feedback')
                ->where('user_id', $user->id)
                ->select('transaction_purpose', 'transaction_date')
                ->get();

            // Filter out transactions that already have feedback
            $transactionsWithoutFeedback = $completedTransactions->filter(function($transaction) use ($userFeedback) {
                // Normalize transaction date
                $transactionDate = \Carbon\Carbon::parse($transaction->schedule_date)->format('Y-m-d');
                
                // Check if there's feedback for this specific transaction
                $hasFeedback = $userFeedback->contains(function($feedback) use ($transaction, $transactionDate) {
                    $feedbackDate = \Carbon\Carbon::parse($feedback->transaction_date)->format('Y-m-d');
                    return $feedback->transaction_purpose === $transaction->purpose 
                        && $feedbackDate === $transactionDate;
                });
                
                return !$hasFeedback;
            });

            return response()->json([
                'success' => true,
                'transactions' => $transactionsWithoutFeedback->values(),
                'count' => $transactionsWithoutFeedback->count()
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Feedback API Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'transactions' => [],
            ], 500);
        }
    }

    /**
     * Check if transaction has feedback and return the feedback
     */
    public function checkTransactionFeedback(Request $request, $transactionId)
    {
        $user = $request->user();

        // Get the transaction
        $transaction = \App\Models\Transaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'has_feedback' => false,
                'feedback' => null
            ]);
        }

        // Normalize the date for consistent comparison
        $normalizedDate = \Carbon\Carbon::parse($transaction->schedule_date)->format('Y-m-d');

        // Check if there's feedback for THIS SPECIFIC transaction
        $feedback = Feedback::where('user_id', $user->id)
            ->where('transaction_purpose', $transaction->purpose)
            ->where('transaction_date', $normalizedDate)
            ->with('user:id,fname,mname,lname,email,student_id')
            ->first();

        return response()->json([
            'has_feedback' => $feedback ? true : false,
            'feedback' => $feedback // transaction_data is automatically appended
        ]);
    }

    /**
     * Get single feedback
     */
    public function show($id)
    {
        $feedback = Feedback::with([
            'user:id,fname,mname,lname,email,student_id',
            'transaction:id,purpose,schedule_date,time_slot,status'
        ])
            ->findOrFail($id);

        return response()->json([
            'feedback' => $feedback
        ]);
    }

    /**
     * Delete feedback (Admin only)
     */
    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return response()->json([
            'message' => 'Feedback deleted successfully'
        ]);
    }

    /**
     * Get feedback statistics (Admin only)
     */
    public function statistics()
    {
        $stats = [
            'total' => Feedback::count(),
            'by_rating' => [
                '5' => Feedback::where('rating', 5)->count(),
                '4' => Feedback::where('rating', 4)->count(),
                '3' => Feedback::where('rating', 3)->count(),
                '2' => Feedback::where('rating', 2)->count(),
                '1' => Feedback::where('rating', 1)->count(),
            ],
            'average_rating' => round(Feedback::avg('rating'), 2),
        ];

        return response()->json($stats);
    }
}
