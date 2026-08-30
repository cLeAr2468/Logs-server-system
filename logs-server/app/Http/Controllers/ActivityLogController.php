<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get activity logs for the authenticated admin/staff
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Determine user type and ID
            $userType = $user instanceof \App\Models\Admin ? 'admin' : 'staff';
            $userId = $user->id;

            // Get pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Get activity logs for this specific user
            $logs = ActivityLog::where('user_type', $userType)
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'logs' => $logs->items(),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent activity logs (for dashboard)
     */
    public function recent(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Determine user type and ID
            $userType = $user instanceof \App\Models\Admin ? 'admin' : 'staff';
            $userId = $user->id;

            $limit = $request->input('limit', 10);

            // Get recent activity logs for this specific user
            $logs = ActivityLog::where('user_type', $userType)
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent activity logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all activity logs for the authenticated user
     */
    public function clear(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Determine user type and ID
            $userType = $user instanceof \App\Models\Admin ? 'admin' : 'staff';
            $userId = $user->id;

            // Delete all logs for this user
            ActivityLog::where('user_type', $userType)
                ->where('user_id', $userId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Activity logs cleared successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear activity logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
