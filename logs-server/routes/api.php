<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MasterlistController;
use App\Http\Controllers\ReportController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API working'
    ]);
});

// AUTH ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/masterlist/student/{studentId}', [AuthController::class, 'getStudentFromMasterlist']);

// STAFF AUTH ROUTES
Route::post('/staff/login', [StaffController::class, 'login']);
Route::post('/staff/register', [StaffController::class, 'store']);

// ADMIN LOGIN ROUTE (includes default admin and staff login)
Route::post('/admin/login', [AdminController::class, 'login']);


// PASSWORD RESET ROUTES
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// TRANSACTION/APPOINTMENT ROUTES (Protected - Require Authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User appointment routes
    Route::post('/appointments', [TransactionController::class, 'store']);
    Route::get('/my-appointments', [TransactionController::class, 'getUserTransactions']);
    Route::put('/appointments/{id}', [TransactionController::class, 'update']);
    Route::put('/appointments/{id}/cancel', [TransactionController::class, 'cancel']);
    Route::get('/appointments/available-slots', [TransactionController::class, 'getAvailableSlots']);
    
    // Admin routes (you can add middleware to restrict to admin only)
    Route::get('/appointments', [TransactionController::class, 'index']);
    Route::get('/appointments/{id}', [TransactionController::class, 'show']);
    Route::put('/appointments/{id}/status', [TransactionController::class, 'updateStatus']);
    Route::delete('/appointments/{id}', [TransactionController::class, 'destroy']);

    // USER PROFILE ROUTES
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // FEEDBACK ROUTES
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::get('/my-feedback', [FeedbackController::class, 'getUserFeedback']);
    
    // Admin feedback routes
    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::get('/feedback/statistics', [FeedbackController::class, 'statistics']);
    Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);

    // DASHBOARD ROUTES
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity']);
});

// ADMIN-ONLY ROUTES (Custom admin auth middleware for both default admin and staff)
Route::middleware('admin.auth')->group(function () {
    // Admin profile and logout
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::get('/admin/profile', [AdminController::class, 'getProfile']);
    
    // Staff management (admin/staff access)
    Route::get('/staff', [StaffController::class, 'index']);
    Route::get('/staff/{id}', [StaffController::class, 'show']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
    Route::post('/staff/logout', [StaffController::class, 'logout']);
    
    // User/Client management (admin/staff access)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/statistics', [UserController::class, 'statistics']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    
    // Masterlist management (admin/staff access)
    Route::get('/masterlist', [MasterlistController::class, 'index']);
    Route::post('/masterlist', [MasterlistController::class, 'store']);
    Route::post('/masterlist/import', [MasterlistController::class, 'importCSV']);
    Route::get('/masterlist/statistics', [MasterlistController::class, 'statistics']);
    Route::get('/masterlist/{id}', [MasterlistController::class, 'show']);
    Route::put('/masterlist/{id}', [MasterlistController::class, 'update']);
    Route::delete('/masterlist/{id}', [MasterlistController::class, 'destroy']);
    
    // Announcements (admin/staff access)
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);
    Route::post('/announcements/{id}/publish', [AnnouncementController::class, 'publish']);
    Route::post('/announcements/{id}/unpublish', [AnnouncementController::class, 'unpublish']);
    
    // Transaction management by admin/staff
    Route::get('/admin/appointments', [TransactionController::class, 'index']);
    Route::get('/admin/appointments/{id}', [TransactionController::class, 'show']);
    Route::put('/admin/appointments/{id}/status', [TransactionController::class, 'updateStatus']);
    Route::delete('/admin/appointments/{id}', [TransactionController::class, 'destroy']);
    Route::post('/transactions/validate-student', [TransactionController::class, 'validateStudentId']);
    Route::post('/transactions/create-by-admin', [TransactionController::class, 'storeByAdmin']);
    
    // Reports and Analytics (admin/staff access)
    Route::get('/reports/statistics', [ReportController::class, 'getStatistics']);
    Route::get('/reports/by-purpose', [ReportController::class, 'getTransactionsByPurpose']);
    Route::get('/reports/monthly-trends', [ReportController::class, 'getMonthlyTrends']);
    Route::get('/reports/export', [ReportController::class, 'exportReport']);
    Route::get('/reports/recent', [ReportController::class, 'getRecentReports']);
    
    // Admin Dashboard (admin/staff access)
    Route::get('/admin/dashboard/statistics', [DashboardController::class, 'getAdminStatistics']);
    Route::get('/admin/dashboard/recent-transactions', [DashboardController::class, 'getRecentTransactions']);
    Route::get('/admin/dashboard/performance', [DashboardController::class, 'getPerformanceSummary']);
});

// PUBLIC ANNOUNCEMENT ROUTES (No authentication required)
Route::get('/public/announcements', [AnnouncementController::class, 'getPublished']);