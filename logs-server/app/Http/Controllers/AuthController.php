<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'student_id' => 'required|unique:users',
            'fname' => 'required',
            'mname' => 'nullable',
            'lname' => 'required',
            'email' => 'required|email|unique:users',
            'course' => 'required',
            'year_level' => 'required',
            'password' => 'required|min:6',
        ]);

        // Check if student_id exists in masterlist
        $masterlistEntry = \App\Models\Masterlist::where('student_id', $request->student_id)->first();
        
        if (!$masterlistEntry) {
            return response()->json([
                'message' => 'Student ID not found in masterlist. Please contact the administrator.'
            ], 400);
        }

        // Verify that the student's information matches the masterlist
        // Optional: You can add more validation here if needed
        // For example, check if names match

        $user = User::create([
            'student_id' => $request->student_id,
            'fname' => $request->fname,
            'mname' => $request->mname,
            'lname' => $request->lname,
            'email' => $request->email,
            'course' => $request->course,
            'year_level' => $request->year_level,
            'status' => 'Active', // Automatically set status to Active
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'fname' => $user->fname,
                'mname' => $user->mname,
                'lname' => $user->lname,
                'email' => $user->email,
                'course' => $user->course,
                'year_level' => $user->year_level,
                'status' => $user->status,
            ]
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // simple token (Sanctum not required for basic setup)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'fname' => $user->fname,
                'mname' => $user->mname,
                'lname' => $user->lname,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'course' => $user->course,
                'year_level' => $user->year_level,
                'status' => $user->status ?? 'Active',
                'address' => $user->address ?? '',
            ]
        ]);
    }

    // UPDATE USER
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $request->validate([
            'student_id' => 'sometimes|required|unique:users,student_id,' . $id,
            'fname' => 'sometimes|required',
            'mname' => 'nullable',
            'lname' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'course' => 'sometimes|required',
            'year_level' => 'sometimes|required',
            'password' => 'sometimes|required|min:6',
        ]);

        if ($request->has('student_id')) {
            $user->student_id = $request->student_id;
        }
        
        if ($request->has('fname')) {
            $user->fname = $request->fname;
        }
        
        if ($request->has('mname')) {
            $user->mname = $request->mname;
        }
        
        if ($request->has('lname')) {
            $user->lname = $request->lname;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('course')) {
            $user->course = $request->course;
        }
        
        if ($request->has('year_level')) {
            $user->year_level = $request->year_level;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
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
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'firstname' => $user->fname,
                'middlename' => $user->mname,
                'lastname' => $user->lname,
                'email' => $user->email,
                'course' => $user->course,
                'year' => $user->year_level,
                'address' => $user->address ?? '',
                'status' => $user->status ?? 'Active',
                'profile' => $user->profile ?? '',
            ]
        ], 200);
    }

    // UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'student_id' => 'sometimes|required|unique:users,student_id,' . $user->id,
            'firstname' => 'sometimes|required',
            'middlename' => 'nullable',
            'lastname' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'course' => 'sometimes|required',
            'year' => 'sometimes|required',
            'address' => 'nullable',
            'status' => 'nullable',
        ]);

        // Update user fields
        if ($request->has('student_id')) {
            $user->student_id = $request->student_id;
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
        
        if ($request->has('course')) {
            $user->course = $request->course;
        }
        
        if ($request->has('year')) {
            $user->year_level = $request->year;
        }
        
        if ($request->has('address')) {
            $user->address = $request->address;
        }
        
        if ($request->has('status')) {
            $user->status = $request->status;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'student_id' => $user->student_id,
                'firstname' => $user->fname,
                'middlename' => $user->mname,
                'lastname' => $user->lname,
                'email' => $user->email,
                'course' => $user->course,
                'year' => $user->year_level,
                'address' => $user->address ?? '',
                'status' => $user->status ?? 'Active',
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

        // Check if user exists in users table OR staff table
        $user = User::where('email', $request->email)->first();
        $userType = 'user';
        
        // If not found in users, check staff table
        if (!$user) {
            $user = \App\Models\Staff::where('email', $request->email)->first();
            $userType = 'staff';
        }

        if (!$user) {
            return response()->json([
                'message' => 'If this email exists in our system, you will receive a password reset code.'
            ], 200); // Don't reveal if email exists for security
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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

        // Send OTP via email
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->fname));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }

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

        // Check if user exists in users table OR staff table
        $user = User::where('email', $request->email)->first();
        
        // If not found in users, check staff table
        if (!$user) {
            $user = \App\Models\Staff::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Generate new 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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

        // Send OTP via email
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->fname));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resend OTP email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }

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

        // Find user in users table OR staff table
        $user = User::where('email', $request->email)->first();
        $isStaff = false;
        
        // If not found in users, check staff table
        if (!$user) {
            $user = \App\Models\Staff::where('email', $request->email)->first();
            $isStaff = true;
        }

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Mark token as used
        $resetToken->is_used = true;
        $resetToken->save();

        // Revoke all existing tokens for this user
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully',
            'user_type' => $isStaff ? 'staff' : 'user'
        ], 200);
    }
}