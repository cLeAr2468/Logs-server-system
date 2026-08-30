<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    /**
     * Get all staff members
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $staff = Staff::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'staff' => $staff,
                'total' => $staff->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staff members',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single staff member
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $staff = Staff::findOrFail($id);

            return response()->json([
                'success' => true,
                'staff' => $staff,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }
    }

    /**
     * Register a new staff member
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'staff_id' => 'required|string',
                'fname' => 'required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if staff_id already exists in staff table
            if (Staff::where('staff_id', $request->staff_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Staff ID already exists',
                ], 422);
            }

            // Check if staff_id exists in users/masterlist table (as student_id)
            if (\App\Models\User::where('student_id', $request->staff_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID is already used as a Student ID',
                ], 422);
            }

            if (\App\Models\Masterlist::where('student_id', $request->staff_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID is already used as a Student ID in masterlist',
                ], 422);
            }

            // Check if email already exists in staff table
            if (Staff::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered as a staff account',
                ], 422);
            }

            // Check if email exists in users table
            if (\App\Models\User::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered as a client account',
                ], 422);
            }

            // Check if email exists in masterlist
            if (\App\Models\Masterlist::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email already exists in masterlist',
                ], 422);
            }

            // Use DB insert to bypass auto-hashing, then manually hash
            $staffId = \DB::table('staff')->insertGetId([
                'staff_id' => $request->staff_id,
                'fname' => $request->fname,
                'mname' => $request->mname,
                'lname' => $request->lname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Fetch the created staff
            $staff = Staff::find($staffId);

            // Log activity
            $user = $request->user();
            if ($user) {
                ActivityLog::create([
                    'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                    'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                    'user_name' => trim($user->fname . ' ' . $user->lname),
                    'action' => 'created',
                    'module' => 'Staff',
                    'description' => 'Registered new staff: ' . $staff->fname . ' ' . $staff->lname . ' (' . $staff->staff_id . ')',
                    'ip_address' => $request->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Staff member registered successfully!',
                'staff' => $staff,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register staff member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a staff member
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $staff = Staff::findOrFail($id);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'staff_id' => 'sometimes|required|string|unique:staff,staff_id,' . $id,
                'fname' => 'sometimes|required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:staff,email,' . $id,
                'password' => 'nullable|string|min:6',
                'status' => 'sometimes|required|in:Active,Inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Prepare update data
            $updateData = [];
            
            if ($request->has('staff_id')) $updateData['staff_id'] = $request->staff_id;
            if ($request->has('fname')) $updateData['fname'] = $request->fname;
            if ($request->has('mname')) $updateData['mname'] = $request->mname;
            if ($request->has('lname')) $updateData['lname'] = $request->lname;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('status')) $updateData['status'] = $request->status;
            
            // Handle password separately to avoid double hashing
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            
            // Update using DB to bypass auto-hashing
            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                \DB::table('staff')->where('id', $id)->update($updateData);
                $staff->refresh();
            }

            // Log activity
            $user = $request->user();
            if ($user) {
                ActivityLog::create([
                    'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                    'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                    'user_name' => trim($user->fname . ' ' . $user->lname),
                    'action' => 'updated',
                    'module' => 'Staff',
                    'description' => 'Updated staff: ' . $staff->fname . ' ' . $staff->lname . ' (' . $staff->staff_id . ')',
                    'ip_address' => $request->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully!',
                'staff' => $staff,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a staff member
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $staff = Staff::findOrFail($id);
            $staffName = $staff->fname . ' ' . $staff->lname;
            $staffId = $staff->staff_id;
            
            $staff->delete();

            // Log activity
            $user = $request->user();
            if ($user) {
                ActivityLog::create([
                    'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                    'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                    'user_name' => trim($user->fname . ' ' . $user->lname),
                    'action' => 'deleted',
                    'module' => 'Staff',
                    'description' => 'Deleted staff: ' . $staffName . ' (' . $staffId . ')',
                    'ip_address' => $request->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff member',
            ], 500);
        }
    }

    /**
     * Staff login
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Find staff by email
            $staff = Staff::where('email', $request->email)->first();

            // Check if staff exists and password is correct
            if (!$staff || !Hash::check($request->password, $staff->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Create token for staff
            $token = $staff->createToken('staff-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'staff' => [
                    'id' => $staff->id,
                    'staff_id' => $staff->staff_id,
                    'fname' => $staff->fname,
                    'mname' => $staff->mname,
                    'lname' => $staff->lname,
                    'email' => $staff->email,
                    'full_name' => $staff->full_name,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Staff logout
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
            ], 500);
        }
    }
}
