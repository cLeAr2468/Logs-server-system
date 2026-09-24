<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Get all feedback (Admin only)
     * Only show feedback for completed transactions
     */
    public function index(Request $request)
    {
        $query = Feedback::with([
            'user:id,fname,mname,lname,email,student_id',
            'transaction:id,purpose,schedule_date,time_slot,status'
        ])
            ->whereHas('transaction', function($q) {
                $q->where('status', 'completed');
            })
            ->whereNotNull('transact_id')
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
            'transaction_id' => 'required|exists:transactions,id',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $transactionId = $request->transaction_id;

        // Check if transaction belongs to user and is completed
        $transaction = \App\Models\Transaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found or not yet completed',
            ], 404);
        }

        // Check if feedback already exists for this transaction
        $existingFeedback = Feedback::where('user_id', $user->id)
            ->where('transact_id', $transactionId)
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'message' => 'You have already submitted feedback for this transaction',
            ], 422);
        }

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'transact_id' => $transactionId,
            'rating' => $request->rating,
            'message' => $request->message,
        ]);

        // Load relationships
        $feedback->load(['user:id,fname,mname,lname,email,student_id', 'transaction:id,purpose,schedule_date']);

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback
        ], 201);
    }

    /**
     * Get completed transactions without feedback for the authenticated user
     * SIMPLE VERSION - Same approach as getUserAppointments
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

            // Get completed transactions WITHOUT feedback
            $completedTransactions = \DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereNotExists(function($query) use ($user) {
                    $query->select(\DB::raw(1))
                          ->from('feedback')
                          ->whereColumn('feedback.transact_id', '=', 'transactions.id')
                          ->where('feedback.user_id', $user->id);
                })
                ->orderBy('schedule_date', 'desc')
                ->select('id', 'purpose', 'schedule_date', 'time_slot', 'created_at')
                ->get();

            return response()->json([
                'success' => true,
                'transactions' => $completedTransactions,
                'count' => count($completedTransactions)
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

        $feedback = Feedback::where('user_id', $user->id)
            ->where('transact_id', $transactionId)
            ->with('transaction:id,purpose,schedule_date')
            ->first();

        return response()->json([
            'has_feedback' => $feedback ? true : false,
            'feedback' => $feedback
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
