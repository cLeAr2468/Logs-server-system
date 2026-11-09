<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use App\Models\PasswordResetToken;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // GET STUDENT INFO FROM MASTERLIST
    public function getStudentFromMasterlist($studentId)
    {
        $student = \App\Models\Masterlist::where('student_id', $studentId)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student ID not found in masterlist'
            ], 404);
        }

        return response()->json([
            'student' => [
                'student_id' => $student->student_id,
                'fname' => $student->fname,
                'mname' => $student->mname,
                'lname' => $student->lname,
                'email' => $student->email,
                'course' => $student->course,
                'year_level' => $student->year_level,
            ]
        ], 200);
    }

    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'suffix' => 'nullable|string|in:Jr.,Sr.,II,III,IV,V',
            'region' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'password' => 'required|min:6',
        ]);

        // Check if student_id exists in masterlist
        $masterlistEntry = \App\Models\Masterlist::where('student_id', $request->student_id)->first();
        
        if (!$masterlistEntry) {
            return response()->json([
                'message' => 'Student ID not found in masterlist. Please contact the administrator.'
            ], 400);
        }

        // If user provided any masterlist fields (edited), update masterlist
        $masterlistUpdated = false;
        if ($request->has('fname') || $request->has('lname') || $request->has('email') || 
            $request->has('course') || $request->has('year_level')) {
            
            $updateData = [];
            if ($request->filled('fname')) $updateData['fname'] = $request->fname;
            if ($request->filled('mname')) $updateData['mname'] = $request->mname;
            if ($request->filled('lname')) $updateData['lname'] = $request->lname;
            if ($request->filled('email')) $updateData['email'] = $request->email;
            if ($request->filled('course')) $updateData['course'] = $request->course;
            if ($request->filled('year_level')) $updateData['year_level'] = $request->year_level;
            
            if (!empty($updateData)) {
                $masterlistEntry->update($updateData);
                $masterlistUpdated = true;
            }
        }

        // Check if student_id already registered in users table
        if (User::where('student_id', $request->student_id)->exists()) {
            return response()->json([
                'message' => 'This Student ID is already registered. Please login instead.'
            ], 422);
        }

        // Check if student_id exists in staff table
        if (\App\Models\Staff::where('staff_id', $request->student_id)->exists()) {
            return response()->json([
                'message' => 'This ID is registered as a staff ID. Please use your staff account.'
            ], 422);
        }

        // Create user account (pure relationship - no duplicate data)
        $user = User::create([
            'student_id' => $request->student_id,
            'suffix' => $request->suffix,
            'region' => $request->region,
            'province' => $request->province,
            'municipality' => $request->municipality,
            'barangay' => $request->barangay,
            'status' => 'Active',
            'password' => Hash::make($request->password),
        ]);

        // Log activity
        $authUser = $request->user();
        if ($authUser) {
            try {
                $description = 'Created client account: ' . $user->masterlist->fname . ' ' . $user->masterlist->lname . ' (' . $user->student_id . ')';
                if ($masterlistUpdated) {
                    $description .= ' (Updated masterlist info)';
                }
                
                ActivityLog::create([
                    'user_type' => $authUser instanceof \App\Models\Admin ? 'admin' : 'staff',
                    'user_id' => $authUser instanceof \App\Models\Admin ? $authUser->admin_id : $authUser->staff_id,
                    'user_name' => trim($authUser->fname . ' ' . $authUser->lname),
                    'action' => 'created',
                    'module' => 'Users',
                    'description' => $description,
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $logError) {
                \Log::error('Activity log failed during user creation: ' . $logError->getMessage());
            }
        } else {
            try {
                $description = 'Client self-registered: ' . $user->masterlist->fname . ' ' . $user->masterlist->lname . ' (' . $user->student_id . ')';
                if ($masterlistUpdated) {
                    $description .= ' (Updated masterlist info)';
                }
                
                ActivityLog::create([
                    'user_type' => 'client',
                    'user_id' => $user->student_id,
                    'user_name' => trim($user->masterlist->fname . ' ' . $user->masterlist->lname),
                    'action' => 'registered',
                    'module' => 'Authentication',
                    'description' => $description,
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $logError) {
                \Log::error('Activity log failed during self-registration: ' . $logError->getMessage());
            }
        }

        // Load masterlist relationship for response
        $user->load('masterlist');

        return response()->json([
            'message' => 'User registered successfully' . ($masterlistUpdated ? ' (Masterlist info updated)' : ''),
            'user' => [
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
            ]
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            // Find user by email through masterlist relationship
            $user = User::whereHas('masterlist', function($query) use ($request) {
                $query->where('email', $request->email);
            })->with('masterlist')->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Log client login activity (non-blocking)
            try {
                ActivityLog::create([
                    'user_type' => 'client',
                    'user_id' => $user->student_id,
                    'user_name' => trim($user->masterlist->fname . ' ' . $user->masterlist->lname),
                    'action' => 'logged_in',
                    'module' => 'Authentication',
                    'description' => 'Client logged in',
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
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
                    'status' => $user->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATE USER
    public function update(Request $request, $id)
    {
        $user = User::with('masterlist')->find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $request->validate([
            'suffix' => 'nullable|string',
            'region' => 'nullable|string',
            'province' => 'nullable|string',
            'municipality' => 'nullable|string',
            'barangay' => 'nullable|string',
            'password' => 'sometimes|required|min:6',
        ]);

        // Only allow updating user-specific fields (address, password)
        // Name, email, course, year_level should be updated in masterlist
        
        if ($request->has('suffix')) {
            $user->suffix = $request->suffix;
        }
        
        if ($request->has('region')) {
            $user->region = $request->region;
        }
        
        if ($request->has('province')) {
            $user->province = $request->province;
        }
        
        if ($request->has('municipality')) {
            $user->municipality = $request->municipality;
        }
        
        if ($request->has('barangay')) {
            $user->barangay = $request->barangay;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
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
            ]
        ]);
    }

    // DELETE USER
    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    // GET PROFILE
    public function getProfile(Request $request)
    {
        $user = $request->user()->load('masterlist');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'firstname' => $user->masterlist->fname,
                'middlename' => $user->masterlist->mname,
                'lastname' => $user->masterlist->lname,
                'email' => $user->masterlist->email,
                'course' => $user->masterlist->course,
                'year' => $user->masterlist->year_level,
                'suffix' => $user->suffix ?? '',
                'region' => $user->region ?? '',
                'province' => $user->province ?? '',
                'municipality' => $user->municipality ?? '',
                'barangay' => $user->barangay ?? '',
                'status' => $user->status ?? 'Active',
                'profile' => $user->profile ?? '',
            ]
        ], 200);
    }

    // UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user()->load('masterlist');

        $request->validate([
            'suffix' => 'nullable|string',
            'region' => 'nullable|string',
            'barangay' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
        ]);

        // Only allow updating user-specific fields (address info)
        // Name, email, course, year_level are READ-ONLY from masterlist
        
        if ($request->has('suffix')) {
            $user->suffix = $request->suffix;
        }
        
        if ($request->has('region')) {
            $user->region = $request->region;
        }
        
        if ($request->has('barangay')) {
            $user->barangay = $request->barangay;
        }
        
        if ($request->has('municipality')) {
            $user->municipality = $request->municipality;
        }
        
        if ($request->has('province')) {
            $user->province = $request->province;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'firstname' => $user->masterlist->fname,
                'middlename' => $user->masterlist->mname,
                'lastname' => $user->masterlist->lname,
                'email' => $user->masterlist->email,
                'course' => $user->masterlist->course,
                'year' => $user->masterlist->year_level,
                'suffix' => $user->suffix,
                'region' => $user->region,
                'province' => $user->province,
                'municipality' => $user->municipality,
                'barangay' => $user->barangay,
                'status' => $user->status,
                'profile' => $user->profile ?? '',
            ]
        ], 200);
    }

    // CHANGE PASSWORD
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|different:current_password',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Optionally revoke all tokens except current one to force re-login on other devices
        // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Log client logout activity
        if ($user instanceof User) {
            ActivityLog::create([
                'user_type' => 'client',
                'user_id' => $user->student_id,
                'user_name' => trim($user->fname . ' ' . $user->lname),
                'action' => 'logged_out',
                'module' => 'Authentication',
                'description' => 'Client logged out',
                'ip_address' => $request->ip(),
            ]);
        }
        
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // FORGOT PASSWORD - Send OTP
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Check if user exists in users table OR staff table OR admins table
        $user = User::where('email', $request->email)->first();
        $userType = 'user';
        
        // If not found in users, check staff table
        if (!$user) {
            $user = \App\Models\Staff::where('email', $request->email)->first();
            $userType = 'staff';
        }
        
        // If not found in staff, check admins table
        if (!$user) {
            $user = \App\Models\Admin::where('email', $request->email)->first();
            $userType = 'admin';
        }

        if (!$user) {
            return response()->json([
                'message' => 'Email not found. Please register first.',
                'error' => 'email_not_found'
            ], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Send OTP via email FIRST (before saving to database)
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->email));
            
            // Log email sending for debugging
            \Log::info('OTP Email sent successfully', [
                'email' => $user->email,
                'user_type' => $userType,
                'otp' => $otp
            ]);
        } catch (\Exception $e) {
            // Log the full error for debugging
            \Log::error('Failed to send OTP email', [
                'email' => $user->email,
                'user_type' => $userType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't save OTP if email failed
            return response()->json([
                'message' => 'Failed to send OTP email. Please check your email address and try again.',
                'error' => 'email_send_failed',
                'details' => config('app.debug') ? $e->getMessage() : 'Email service temporarily unavailable'
            ], 500);
        }

        // Only save OTP to database if email was sent successfully
        // Delete any existing unused tokens for this email
        PasswordResetToken::where('email', $request->email)
            ->where('is_used', false)
            ->delete();

        // Create new password reset token
        $resetToken = PasswordResetToken::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5), // OTP expires in 5 minutes
            'is_used' => false,
        ]);

        return response()->json([
            'message' => 'OTP sent successfully to your email',
            'email' => $request->email,
            'user_type' => $userType // Return user type for frontend reference
        ], 200);
    }

    // VERIFY OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        // Find the most recent unused token for this email
        $resetToken = PasswordResetToken::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$resetToken) {
            return response()->json([
                'message' => 'Invalid OTP code'
            ], 400);
        }

        if ($resetToken->isExpired()) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }

        return response()->json([
            'message' => 'OTP verified successfully',
            'email' => $request->email,
            'token_id' => $resetToken->id
        ], 200);
    }

    // RESEND OTP
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Check if user exists in users table OR staff table OR admins table
        $user = User::where('email', $request->email)->first();
        
        // If not found in users, check staff table
        if (!$user) {
            $user = \App\Models\Staff::where('email', $request->email)->first();
        }
        
        // If not found in staff, check admins table
        if (!$user) {
            $user = \App\Models\Admin::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Generate new 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Send OTP via email FIRST (before saving to database)
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->email));
            
            // Log email sending for debugging
            \Log::info('OTP Email resent successfully', [
                'email' => $user->email,
                'otp' => $otp
            ]);
        } catch (\Exception $e) {
            // Log the full error for debugging
            \Log::error('Failed to resend OTP email', [
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't save OTP if email failed
            return response()->json([
                'message' => 'Failed to resend OTP email. Please try again later.',
                'error' => 'email_send_failed',
                'details' => config('app.debug') ? $e->getMessage() : 'Email service temporarily unavailable'
            ], 500);
        }

        // Only save OTP to database if email was sent successfully
        // Delete any existing unused tokens for this email
        PasswordResetToken::where('email', $request->email)
            ->where('is_used', false)
            ->delete();

        // Create new password reset token
        $resetToken = PasswordResetToken::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
        ]);

        return response()->json([
            'message' => 'OTP resent successfully to your email',
            'email' => $request->email
        ], 200);
    }

    // RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|min:6|confirmed'
        ]);

        // Find the token
        $resetToken = PasswordResetToken::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$resetToken) {
            return response()->json([
                'message' => 'Invalid OTP code'
            ], 400);
        }

        if ($resetToken->isExpired()) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }

        // Find user in users table OR staff table OR admins table
        $user = User::where('email', $request->email)->first();
        $isStaff = false;
        $isAdmin = false;
        $userType = 'user';
        
        // If not found in users, check staff table
        if (!$user) {
            $user = \App\Models\Staff::where('email', $request->email)->first();
            $isStaff = true;
            $userType = 'staff';
        }
        
        // If not found in staff, check admins table
        if (!$user) {
            $user = \App\Models\Admin::where('email', $request->email)->first();
            $isAdmin = true;
            $userType = 'admin';
        }

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Update password
        // For Staff and Admin models, we need to use DB update to bypass auto-hashing
        // since they have 'password' => 'hashed' in casts
        if ($isAdmin) {
            $hashedPassword = Hash::make($request->password);
            
            // Log before update
            \Log::info('Resetting admin password', [
                'email' => $request->email,
                'admin_id' => $user->id,
                'old_password_hash' => substr($user->password, 0, 20) . '...',
                'new_password_hash' => substr($hashedPassword, 0, 20) . '...'
            ]);
            
            // Use DB update to bypass model events and auto-hashing
            $affectedRows = \DB::table('admins')
                ->where('id', $user->id)
                ->update([
                    'password' => $hashedPassword,
                    'updated_at' => now()
                ]);
            
            // Refresh the model to get updated data
            $user->refresh();
            
            // Log after update
            \Log::info('Admin password reset successful', [
                'email' => $request->email,
                'admin_id' => $user->id,
                'affected_rows' => $affectedRows,
                'updated_password_hash' => substr($user->password, 0, 20) . '...'
            ]);
        } elseif ($isStaff) {
            $hashedPassword = Hash::make($request->password);
            
            // Log before update
            \Log::info('Resetting staff password', [
                'email' => $request->email,
                'staff_id' => $user->id,
                'old_password_hash' => substr($user->password, 0, 20) . '...',
                'new_password_hash' => substr($hashedPassword, 0, 20) . '...'
            ]);
            
            // Use DB update to bypass model events and auto-hashing
            $affectedRows = \DB::table('staff')
                ->where('id', $user->id)
                ->update([
                    'password' => $hashedPassword,
                    'updated_at' => now()
                ]);
            
            // Refresh the model to get updated data
            $user->refresh();
            
            // Log after update
            \Log::info('Staff password reset successful', [
                'email' => $request->email,
                'staff_id' => $user->id,
                'affected_rows' => $affectedRows,
                'updated_password_hash' => substr($user->password, 0, 20) . '...'
            ]);
        } else {
            // For regular users, just hash and save normally
            \Log::info('Resetting user password', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
            
            $user->password = Hash::make($request->password);
            $user->save();
            
            \Log::info('User password reset successful', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
        }

        // Mark token as used
        $resetToken->is_used = true;
        $resetToken->save();

        // Revoke all existing tokens for this user
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully',
            'user_type' => $userType
        ], 200);
    }
}