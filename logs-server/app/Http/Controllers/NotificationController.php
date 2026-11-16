<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get notifications for current user (client or admin/staff) with pagination
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Get pagination parameters
            $perPage = $request->input('per_page', 15); // Default 15 per page
            $page = $request->input('page', 1);

            // Determine user type
            $isAdmin = $user instanceof \App\Models\Admin || $user instanceof \App\Models\Staff;
            
            if ($isAdmin) {
                // Get the specific admin/staff's notifications only
                $recipientType = $user instanceof \App\Models\Admin ? 'admin' : 'staff';
                $recipientId = $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id;
                
                $query = Notification::where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId)
                    ->with('transaction.user.masterlist', 'transaction.purpose')
                    ->orderBy('created_at', 'desc');
                
                // Get total unread count (not paginated)
                $unreadCount = Notification::where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId)
                    ->where('is_read', false)
                    ->count();
                
                // Paginate
                $notifications = $query->paginate($perPage, ['*'], 'page', $page);
            } else {
                // Client gets their own notifications
                $query = Notification::forClient($user->student_id)
                    ->with('transaction.purpose')
                    ->orderBy('created_at', 'desc');
                
                // Get total unread count (not paginated)
                $unreadCount = Notification::forClient($user->student_id)
                    ->where('is_read', false)
                    ->count();
                
                // Paginate
                $notifications = $query->paginate($perPage, ['*'], 'page', $page);
            }

            return response()->json([
                'notifications' => $notifications->items(),
                'unread_count' => $unreadCount,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Fetch notifications error:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch notifications'], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->is_read = true;
            $notification->save();

            return response()->json([
                'message' => 'Notification marked as read',
                'notification' => $notification,
            ]);

        } catch (\Exception $e) {
            Log::error('Mark notification as read error:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to mark notification'], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            
            $isAdmin = $user instanceof \App\Models\Admin || $user instanceof \App\Models\Staff;
            
            if ($isAdmin) {
                // Mark only THIS admin/staff's notifications as read
                $recipientType = $user instanceof \App\Models\Admin ? 'admin' : 'staff';
                $recipientId = $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id;
                
                Notification::where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            } else {
                Notification::forClient($user->student_id)->unread()->update(['is_read' => true]);
            }

            return response()->json(['message' => 'All notifications marked as read']);

        } catch (\Exception $e) {
            Log::error('Mark all as read error:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to mark all notifications'], 500);
        }
    }

    /**
     * Create notification (helper method for internal use)
     * Creates individual notification for each admin/staff member
     */
    public static function createNotification($transactionId, $type, $recipientType, $recipientId, $reason = null)
    {
        try {
            $transaction = Transaction::with('user.masterlist', 'purpose')->find($transactionId);
            
            if (!$transaction) {
                Log::error('Transaction not found for notification', ['transaction_id' => $transactionId]);
                return;
            }

            $title = '';
            $message = '';

            switch ($type) {
                case 'new_request':
                    $studentName = $transaction->user->masterlist 
                        ? "{$transaction->user->masterlist->fname} {$transaction->user->masterlist->lname}"
                        : 'Student';
                    $title = 'New Appointment Request';
                    $message = "{$studentName} submitted a new online appointment request for {$transaction->purpose->name}.";
                    break;

                case 'approved':
                    $title = 'Appointment Approved';
                    $message = "Your appointment request for {$transaction->purpose->name} has been approved. Scheduled on " . date('M d, Y', strtotime($transaction->schedule_date)) . " at {$transaction->time_slot}.";
                    break;

                case 'rejected':
                    $title = 'Appointment Rejected';
                    $message = "Your appointment request for {$transaction->purpose->name} has been rejected.";
                    if ($reason) {
                        $message .= " Reason: {$reason}";
                    }
                    break;

                case 'completed':
                    $title = 'Appointment Completed';
                    $message = "Your appointment for {$transaction->purpose->name} has been completed. You can now provide feedback.";
                    break;

                case 'cancelled':
                    if ($recipientType === 'admin') {
                        $studentName = $transaction->user->masterlist 
                            ? "{$transaction->user->masterlist->fname} {$transaction->user->masterlist->lname}"
                            : 'Student';
                        $title = 'Appointment Cancelled by Student';
                        $message = "{$studentName} cancelled their appointment for {$transaction->purpose->name}.";
                        if ($reason) {
                            $message .= " Reason: {$reason}";
                        }
                    } else {
                        $title = 'Appointment Cancelled';
                        $message = "Your appointment for {$transaction->purpose->name} has been cancelled.";
                        if ($reason) {
                            $message .= " Reason: {$reason}";
                        }
                    }
                    break;
            }

            // If recipient is 'admin', create notification for ALL admin and staff accounts
            if ($recipientType === 'admin' && $recipientId === 'all') {
                // Get all admins
                $admins = \App\Models\Admin::all();
                foreach ($admins as $admin) {
                    Notification::create([
                        'recipient_type' => 'admin',
                        'recipient_id' => $admin->admin_id,
                        'transaction_id' => $transactionId,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'reason' => $reason,
                        'is_read' => false,
                    ]);
                }
                
                // Get all staff
                $staff = \App\Models\Staff::where('status', 'Active')->get();
                foreach ($staff as $staffMember) {
                    Notification::create([
                        'recipient_type' => 'staff',
                        'recipient_id' => $staffMember->staff_id,
                        'transaction_id' => $transactionId,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'reason' => $reason,
                        'is_read' => false,
                    ]);
                }
                
                Log::info('Notifications created for all admins and staff', [
                    'type' => $type,
                    'transaction_id' => $transactionId,
                    'admin_count' => $admins->count(),
                    'staff_count' => $staff->count()
                ]);
            } else {
                // Single recipient (client)
                Notification::create([
                    'recipient_type' => $recipientType,
                    'recipient_id' => $recipientId,
                    'transaction_id' => $transactionId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'reason' => $reason,
                    'is_read' => false,
                ]);

                Log::info('Notification created', [
                    'type' => $type,
                    'recipient_type' => $recipientType,
                    'transaction_id' => $transactionId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Create notification error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
