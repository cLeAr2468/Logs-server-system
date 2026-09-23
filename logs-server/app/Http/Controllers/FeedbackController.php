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

        $feedback = $query->paginate(20);

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
            ->where('transaction_id', $transactionId)
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'message' => 'You have already submitted feedback for this transaction',
            ], 422);
        }

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'transaction_id' => $transactionId,
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
     */
    public function getCompletedTransactionsWithoutFeedback(Request $request)
    {
        $user = $request->user();

        $completedTransactions = \App\Models\Transaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('feedback')
            ->orderBy('schedule_date', 'desc')
            ->get(['id', 'purpose', 'schedule_date', 'time_slot', 'created_at']);

        return response()->json([
            'transactions' => $completedTransactions,
            'count' => $completedTransactions->count()
        ]);
    }

    /**
     * Check if transaction has feedback
     */
    public function checkTransactionFeedback(Request $request, $transactionId)
    {
        $user = $request->user();

        $hasFeedback = Feedback::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->exists();

        return response()->json([
            'has_feedback' => $hasFeedback
        ]);
    }

    /**
     * Get single feedback
     */
    public function show($id)
    {
        $feedback = Feedback::with('user:id,fname,mname,lname,email,student_id')
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
