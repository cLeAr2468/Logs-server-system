<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Admin/Staff Login
     * Supports:
     * 1. Default admin credentials (admin@nwssu.edu.ph / admin)
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

            // Check 1: Default admin credentials
            if ($email === 'admin@nwssu.edu.ph' && $password === 'admin') {
                // Create a token for default admin (using first user or create dummy record)
                // We'll use a simple approach - create a token without a model
                $token = base64_encode($email . ':' . time());

                return response()->json([
                    'success' => true,
                    'message' => 'Admin login successful',
                    'token' => $token,
                    'user' => [
                        'id' => 0,
                        'email' => $email,
                        'role' => 'admin',
                        'full_name' => 'System Administrator',
                    ],
                ], 200);
            }

            // Check 2: Staff credentials from database
            // Use case-insensitive email search
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
                // Staff not found in database
                \Log::warning('Staff login failed - email not found', [
                    'email' => $email
                ]);
            }

            // Check 3: Invalid credentials
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
            // Check if token is for default admin
            $authHeader = $request->header('Authorization');
            if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
                
                // Check if it's the default admin token
                $decoded = base64_decode($token);
                if (strpos($decoded, 'admin@nwssu.edu.ph') === 0) {
                    return response()->json([
                        'success' => true,
                        'user' => [
                            'id' => 0,
                            'email' => 'admin@nwssu.edu.ph',
                            'role' => 'admin',
                            'full_name' => 'System Administrator',
                            'firstname' => 'System',
                            'middlename' => '',
                            'lastname' => 'Administrator',
                            'staff_id' => 'ADMIN',
                            'position' => 'System Administrator',
                            'contact_number' => '',
                            'status' => 'Active',
                        ],
                    ], 200);
                }
            }

            // Otherwise, get staff profile
            $staff = $request->user();
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'staff' => [
                    'id' => $staff->id,
                    'staff_id' => $staff->staff_id,
                    'email' => $staff->email,
                    'role' => 'staff',
                    'firstname' => $staff->fname,
                    'middlename' => $staff->mname,
                    'lastname' => $staff->lname,
                    'full_name' => trim("{$staff->fname} {$staff->mname} {$staff->lname}"),
                    'position' => $staff->position ?? 'Staff',
                    'contact_number' => $staff->contact_number ?? '',
                    'status' => $staff->status ?? 'Active',
                    'profile' => $staff->profile ?? '',
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
            // Check if it's default admin (cannot update)
            $authHeader = $request->header('Authorization');
            if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
                $decoded = base64_decode($token);
                if (strpos($decoded, 'admin@nwssu.edu.ph') === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Default admin profile cannot be updated',
                    ], 403);
                }
            }

            // Get authenticated staff
            $staff = $request->user();
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'staff_id' => 'sometimes|required|unique:staff,staff_id,' . $staff->id,
                'firstname' => 'sometimes|required',
                'middlename' => 'nullable',
                'lastname' => 'sometimes|required',
                'email' => 'sometimes|required|email|unique:staff,email,' . $staff->id,
                'position' => 'nullable',
                'contact_number' => 'nullable',
                'status' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update staff fields
            if ($request->has('staff_id')) {
                $staff->staff_id = $request->staff_id;
            }
            
            if ($request->has('firstname')) {
                $staff->fname = $request->firstname;
            }
            
            if ($request->has('middlename')) {
                $staff->mname = $request->middlename;
            }
            
            if ($request->has('lastname')) {
                $staff->lname = $request->lastname;
            }
            
            if ($request->has('email')) {
                $staff->email = $request->email;
            }
            
            if ($request->has('position')) {
                $staff->position = $request->position;
            }
            
            if ($request->has('contact_number')) {
                $staff->contact_number = $request->contact_number;
            }
            
            if ($request->has('status')) {
                $staff->status = $request->status;
            }

            $staff->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'staff' => [
                    'id' => $staff->id,
                    'staff_id' => $staff->staff_id,
                    'email' => $staff->email,
                    'role' => 'staff',
                    'firstname' => $staff->fname,
                    'middlename' => $staff->mname,
                    'lastname' => $staff->lname,
                    'full_name' => trim("{$staff->fname} {$staff->mname} {$staff->lname}"),
                    'position' => $staff->position ?? 'Staff',
                    'contact_number' => $staff->contact_number ?? '',
                    'status' => $staff->status ?? 'Active',
                    'profile' => $staff->profile ?? '',
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
}
