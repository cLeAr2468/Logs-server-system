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
     * Get all users (clients)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Eager load masterlist relationship
            $users = User::with('masterlist')->orderBy('created_at', 'desc')->get();

            // Transform data to include masterlist fields
            $usersData = $users->map(function($user) {
                return [
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
     * Create a new user (client account) - Admin/Staff only
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
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if student_id exists in masterlist
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

            // Create user account with masterlist relationship
            $user = User::create([
                'student_id' => $request->student_id,
                'masterlist_id' => $masterlistEntry->id,
                'suffix' => $request->suffix,
                'region' => $request->region,
                'province' => $request->province,
                'municipality' => $request->municipality,
                'barangay' => $request->barangay,
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
                    // Silently fail activity logging - don't break the user creation
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
     * Get a single user
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = User::with('masterlist')->findOrFail($id);

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
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'student_id' => 'sometimes|required|string|unique:users,student_id,' . $id,
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
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
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
}
