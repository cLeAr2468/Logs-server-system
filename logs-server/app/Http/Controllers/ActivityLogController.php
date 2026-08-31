<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Create a new activity log entry
     * Can be called by authenticated users (client, staff, admin)
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Validate input
            $request->validate([
                'action' => 'required|string|max:255',
                'module' => 'required|string|max:255',
                'description' => 'required|string',
                'metadata' => 'nullable|array',
            ]);

            // Determine user type and ID
            if ($user instanceof \App\Models\Admin) {
                $userType = 'admin';
                $userId = $user->admin_id;
                $userName = $user->full_name;
            } elseif ($user instanceof \App\Models\Staff) {
                $userType = 'staff';
                $userId = $user->staff_id;
                $userName = trim("{$user->fname} {$user->mname} {$user->lname}");
            } else {
                // Regular user (client)
                $userType = 'client';
                $userId = $user->student_id;
                $userName = trim("{$user->fname} {$user->mname} {$user->lname}");
            }

            // Create activity log
            $log = ActivityLog::create([
                'user_type' => $userType,
                'user_id' => $userId,
                'user_name' => $userName,
                'action' => $request->action,
                'module' => $request->module,
                'description' => $request->description,
                'metadata' => $request->metadata,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity logged successfully',
                'log' => $log,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create activity log', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to log activity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity logs for the authenticated admin/staff
     * Admin sees ALL logs, Staff sees only their own
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

            // Determine user type
            $isAdmin = $user instanceof \App\Models\Admin;
            $userType = $isAdmin ? 'admin' : 'staff';
            $userId = $isAdmin ? $user->admin_id : $user->staff_id;

            // Get pagination and filter parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $filterType = $request->input('filter_type'); // 'admin', 'staff', 'client', or null for all

            // Build query
            $query = ActivityLog::query();

            if ($isAdmin) {
                // Admin sees all logs, optionally filtered by user_type
                if ($filterType && in_array($filterType, ['admin', 'staff', 'client'])) {
                    $query->where('user_type', $filterType);
                }
            } else {
                // Staff sees only their own logs
                $query->where('user_type', $userType)
                      ->where('user_id', $userId);
            }

            $logs = $query->orderBy('created_at', 'desc')
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
     * Admin sees ALL logs, Staff sees only their own
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

            // Determine user type
            $isAdmin = $user instanceof \App\Models\Admin;
            $userType = $isAdmin ? 'admin' : 'staff';
            $userId = $isAdmin ? $user->admin_id : $user->staff_id;

            $limit = $request->input('limit', 10);

            // Build query
            $query = ActivityLog::query();

            if ($isAdmin) {
                // Admin sees all logs (no filter)
            } else {
                // Staff sees only their own logs
                $query->where('user_type', $userType)
                      ->where('user_id', $userId);
            }

            $logs = $query->orderBy('created_at', 'desc')
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

            // Determine user type
            $isAdmin = $user instanceof \App\Models\Admin;
            $userType = $isAdmin ? 'admin' : 'staff';
            $userId = $isAdmin ? $user->admin_id : $user->staff_id;

            if ($isAdmin) {
                // Admin can clear all logs
                ActivityLog::truncate();
            } else {
                // Staff can only clear their own logs
                ActivityLog::where('user_type', $userType)
                    ->where('user_id', $userId)
                    ->delete();
            }

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

    /**
     * Get activity log statistics
     * Returns total counts by user type (admin only)
     */
    public function statistics(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $isAdmin = $user instanceof \App\Models\Admin;

            if (!$isAdmin) {
                // Staff only sees their own count
                $userType = 'staff';
                $userId = $user->staff_id;
                
                $staffCount = ActivityLog::where('user_type', $userType)
                    ->where('user_id', $userId)
                    ->count();

                return response()->json([
                    'success' => true,
                    'statistics' => [
                        'total' => $staffCount,
                        'admin_count' => 0,
                        'staff_count' => $staffCount,
                        'client_count' => 0,
                    ],
                ], 200);
            }

            // Admin sees all statistics
            $adminCount = ActivityLog::where('user_type', 'admin')->count();
            $staffCount = ActivityLog::where('user_type', 'staff')->count();
            $clientCount = ActivityLog::where('user_type', 'client')->count();
            $total = $adminCount + $staffCount + $clientCount;

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total' => $total,
                    'admin_count' => $adminCount,
                    'staff_count' => $staffCount,
                    'client_count' => $clientCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity log statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
