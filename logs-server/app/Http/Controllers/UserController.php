<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users (clients) - Pure Relationship
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Eager load masterlist relationship
            $users = User::with('masterlist')->orderBy('created_at', 'desc')->get();

            // Transform data to include masterlist fields (Pure Relationship)
            $usersData = $users->map(function($user) {
                return [
                    'id' => $user->id, // This might be null since student_id is the primary key
                    'student_id' => $user->student_id, // Use this as the main identifier
                    'fname' => $user->masterlist->fname ?? '',
                    'mname' => $user->masterlist->mname ?? '',
                    'lname' => $user->masterlist->lname ?? '',
                    'email' => $user->masterlist->email ?? '',
                    'course' => $user->masterlist->course ?? '',
                    'year_level' => $user->masterlist->year_level ?? '',
                    'suffix' => $user->suffix,
                    'region' => $user->region,
                    'province' => $user->province,
                    'municipality' => $user->municipality,
                    'barangay' => $user->barangay,
                    'purok' => $user->purok,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $usersData,
                'total' => $usersData->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new user (client account) - Admin/Staff only - Pure Relationship
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|string',
                'suffix' => 'nullable|string',
                'region' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'municipality' => 'required|string|max:255',
                'barangay' => 'required|string|max:255',
                'purok' => 'nullable|string|max:255',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if student_id exists in masterlist (Pure Relationship)
            $masterlistEntry = \App\Models\Masterlist::where('student_id', $request->student_id)->first();
            
            if (!$masterlistEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID not found in masterlist. Please add to masterlist first.',
                ], 422);
            }

            // Check if student_id already exists in users table
            if (User::where('student_id', $request->student_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Student ID is already registered',
                ], 422);
            }

            // Check if student_id exists in staff table
            if (\App\Models\Staff::where('staff_id', $request->student_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID is registered as a staff ID',
                ], 422);
            }

            // Create user account (Pure Relationship - no duplicate data)
            $user = User::create([
                'student_id' => $request->student_id,
                'suffix' => $request->suffix,
                'region' => $request->region,
                'province' => $request->province,
                'municipality' => $request->municipality,
                'barangay' => $request->barangay,
                'purok' => $request->purok,
                'status' => 'Active',
                'password' => Hash::make($request->password),
            ]);

            // Load masterlist relationship
            $user->load('masterlist');

            // Log activity (created by admin or staff)
            $authUser = $request->user();
            if ($authUser) {
                try {
                    ActivityLog::create([
                        'user_type' => $authUser instanceof \App\Models\Admin ? 'admin' : 'staff',
                        'user_id' => $authUser instanceof \App\Models\Admin ? $authUser->admin_id : $authUser->staff_id,
                        'user_name' => trim($authUser->fname . ' ' . $authUser->lname),
                        'action' => 'created',
                        'module' => 'Users',
                        'description' => 'Created client account: ' . $user->masterlist->fname . ' ' . $user->masterlist->lname . ' (' . $user->student_id . ')',
                        'ip_address' => $request->ip(),
                    ]);
                } catch (\Exception $logError) {
                    \Log::error('Activity log failed during user creation: ' . $logError->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Client account created successfully!',
                'user' => [
                    'id' => $user->id,
                    'student_id' => $user->student_id,
                    'fname' => $user->masterlist->fname,
                    'mname' => $user->masterlist->mname,
                    'lname' => $user->masterlist->lname,
                    'email' => $user->masterlist->email,
                    'course' => $user->masterlist->course,
                    'year_level' => $user->masterlist->year_level,
                    'suffix' => $user->suffix,
                    'region' => $user->region,
                    'province' => $user->province,
                    'municipality' => $user->municipality,
                    'barangay' => $user->barangay,
                    'purok' => $user->purok,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single user - Pure Relationship (Using student_id)
     * 
     * @param string $id (student_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Find user by student_id (primary key)
            $user = User::with('masterlist')->where('student_id', $id)->firstOrFail();

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'student_id' => $user->student_id,
                    'fname' => $user->masterlist->fname ?? '',
                    'mname' => $user->masterlist->mname ?? '',
                    'lname' => $user->masterlist->lname ?? '',
                    'email' => $user->masterlist->email ?? '',
                    'course' => $user->masterlist->course ?? '',
                    'year_level' => $user->masterlist->year_level ?? '',
                    'suffix' => $user->suffix,
                    'region' => $user->region,
                    'province' => $user->province,
                    'municipality' => $user->municipality,
                    'barangay' => $user->barangay,
                    'purok' => $user->purok,
                    'status' => $user->status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Update a user
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id (student_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('student_id', $id)->firstOrFail();

            // Validate the request
            $validator = Validator::make($request->all(), [
                'student_id' => 'sometimes|required|string|unique:users,student_id,' . $user->student_id . ',student_id',
                'fname' => 'sometimes|required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'course' => 'sometimes|required|string',
                'year_level' => 'sometimes|required|string',
                'status' => 'sometimes|required|in:Active,Inactive',
                'password' => 'nullable|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update user data
            $updateData = $request->only([
                'student_id', 
                'fname', 
                'mname', 
                'lname', 
                'email', 
                'course', 
                'year_level', 
                'status'
            ]);

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a user
     * 
     * @param string $id (student_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = User::where('student_id', $id)->firstOrFail();
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }

    /**
     * Get users statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $total = User::count();
            $active = User::where('status', 'Active')->count();
            $inactive = User::where('status', 'Inactive')->count();

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    /**
     * Update user status (Active/Inactive)
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id (student_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = User::with('masterlist')->where('student_id', $id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:Active,Inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldStatus = $user->status;
            $user->status = $request->status;
            $user->save();

            // Log activity
            $authUser = $request->user();
            if ($authUser) {
                try {
                    ActivityLog::create([
                        'user_type' => $authUser instanceof \App\Models\Admin ? 'admin' : 'staff',
                        'user_id' => $authUser instanceof \App\Models\Admin ? $authUser->admin_id : $authUser->staff_id,
                        'user_name' => trim($authUser->fname . ' ' . $authUser->lname),
                        'action' => 'updated',
                        'module' => 'Users',
                        'description' => 'Changed status of ' . ($user->masterlist->fname ?? '') . ' ' . ($user->masterlist->lname ?? '') . ' (' . $user->student_id . ') from ' . $oldStatus . ' to ' . $request->status,
                        'ip_address' => $request->ip(),
                    ]);
                } catch (\Exception $logError) {
                    \Log::error('Activity log failed: ' . $logError->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully!',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction statistics for a user - Pure Relationship
     * 
     * @param string $id (student_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionStatistics($id)
    {
        try {
            $user = User::where('student_id', $id)->firstOrFail();
            
            // Use student_id for pure relationship with transactions table
            $total = \App\Models\Transaction::where('student_id', $user->student_id)->count();
            $pending = \App\Models\Transaction::where('student_id', $user->student_id)->where('status', 'pending')->count();
            $approved = \App\Models\Transaction::where('student_id', $user->student_id)->where('status', 'approved')->count();
            $completed = \App\Models\Transaction::where('student_id', $user->student_id)->where('status', 'completed')->count();
            $cancelled = \App\Models\Transaction::where('student_id', $user->student_id)->where('status', 'cancelled')->count();
            $rejected = \App\Models\Transaction::where('student_id', $user->student_id)->where('status', 'rejected')->count();

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total' => $total,
                    'pending' => $pending,
                    'approved' => $approved,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'rejected' => $rejected,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transaction statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all transactions for a user - Pure Relationship
     * 
     * @param string $id (student_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function userTransactions($id)
    {
        try {
            $user = User::where('student_id', $id)->firstOrFail();
            
            // Fetch transactions with purpose relationship
            $transactions = \App\Models\Transaction::with('purpose')
                ->where('student_id', $user->student_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'transactions' => $transactions,
                'total' => $transactions->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
