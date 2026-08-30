<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Verify admin/staff token
     */
    public function verify(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'authenticated' => false,
                    'message' => 'Invalid or expired token',
                ], 401);
            }

            // Check if user is Admin or Staff
            $role = $user instanceof Admin ? 'admin' : 'staff';

            return response()->json([
                'success' => true,
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $role,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => 'Token verification failed',
            ], 401);
        }
    }

    /**
     * Admin/Staff Login
     * Supports:
     * 1. Admin credentials from admins table
     * 2. Staff credentials from staff table
     */
    public function login(Request $request)
    {
        try {
            // Validate request
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

            $email = trim($request->email);
            $password = $request->password;

            // Check 1: Admin credentials from database
            $admin = Admin::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if ($admin) {
                // Check if password matches
                if (Hash::check($password, $admin->password)) {
                    // Generate token for admin
                    $token = $admin->createToken('admin-token')->plainTextToken;

                    return response()->json([
                        'success' => true,
                        'message' => 'Admin login successful',
                        'token' => $token,
                        'user' => [
                            'id' => $admin->id,
                            'email' => $admin->email,
                            'role' => 'admin',
                            'fname' => $admin->fname,
                            'mname' => $admin->mname,
                            'lname' => $admin->lname,
                            'full_name' => $admin->full_name,
                            'status' => $admin->status,
                        ],
                    ], 200);
                }
                
                // Admin found but password doesn't match
                \Log::warning('Admin login failed - invalid password', [
                    'email' => $email,
                    'admin_id' => $admin->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password',
                ], 401);
            }

            // Check 2: Staff credentials from database
            $staff = Staff::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if ($staff) {
                // Check if password matches
                if (Hash::check($password, $staff->password)) {
                    // Generate token for staff
                    $token = $staff->createToken('admin-staff-token')->plainTextToken;

                    return response()->json([
                        'success' => true,
                        'message' => 'Staff login successful',
                        'token' => $token,
                        'user' => [
                            'id' => $staff->id,
                            'staff_id' => $staff->staff_id,
                            'email' => $staff->email,
                            'role' => 'staff',
                            'fname' => $staff->fname,
                            'mname' => $staff->mname,
                            'lname' => $staff->lname,
                            'full_name' => trim("{$staff->fname} {$staff->mname} {$staff->lname}"),
                        ],
                    ], 200);
                }
                
                // Staff found but password doesn't match
                \Log::warning('Staff login failed - invalid password', [
                    'email' => $email,
                    'staff_id' => $staff->id
                ]);
            } else {
                // Neither admin nor staff found
                \Log::warning('Login failed - email not found', [
                    'email' => $email
                ]);
            }

            // Invalid credentials
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);

        } catch (\Exception $e) {
            \Log::error('Login exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin/Staff Logout
     */
    public function logout(Request $request)
    {
        try {
            // For default admin (token is just encoded string), no token to revoke
            // For staff, revoke the token
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }

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

    /**
     * Get current admin/staff profile
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check if user is Admin
            if ($user instanceof Admin) {
                return response()->json([
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => 'admin',
                        'firstname' => $user->fname,
                        'middlename' => $user->mname,
                        'lastname' => $user->lname,
                        'full_name' => $user->full_name,
                        'status' => $user->status,
                    ],
                ], 200);
            }

            // Otherwise, it's staff
            return response()->json([
                'success' => true,
                'staff' => [
                    'id' => $user->id,
                    'staff_id' => $user->staff_id,
                    'email' => $user->email,
                    'role' => 'staff',
                    'firstname' => $user->fname,
                    'middlename' => $user->mname,
                    'lastname' => $user->lname,
                    'full_name' => trim("{$user->fname} {$user->mname} {$user->lname}"),
                    'status' => $user->status ?? 'Active',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Get profile exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get profile',
            ], 500);
        }
    }

    /**
     * Update staff profile
     */
    public function updateProfile(Request $request)
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
            $validator = Validator::make($request->all(), [
                'firstname' => 'sometimes|required',
                'middlename' => 'nullable',
                'lastname' => 'sometimes|required',
                'email' => 'sometimes|required|email',
                'status' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if user is Admin
            if ($user instanceof Admin) {
                // Validate email uniqueness for admins
                if ($request->has('email') && $request->email !== $user->email) {
                    $existingAdmin = Admin::where('email', $request->email)->where('id', '!=', $user->id)->first();
                    if ($existingAdmin) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Email already in use',
                        ], 422);
                    }
                }

                // Update admin fields
                if ($request->has('firstname')) {
                    $user->fname = $request->firstname;
                }
                
                if ($request->has('middlename')) {
                    $user->mname = $request->middlename;
                }
                
                if ($request->has('lastname')) {
                    $user->lname = $request->lastname;
                }
                
                if ($request->has('email')) {
                    $user->email = $request->email;
                }
                
                if ($request->has('status')) {
                    $user->status = $request->status;
                }

                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => 'admin',
                        'firstname' => $user->fname,
                        'middlename' => $user->mname,
                        'lastname' => $user->lname,
                        'full_name' => $user->full_name,
                        'status' => $user->status,
                    ],
                ], 200);
            }

            // Otherwise, update staff profile
            // Validate staff_id and email uniqueness for staff
            if ($request->has('staff_id') && $request->staff_id !== $user->staff_id) {
                $existingStaff = Staff::where('staff_id', $request->staff_id)->where('id', '!=', $user->id)->first();
                if ($existingStaff) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Staff ID already in use',
                    ], 422);
                }
            }

            if ($request->has('email') && $request->email !== $user->email) {
                $existingStaff = Staff::where('email', $request->email)->where('id', '!=', $user->id)->first();
                if ($existingStaff) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email already in use',
                    ], 422);
                }
            }

            // Update staff fields
            if ($request->has('staff_id')) {
                $user->staff_id = $request->staff_id;
            }
            
            if ($request->has('firstname')) {
                $user->fname = $request->firstname;
            }
            
            if ($request->has('middlename')) {
                $user->mname = $request->middlename;
            }
            
            if ($request->has('lastname')) {
                $user->lname = $request->lastname;
            }
            
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            
            if ($request->has('status')) {
                $user->status = $request->status;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'staff' => [
                    'id' => $user->id,
                    'staff_id' => $user->staff_id,
                    'email' => $user->email,
                    'role' => 'staff',
                    'firstname' => $user->fname,
                    'middlename' => $user->mname,
                    'lastname' => $user->lname,
                    'full_name' => trim("{$user->fname} {$user->mname} {$user->lname}"),
                    'status' => $user->status ?? 'Active',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Update profile exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
            ], 500);
        }
    }

    /**
     * Change staff password
     */
    public function changePassword(Request $request)
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
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            // Check if new password is different from current
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from current password',
                ], 400);
            }

            // Update password using direct DB update to avoid double hashing
            $hashedPassword = Hash::make($request->new_password);
            
            // Determine table name based on user type
            $tableName = $user instanceof Admin ? 'admins' : 'staff';
            
            \Log::info('Changing password', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_type' => $tableName,
            ]);
            
            \DB::table($tableName)
                ->where('id', $user->id)
                ->update([
                    'password' => $hashedPassword,
                    'updated_at' => now()
                ]);

            \Log::info('Password changed successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_type' => $tableName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Change password exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
            ], 500);
        }
    }
}
