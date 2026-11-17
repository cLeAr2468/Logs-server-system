<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                    'error' => 'User not found'
                ], 401);
            }

            $studentId = $user->student_id;

            if (!$studentId) {
                return response()->json([
                    'message' => 'Student ID not found',
                    'error' => 'Invalid user data'
                ], 400);
            }

            // Get appointment statistics
            $totalAppointments = Transaction::where('student_id', $studentId)->count();
            $completedAppointments = Transaction::where('student_id', $studentId)
                ->where('status', 'completed')
                ->count();
            $approvedAppointments = Transaction::where('student_id', $studentId)
                ->where('status', 'approved')
                ->count();
            $pendingAppointments = Transaction::where('student_id', $studentId)
                ->where('status', 'pending')
                ->count();

            return response()->json([
                'statistics' => [
                    'total_appointments' => $totalAppointments,
                    'completed_appointments' => $completedAppointments,
                    'approved_appointments' => $approvedAppointments,
                    'pending_appointments' => $pendingAppointments,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard statistics error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                    'error' => 'User not found'
                ], 401);
            }

            $studentId = $user->student_id;

            if (!$studentId) {
                return response()->json([
                    'message' => 'Student ID not found',
                    'error' => 'Invalid user data'
                ], 400);
            }

            $activities = [];

            // Get recent appointments (last 7)
            $recentAppointments = Transaction::where('student_id', $studentId)
                ->with('purpose')
                ->orderBy('created_at', 'desc')
                ->limit(7)
                ->get();

            foreach ($recentAppointments as $appointment) {
                $activities[] = [
                    'type' => 'appointment',
                    'action' => $this->getAppointmentAction($appointment->status),
                    'description' => $this->getAppointmentDescription($appointment),
                    'timestamp' => $appointment->created_at,
                    'status' => $appointment->status,
                    'icon' => $this->getActivityIcon('appointment', $appointment->status),
                ];
            }

            // Get recent feedback (last 3) using the relationship
            $recentFeedback = $user->feedback()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentFeedback as $feedback) {
                $activities[] = [
                    'type' => 'feedback',
                    'action' => 'Feedback Submitted',
                    'description' => "You submitted a {$feedback->rating}-star feedback",
                    'timestamp' => $feedback->created_at,
                    'rating' => $feedback->rating,
                    'icon' => 'MessageSquare',
                ];
            }

            // Sort by timestamp descending
            usort($activities, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            // Limit to 10 most recent activities
            $activities = array_slice($activities, 0, 10);

            return response()->json([
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard recent activity error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch recent activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointment action text based on status
     */
    private function getAppointmentAction($status)
    {
        return match ($status) {
            'pending' => 'Appointment Requested',
            'approved' => 'Appointment Approved',
            'completed' => 'Appointment Completed',
            'cancelled' => 'Appointment Cancelled',
            'rejected' => 'Appointment Rejected',
            default => 'Appointment Updated',
        };
    }

    /**
     * Get appointment description
     */
    private function getAppointmentDescription($appointment)
    {
        $purpose = $appointment->purpose ? $appointment->purpose->name : 'Unknown';
        $date = $appointment->appointment_date;
        $time = $appointment->appointment_time;

        return "{$purpose} appointment on {$date} at {$time}";
    }

    /**
     * Get activity icon based on type and status
     */
    private function getActivityIcon($type, $status = null)
    {
        if ($type === 'appointment') {
            return match ($status) {
                'pending' => 'Clock',
                'approved' => 'RefreshCw',
                'completed' => 'CheckCircle',
                'cancelled' => 'XCircle',
                'rejected' => 'XCircle',
                default => 'Calendar',
            };
        }

        return 'Activity';
    }

    /**
     * Get admin dashboard statistics
     */
    public function getAdminStatistics(Request $request)
    {
        // Get date range from request, default to current month
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // If no date range provided, default to current month
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01'); // First day of current month
            $endDate = date('Y-m-t'); // Last day of current month
        }

        // Total transactions for the selected date range
        $totalTransactions = Transaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Monthly target (you can make this configurable)
        $monthlyTarget = 6500;
        $targetPercentage = $monthlyTarget > 0 ? round(($totalTransactions / $monthlyTarget) * 100) : 0;

        // Pending requests for the selected date range
        $pendingRequests = Transaction::where('status', 'pending')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();
        $pendingTrend = '+3.1%'; // You can calculate this by comparing with previous period

        // Completed services
        $completedServices = Transaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->count();
        $completionRate = $totalTransactions > 0 ? round(($completedServices / $totalTransactions) * 100) : 0;

        // Feedback score
        $avgRating = Feedback::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->avg('rating') ?? 0;
        $feedbackCount = Feedback::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();
        
        // Transaction counts by purpose (service) with percentages
        $transactionsByPurpose = Transaction::join('purposes', 'transactions.purpose_id', '=', 'purposes.id')
            ->whereBetween('transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('purposes.name as purpose_name', 'purposes.id as purpose_id', DB::raw('count(*) as count'))
            ->groupBy('purposes.id', 'purposes.name')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) use ($totalTransactions) {
                $item->percentage = $totalTransactions > 0 ? round(($item->count / $totalTransactions) * 100, 2) : 0;
                return $item;
            });
        
        // Transaction counts by status
        $transactionsByStatus = Transaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) use ($totalTransactions) {
                $item->percentage = $totalTransactions > 0 ? round(($item->count / $totalTransactions) * 100, 2) : 0;
                return $item;
            });
        
        // Transaction counts by request type (walk-in vs online)
        $transactionsByType = Transaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('request_type', DB::raw('count(*) as count'))
            ->groupBy('request_type')
            ->get()
            ->map(function ($item) use ($totalTransactions) {
                $item->percentage = $totalTransactions > 0 ? round(($item->count / $totalTransactions) * 100, 2) : 0;
                return $item;
            });

        return response()->json([
            'statistics' => [
                'total_transactions' => $totalTransactions,
                'target_percentage' => $targetPercentage,
                'monthly_target' => $monthlyTarget,
                'pending_requests' => $pendingRequests,
                'pending_trend' => $pendingTrend,
                'completed_services' => $completedServices,
                'completion_rate' => $completionRate,
                'feedback_score' => round($avgRating, 1),
                'feedback_count' => $feedbackCount,
                'transactions_by_purpose' => $transactionsByPurpose,
                'transactions_by_status' => $transactionsByStatus,
                'transactions_by_type' => $transactionsByType,
            ]
        ]);
    }

    /**
     * Get recent transactions for admin dashboard
     */
    public function getRecentTransactions(Request $request)
    {
        $limit = $request->input('limit', 10);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Transaction::with(['user.masterlist', 'purpose'])
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $transactions = $query->limit($limit)
            ->get()
            ->map(function ($transaction) {
                $user = $transaction->user;
                $masterlist = $user ? $user->masterlist : null;
                
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('M d, Y'),
                    'student' => $masterlist ? ($masterlist->fname . ' ' . $masterlist->lname) : 'N/A',
                    'purpose' => $transaction->purpose ? $transaction->purpose->name : 'N/A',
                    'address' => $transaction->brgy . ', ' . $transaction->municipality,
                    'course' => $masterlist ? $masterlist->course : 'N/A',
                    'status' => ucfirst($transaction->status),
                ];
            });

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    /**
     * Get performance summary (transactions by purpose)
     * Shows ALL purposes that have transactions
     */
    public function getPerformanceSummary(Request $request)
    {
        // Get date range from request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Transaction::join('purposes', 'transactions.purpose_id', '=', 'purposes.id')
            ->select('purposes.name as label', DB::raw('count(*) as value'))
            ->groupBy('purposes.id', 'purposes.name')
            ->orderBy('value', 'desc');
            // Removed limit - shows ALL purposes with transactions

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $performanceData = $query->get();

        return response()->json([
            'performance' => $performanceData
        ]);
    }

    /**
     * Get all recent transactions with pagination for recent-transact page
     */
    public function getAllRecentTransactions(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $query = Transaction::with(['user.masterlist', 'purpose'])
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('brgy', 'LIKE', "%{$search}%")
                  ->orWhere('municipality', 'LIKE', "%{$search}%")
                  ->orWhereHas('purpose', function ($purposeQuery) use ($search) {
                      $purposeQuery->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('user.masterlist', function ($masterlistQuery) use ($search) {
                      $masterlistQuery->where('fname', 'LIKE', "%{$search}%")
                                     ->orWhere('lname', 'LIKE', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => $transactions->map(function ($transaction) {
                $user = $transaction->user;
                $masterlist = $user ? $user->masterlist : null;
                
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('M d, Y'),
                    'student' => $masterlist ? ($masterlist->fname . ' ' . $masterlist->lname) : 'N/A',
                    'purpose' => $transaction->purpose ? $transaction->purpose->name : 'N/A',
                    'address' => $transaction->brgy . ', ' . $transaction->municipality,
                    'course' => $masterlist ? $masterlist->course : 'N/A',
                    'status' => ucfirst($transaction->status),
                ];
            }),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
        ]);
    }
}
