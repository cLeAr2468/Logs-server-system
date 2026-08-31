<?php

namespace App\Http\Controllers;

use App\Models\Masterlist;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MasterlistController extends Controller
{
    /**
     * Get all masterlist entries
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $masterlist = Masterlist::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'masterlist' => $masterlist,
                'total' => $masterlist->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch masterlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new masterlist entry
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Start transaction
        \DB::beginTransaction();
        
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|string',
                'fname' => 'required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email',
                'course' => 'required|string',
                'year_level' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if student_id already exists in masterlist
            if (Masterlist::where('student_id', $request->student_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Student ID already exists in masterlist',
                ], 422);
            }

            // Check if student_id exists in users table (registered clients)
            if (\App\Models\User::where('student_id', $request->student_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Student ID is already registered as a client account',
                ], 422);
            }

            // Check if student_id exists in staff table
            if (\App\Models\Staff::where('staff_id', $request->student_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID is already used as a staff ID',
                ], 422);
            }

            // Check if email already exists in masterlist
            if (Masterlist::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email already exists in masterlist',
                ], 422);
            }

            // Check if email exists in users table
            if (\App\Models\User::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered as a client account',
                ], 422);
            }

            // Check if email exists in staff table
            if (\App\Models\Staff::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered as a staff account',
                ], 422);
            }

            // Create the masterlist entry
            $masterlist = Masterlist::create([
                'student_id' => $request->student_id,
                'fname' => $request->fname,
                'mname' => $request->mname,
                'lname' => $request->lname,
                'email' => $request->email,
                'course' => $request->course,
                'year_level' => $request->year_level,
                'status' => 'Active',
            ]);

            // Log activity (wrapped in try-catch to prevent logging errors from failing the operation)
            $user = $request->user();
            if ($user) {
                try {
                    ActivityLog::create([
                        'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                        'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                        'user_name' => trim($user->fname . ' ' . $user->lname),
                        'action' => 'created',
                        'module' => 'Masterlist',
                        'description' => 'Added student to masterlist: ' . $masterlist->fname . ' ' . $masterlist->lname . ' (' . $masterlist->student_id . ')',
                        'ip_address' => $request->ip(),
                    ]);
                } catch (\Exception $logError) {
                    // Silently fail activity logging - don't break the operation
                    \Log::error('Activity log failed during masterlist creation: ' . $logError->getMessage());
                }
            }

            // Commit transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student added to masterlist successfully!',
                'data' => $masterlist,
            ], 201);
        } catch (\Exception $e) {
            // Rollback on any exception
            \DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add student to masterlist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single masterlist entry
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $masterlist = Masterlist::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $masterlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Masterlist entry not found',
            ], 404);
        }
    }

    /**
     * Update a masterlist entry
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Start transaction
        \DB::beginTransaction();
        
        try {
            $masterlist = Masterlist::findOrFail($id);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'student_id' => 'sometimes|required|string|unique:masterlist,student_id,' . $id,
                'fname' => 'sometimes|required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:masterlist,email,' . $id,
                'course' => 'sometimes|required|string',
                'year_level' => 'sometimes|required|string',
                'status' => 'sometimes|required|in:Active,Inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update masterlist data
            $masterlist->update($request->only([
                'student_id', 
                'fname', 
                'mname', 
                'lname', 
                'email', 
                'course', 
                'year_level', 
                'status'
            ]));

            // Log activity (wrapped in try-catch to prevent logging errors from failing the operation)
            $user = $request->user();
            if ($user) {
                try {
                    ActivityLog::create([
                        'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                        'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                        'user_name' => trim($user->fname . ' ' . $user->lname),
                        'action' => 'updated',
                        'module' => 'Masterlist',
                        'description' => 'Updated masterlist entry: ' . $masterlist->fname . ' ' . $masterlist->lname . ' (' . $masterlist->student_id . ')',
                        'ip_address' => $request->ip(),
                    ]);
                } catch (\Exception $logError) {
                    // Silently fail activity logging - don't break the operation
                    \Log::error('Activity log failed during masterlist update: ' . $logError->getMessage());
                }
            }

            // Commit transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Masterlist entry updated successfully!',
                'data' => $masterlist,
            ], 200);
        } catch (\Exception $e) {
            // Rollback on any exception
            \DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update masterlist entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a masterlist entry
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        // Start transaction
        \DB::beginTransaction();
        
        try {
            $masterlist = Masterlist::findOrFail($id);
            $studentName = $masterlist->fname . ' ' . $masterlist->lname;
            $studentId = $masterlist->student_id;
            
            $masterlist->delete();

            // Log activity (wrapped in try-catch to prevent logging errors from failing the operation)
            $user = $request->user();
            if ($user) {
                try {
                    ActivityLog::create([
                        'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                        'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                        'user_name' => trim($user->fname . ' ' . $user->lname),
                        'action' => 'deleted',
                        'module' => 'Masterlist',
                        'description' => 'Deleted masterlist entry: ' . $studentName . ' (' . $studentId . ')',
                        'ip_address' => $request->ip(),
                    ]);
                } catch (\Exception $logError) {
                    // Silently fail activity logging - don't break the operation
                    \Log::error('Activity log failed during masterlist deletion: ' . $logError->getMessage());
                }
            }

            // Commit transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Masterlist entry deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            // Rollback on any exception
            \DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete masterlist entry',
            ], 500);
        }
    }

    /**
     * Get masterlist statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $total = Masterlist::count();
            $active = Masterlist::where('status', 'Active')->count();
            $inactive = Masterlist::where('status', 'Inactive')->count();

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
     * Import masterlist from CSV file
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importCSV(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $file = $request->file('csv_file');
            $path = $file->getRealPath();
            
            // Read CSV file
            $csvData = array_map('str_getcsv', file($path));
            
            if (count($csvData) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file must contain at least a header row and one data row',
                ], 422);
            }

            // Get headers
            $headers = array_map('trim', $csvData[0]);
            
            // Validate required headers
            $requiredHeaders = ['student_id', 'fname', 'lname', 'email', 'course', 'year_level'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            
            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required headers: ' . implode(', ', $missingHeaders),
                ], 422);
            }

            // Use database transaction
            \DB::beginTransaction();

            // Process data rows
            $imported = 0;
            $skipped = 0;
            $totalRows = 0;
            $duplicateRecords = [];
            $errors = [];

            for ($i = 1; $i < count($csvData); $i++) {
                $row = $csvData[$i];
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $totalRows++;

                // Map row data to headers
                $data = array_combine($headers, $row);
                
                // Trim all values
                $data = array_map('trim', $data);

                // Skip if required fields are empty
                if (empty($data['student_id']) || empty($data['fname']) || 
                    empty($data['lname']) || empty($data['email']) || 
                    empty($data['course']) || empty($data['year_level'])) {
                    $skipped++;
                    $errors[] = "Row " . ($i + 1) . ": Missing required fields";
                    continue;
                }

                // Check for duplicates by student_id
                $existingEntry = Masterlist::where('student_id', $data['student_id'])->first();

                if ($existingEntry) {
                    $skipped++;
                    $duplicateRecords[] = $data['student_id'];
                    continue;
                }
                
                // Also check for duplicate email
                $existingEmail = Masterlist::where('email', $data['email'])->first();
                
                if ($existingEmail) {
                    $skipped++;
                    $duplicateRecords[] = $data['email'];
                    continue;
                }

                try {
                    // Create masterlist entry
                    Masterlist::create([
                        'student_id' => $data['student_id'],
                        'fname' => $data['fname'],
                        'mname' => $data['mname'] ?? null,
                        'lname' => $data['lname'],
                        'email' => $data['email'],
                        'course' => $data['course'],
                        'year_level' => $data['year_level'],
                        'status' => 'Active',
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            // Prepare response message based on results
            $message = '';
            $success = true;
            $httpStatus = 200;
            
            if ($imported === 0 && $skipped === $totalRows && $totalRows > 0) {
                // All records are duplicates - rollback transaction
                \DB::rollBack();
                $message = "All records are already in the masterlist. No new records imported.";
                $success = false;
                $httpStatus = 200; // Still 200 but with success: false
            } elseif ($imported > 0 && $skipped > 0) {
                // Some imported, some skipped - commit transaction
                \DB::commit();
                $message = "{$imported} new record(s) imported successfully. {$skipped} duplicate record(s) skipped.";
                $success = true; // Success because some records were imported
            } elseif ($imported > 0 && $skipped === 0) {
                // All imported - commit transaction
                \DB::commit();
                $message = "All {$imported} record(s) imported successfully!";
                $success = true;
            } else {
                // None imported - rollback transaction
                \DB::rollBack();
                $message = "No records were imported. Please check your CSV file.";
                $success = false;
            }

            // Log activity only if records were actually imported
            // Wrap in try-catch to prevent activity log errors from failing the import
            $user = $request->user();
            if ($user && $imported > 0) {
                try {
                    ActivityLog::create([
                        'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                        'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                        'user_name' => trim($user->fname . ' ' . $user->lname),
                        'action' => 'created',
                        'module' => 'Masterlist',
                        'description' => "Imported {$imported} student(s) via CSV upload",
                        'ip_address' => $request->ip(),
                    ]);
                } catch (\Exception $logError) {
                    // Silently fail activity logging - don't break the import
                    \Log::error('Activity log failed during CSV import: ' . $logError->getMessage());
                }
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => $totalRows,
                'duplicates' => count($duplicateRecords),
                'errors' => $errors,
            ], $httpStatus);

        } catch (\Exception $e) {
            // Rollback on any exception
            \DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to import CSV file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
