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
        $userId = $request->user()->id;

        // Get appointment statistics
        $totalAppointments = Transaction::where('user_id', $userId)->count();
        $completedAppointments = Transaction::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();
        $approvedAppointments = Transaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->count();
        $pendingAppointments = Transaction::where('user_id', $userId)
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
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(Request $request)
    {
        $userId = $request->user()->id;
        $activities = [];

        // Get recent appointments (last 7)
        $recentAppointments = Transaction::where('user_id', $userId)
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

        // Get recent feedback (last 3)
        $recentFeedback = Feedback::where('user_id', $userId)
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
        $purpose = ucfirst($appointment->purpose);
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

        $query = Transaction::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $transactions = $query->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('M d, Y'),
                    'student' => $transaction->user ? $transaction->user->fname . ' ' . $transaction->user->lname : 'N/A',
                    'purpose' => $transaction->purpose,
                    'address' => $transaction->brgy . ', ' . $transaction->municipality,
                    'course' => $transaction->user->course ?? 'N/A',
                    'status' => ucfirst($transaction->status),
                ];
            });

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    /**
     * Get performance summary (transactions by purpose)
     */
    public function getPerformanceSummary(Request $request)
    {
        // Get date range from request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Transaction::select('purpose', DB::raw('count(*) as value'))
            ->groupBy('purpose')
            ->orderBy('value', 'desc')
            ->limit(5);

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $performanceData = $query->get()
            ->map(function ($item) {
                return [
                    'label' => $item->purpose,
                    'value' => $item->value,
                ];
            });

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

        $query = Transaction::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('brgy', 'LIKE', "%{$search}%")
                  ->orWhere('municipality', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('fname', 'LIKE', "%{$search}%")
                                ->orWhere('lname', 'LIKE', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('M d, Y'),
                    'student' => $transaction->user ? $transaction->user->fname . ' ' . $transaction->user->lname : 'N/A',
                    'purpose' => $transaction->purpose,
                    'address' => $transaction->brgy . ', ' . $transaction->municipality,
                    'course' => $transaction->user->course ?? 'N/A',
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
