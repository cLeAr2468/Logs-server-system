<?php

namespace App\Http\Controllers;

use App\Models\Masterlist;
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
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|string|unique:masterlist,student_id',
                'fname' => 'required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email|unique:masterlist,email',
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

            return response()->json([
                'success' => true,
                'message' => 'Student added to masterlist successfully!',
                'data' => $masterlist,
            ], 201);
        } catch (\Exception $e) {
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

            return response()->json([
                'success' => true,
                'message' => 'Masterlist entry updated successfully!',
                'data' => $masterlist,
            ], 200);
        } catch (\Exception $e) {
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
    public function destroy($id)
    {
        try {
            $masterlist = Masterlist::findOrFail($id);
            $masterlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Masterlist entry deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
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

            // Process data rows
            $imported = 0;
            $skipped = 0;
            $errors = [];

            for ($i = 1; $i < count($csvData); $i++) {
                $row = $csvData[$i];
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

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

                // Check for duplicates
                $existingEntry = Masterlist::where('student_id', $data['student_id'])
                    ->orWhere('email', $data['email'])
                    ->first();

                if ($existingEntry) {
                    $skipped++;
                    $errors[] = "Row " . ($i + 1) . ": Duplicate student_id or email";
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

            return response()->json([
                'success' => true,
                'message' => "Import completed successfully! Imported: {$imported}, Skipped: {$skipped}",
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import CSV file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
