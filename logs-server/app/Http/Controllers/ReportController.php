<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\ExportedReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportController extends Controller
{
    /**
     * Get dashboard statistics for reports - Pure Relationship
     */
    public function getStatistics()
    {
        try {
            // Total transactions
            $totalTransactions = Transaction::count();
            
            // Calculate monthly target (assume 6500 as target)
            $monthlyTarget = 6500;
            $targetPercentage = $totalTransactions > 0 ? ($totalTransactions / $monthlyTarget) * 100 : 0;
            
            // Average processing time (mock for now - in reality calculate from timestamps)
            $avgProcessingTime = '12.5 min';
            
            // Most requested purpose (Pure Relationship)
            $mostRequested = Transaction::join('purposes', 'transactions.purpose_id', '=', 'purposes.id')
                ->select('purposes.name as purpose', \DB::raw('count(*) as count'))
                ->groupBy('purposes.id', 'purposes.name')
                ->orderBy('count', 'desc')
                ->first();
            
            // Completion rate
            $completedCount = Transaction::where('status', 'completed')->count();
            $completionRate = $totalTransactions > 0 
                ? round(($completedCount / $totalTransactions) * 100, 1) 
                : 0;
            
            // Get feedback statistics
            $totalFeedback = \App\Models\Feedback::count();
            $avgRating = \App\Models\Feedback::avg('rating') ?? 0;
            
            return response()->json([
                'statistics' => [
                    'total_transactions' => $totalTransactions,
                    'target_percentage' => round($targetPercentage, 1),
                    'avg_processing_time' => $avgProcessingTime,
                    'most_requested' => [
                        'purpose' => $mostRequested->purpose ?? 'N/A',
                        'count' => $mostRequested->count ?? 0
                    ],
                    'completion_rate' => $completionRate,
                    'avg_rating' => round($avgRating, 1),
                    'total_feedback' => $totalFeedback
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Statistics Error: ' . $e->getMessage());
            return response()->json([
                'statistics' => [
                    'total_transactions' => 0,
                    'target_percentage' => 0,
                    'avg_processing_time' => '0 min',
                    'most_requested' => ['purpose' => 'N/A', 'count' => 0],
                    'completion_rate' => 0,
                    'avg_rating' => 0,
                    'total_feedback' => 0
                ]
            ], 200); // Return 200 with empty data instead of 500 error
        }
    }
    
    /**
     * Get transactions by purpose (for bar chart) - Pure Relationship
     */
    public function getTransactionsByPurpose()
    {
        try {
            $purposeData = Transaction::join('purposes', 'transactions.purpose_id', '=', 'purposes.id')
                ->select('purposes.name', \DB::raw('count(*) as value'))
                ->groupBy('purposes.id', 'purposes.name')
                ->orderBy('value', 'desc')
                ->get()
                ->map(function ($item, $index) {
                    $colors = ['#15592F', '#f59e0b', '#3b82f6', '#155d59', '#8b5cf6', '#ef4444'];
                    return [
                        'name' => $item->name,
                        'value' => $item->value,
                        'fill' => $colors[$index % count($colors)]
                    ];
                });
            
            return response()->json([
                'data' => $purposeData
            ]);
        } catch (\Exception $e) {
            \Log::error('Purpose Data Error: ' . $e->getMessage());
            return response()->json([
                'data' => []
            ], 200);
        }
    }
    
    /**
     * Get monthly transaction trends (for line chart)
     */
    public function getMonthlyTrends(Request $request)
    {
        // Get date range from request or default to last 12 months
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subMonths(11)->startOfMonth();
            
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();
        
        // Log for debugging
        \Log::info('Monthly Trends Request', [
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
        ]);
        
        // Get data for the date range
        $monthlyData = Transaction::select(
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
                DB::raw('count(*) as value')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->get()
            ->map(function($item) {
                return [
                    'month' => $item->month,
                    'value' => $item->value
                ];
            });
        
        // Log the result
        \Log::info('Monthly Trends Result', [
            'count' => $monthlyData->count(),
            'data' => $monthlyData->toArray()
        ]);
        
        return response()->json([
            'data' => $monthlyData,
            'debug' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_transactions' => Transaction::count(),
            ]
        ]);
    }
    
    /**
     * Export report to CSV/Excel/PDF and save to database
     */
    public function exportReport(Request $request)
    {
        try {
            $request->validate([
                'format' => 'sometimes|in:csv,excel,pdf',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
                'report_type' => 'sometimes|string',
                'include_summary' => 'sometimes|in:0,1',
                'include_details' => 'sometimes|in:0,1',
                'include_feedback' => 'sometimes|in:0,1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Export Validation Error: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        
        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $reportType = $request->input('report_type', 'Monthly Transaction Summary');
        $includeSummary = $request->input('include_summary', '1') === '1';
        $includeDetails = $request->input('include_details', '1') === '1';
        $includeFeedback = $request->input('include_feedback', '0') === '1';
        
        // Generate filename
        $dateRange = ($startDate && $endDate) ? $startDate . '_to_' . $endDate : date('Y-m-d');
        $filename = 'transactions_report_' . $dateRange . '.' . $format;
        
        // Get staff_id only if user is Staff model, otherwise null
        $staffId = null;
        $user = $request->user();
        if ($user && get_class($user) === 'App\Models\Staff') {
            $staffId = $user->id;
        }
        
        try {
            // Get all purposes from database for mapping
            $allPurposes = \App\Models\Purpose::pluck('name')->toArray();
            
            \Log::info('Export Report Started', [
                'format' => $format,
                'report_type' => $reportType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'include_summary' => $includeSummary,
                'include_details' => $includeDetails,
                'include_feedback' => $includeFeedback
            ]);
            
            // Get filter parameters
            $statusFilter = $request->input('status'); // e.g., 'pending', 'approved', 'completed'
            $purposeFilter = $request->input('purpose'); // e.g., 'Apply TES', 'ID Validation'
            
            // Build transactions query with PURE RELATIONSHIPS
            $query = Transaction::with(['user.masterlist', 'purpose']);
            
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            
            // Filter by status if provided
            if ($statusFilter && $statusFilter !== 'all') {
                $query->where('status', $statusFilter);
                \Log::info('Filtering by status: ' . $statusFilter);
            }
            
            // Filter by purpose if provided
            if ($purposeFilter && $purposeFilter !== 'all') {
                $query->whereHas('purpose', function($q) use ($purposeFilter) {
                    $q->where('name', $purposeFilter);
                });
                \Log::info('Filtering by purpose: ' . $purposeFilter);
            }
            
            // Filter by purpose using PURE RELATIONSHIP (purpose_id FK) for specific report types
            // Remove this block since we're no longer using report type for filtering
            // Report type is now generic "Transaction Report"
            
            $transactions = $query->orderBy('created_at', 'desc')->get();
            
            \Log::info('Transactions fetched', ['count' => $transactions->count()]);
            
            // Calculate statistics if summary is included
            $statistics = null;
            if ($includeSummary) {
                $statistics = $this->calculateStatistics($transactions);
                \Log::info('Statistics calculated', ['total' => $statistics['total']]);
            }
            
            // Get feedback data if feedback is included
            $feedbackData = null;
            if ($includeFeedback) {
                $feedbackData = $this->getFeedbackData($startDate, $endDate);
                \Log::info('Feedback data fetched', [
                    'total_feedback' => $feedbackData['total_feedback'],
                    'has_details' => isset($feedbackData['feedback_details'])
                ]);
            }
            
            // Generate file based on format
            $filePath = 'reports/' . $filename;
            $contentType = 'text/csv';
            
            \Log::info('Starting file generation', ['format' => $format]);
            
            // Ensure reports directory exists
            if (!Storage::disk('public')->exists('reports')) {
                Storage::disk('public')->makeDirectory('reports');
                \Log::info('Created reports directory');
            }
            
            if ($format === 'pdf') {
                \Log::info('Generating PDF report');
                $fileContent = $this->generatePDFReport($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
                $contentType = 'application/pdf';
                \Log::info('PDF generated successfully');
            } elseif ($format === 'excel') {
                \Log::info('Generating Excel report');
                $fileContent = $this->generateExcelReport($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $filename = str_replace('.excel', '.xlsx', $filename);
                $filePath = 'reports/' . $filename;
                \Log::info('Excel generated successfully');
            } else {
                \Log::info('Generating CSV report');
                $fileContent = $this->generateCSVReport($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
                \Log::info('CSV generated successfully');
            }
            
            // Store file
            try {
                Storage::disk('public')->put($filePath, $fileContent);
                \Log::info('File saved to storage', ['path' => $filePath]);
            } catch (\Exception $storageError) {
                \Log::error('Storage error: ' . $storageError->getMessage());
                // Continue anyway - we can still return the file
            }
            
            // Get file size
            $fileSize = 0;
            $fileSizeFormatted = '0 B';
            try {
                if (Storage::disk('public')->exists($filePath)) {
                    $fileSize = Storage::disk('public')->size($filePath);
                    $fileSizeFormatted = $this->formatBytes($fileSize);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not get file size: ' . $e->getMessage());
            }
            
            // Save to database with success status
            try {
                ExportedReport::create([
                    'staff_id' => $staffId,
                    'report_name' => $reportType . ' - ' . ($dateRange),
                    'report_type' => $reportType,
                    'file_format' => strtoupper($format === 'excel' ? 'XLSX' : $format),
                    'file_path' => $filePath,
                    'status' => 'success',
                    'error_message' => null,
                    'file_size' => $fileSizeFormatted,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'include_summary' => $includeSummary,
                    'include_details' => $includeDetails,
                    'include_feedback' => $includeFeedback,
                ]);
                \Log::info('Export record saved to database');
            } catch (\Exception $dbError) {
                \Log::error('Database save error: ' . $dbError->getMessage());
                // Continue anyway - file generation succeeded
            }
            
            // Clear any output buffers to prevent corruption
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Return file for download with proper binary response
            $headers = [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'public',
            ];
            
            \Log::info('Returning file', [
                'filename' => $filename,
                'content_type' => $contentType,
                'content_length' => strlen($fileContent)
            ]);
            
            return response($fileContent, 200, $headers);
            
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Export Report Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request data: ' . json_encode($request->all()));
            
            // Try to save failed export to database
            try {
                ExportedReport::create([
                    'staff_id' => $staffId ?? null,
                    'report_name' => ($reportType ?? 'Unknown') . ' - ' . ($dateRange ?? date('Y-m-d')),
                    'report_type' => $reportType ?? 'Unknown',
                    'file_format' => strtoupper($format === 'excel' ? 'XLSX' : ($format ?? 'CSV')),
                    'file_path' => $filePath ?? 'reports/unknown.csv',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'file_size' => '0 B',
                    'start_date' => $startDate ?? null,
                    'end_date' => $endDate ?? null,
                    'include_summary' => $includeSummary ?? false,
                    'include_details' => $includeDetails ?? false,
                    'include_feedback' => $includeFeedback ?? false,
                ]);
            } catch (\Exception $dbError) {
                \Log::error('Could not save failed export record: ' . $dbError->getMessage());
            }
            
            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Generate PDF report
     */
    private function generatePDFReport($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate)
    {
        try {
            \Log::info('generatePDFReport: Starting PDF generation');
            
            // Check if GD extension is available
            if (!extension_loaded('gd')) {
                \Log::error('GD extension not loaded');
                throw new \Exception('The PHP GD extension is required, but is not installed.');
            }
            
            \Log::info('GD extension check passed');
            
            // Prepare data for the view
            $data = [
                'reportTitle' => $reportType,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includeSummary' => $includeSummary,
                'includeDetails' => $includeDetails,
                'includeFeedback' => $includeFeedback,
                'statistics' => $statistics,
                'transactions' => $transactions,
                'feedbackData' => $feedbackData,
            ];
            
            \Log::info('PDF data prepared', ['transactions_count' => $transactions->count()]);
            
            // Generate dynamic services list based on actual transaction purposes in the data
            $purposeMapping = [
                'ID Validation' => 'Conducted student ID validation and assisted students with ID-related concerns.',
                'Good Moral' => 'Processed and released Certificates of Good Moral Character.',
                'Clearance' => 'Processed and released Student Clearance Forms.',
                'Affidavit of Loss' => 'Processed and released Affidavits of Loss for School IDs.',
                'Student Handbook' => 'Distributed and released Student Handbooks.',
                'Certificate Request' => 'Processed and issued various certifications and other requested student documents.',
                'Scholarship Application' => 'Received, checked, organized, and gathered documentary requirements for scholarship applications and grants.',
                'Document Request' => 'Assisted students in completing and complying with the necessary requirements for their scholarship applications and other student-related concerns.',
                // Add more mappings as needed
            ];
            
            // Get unique purposes from the actual filtered transactions
            $actualPurposes = $transactions->pluck('purpose.name')->filter()->unique()->sort()->values();
            
            // Build dynamic services list based on what's actually in the data
            $dynamicServicesList = [];
            foreach ($actualPurposes as $purposeName) {
                if (isset($purposeMapping[$purposeName])) {
                    $dynamicServicesList[] = $purposeMapping[$purposeName];
                } else {
                    // Generic fallback for unmapped purposes
                    $dynamicServicesList[] = "Processed and facilitated {$purposeName} transactions for students.";
                }
            }
            
            // If no purposes found, use a generic message
            if (empty($dynamicServicesList)) {
                $dynamicServicesList = ['No transactions were recorded for the selected period and filters.'];
            }
            
            $data['servicesList'] = $dynamicServicesList;
            
            // Dynamic intro and conclusion based on what services were actually provided
            if (count($actualPurposes) > 0) {
                $purposeList = $actualPurposes->take(3)->implode(', ');
                if (count($actualPurposes) > 3) {
                    $purposeList .= ', and other services';
                }
                
                $data['introText'] = "The Student Affairs and Services (SAS) Office continuously provided frontline services to students by facilitating various student-related documents, requests, and scholarship requirements. During this period, the office primarily handled {$purposeList}. These services aimed to ensure the efficient, timely, and organized delivery of assistance to students.";
                
                $data['conclusionText'] = 'Through these frontline services, the SAS Office was able to provide direct assistance to students, facilitate the processing and release of important documents, and ensure the proper collection and organization of requirements. The continuous delivery of these services contributed to the efficient and effective implementation of the office\'s programs and services for the student community.';
            } else {
                $data['introText'] = 'The Student Affairs and Services (SAS) Office continuously provides frontline services to students by facilitating various student-related documents, requests, and scholarship requirements.';
                $data['conclusionText'] = 'The SAS Office remains committed to providing efficient service delivery to the student community.';
            }
            
            // Add signature information
            $data['preparedBy'] = 'HAZEL V. FRANCISCO';
            $data['preparedByTitle'] = 'SAS Aide';
            $data['notedBy'] = 'PERLA S. MANLOLO, MAT';
            $data['notedByTitle'] = 'Head, Student Affairs and Services';
            
            // Generate PDF with options to handle potential issues
            \Log::info('About to call Pdf::loadView');
            
            try {
                $pdf = Pdf::loadView('reports.pdf_report', $data);
                \Log::info('Pdf::loadView successful, setting options');
                
                $pdf->setPaper('letter', 'portrait');
                $pdf->setOption('isHtml5ParserEnabled', true);
                $pdf->setOption('isRemoteEnabled', true);
                
                \Log::info('PDF options set, generating output');
                return $pdf->output();
            } catch (\Exception $pdfError) {
                \Log::error('Dompdf Error: ' . $pdfError->getMessage());
                \Log::error('Dompdf Stack: ' . $pdfError->getTraceAsString());
                
                // Try without options as fallback
                \Log::info('Trying fallback PDF generation without options');
                $pdf = Pdf::loadView('reports.pdf_report', $data);
                $pdf->setPaper('letter', 'portrait');
                
                \Log::info('Fallback PDF output');
                return $pdf->output();
            }
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return a simple error PDF or throw the exception
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Excel report with tables
     */
    private function generateExcelReport($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet name
        $sheet->setTitle('Transaction Report');
        
        $row = 1;
        
        // Header
        $sheet->mergeCells("A{$row}:J{$row}");
        $sheet->setCellValue("A{$row}", 'NORTHWEST SAMAR STATE UNIVERSITY');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        
        $sheet->mergeCells("A{$row}:J{$row}");
        $sheet->setCellValue("A{$row}", 'San Jorge Campus');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        
        $sheet->mergeCells("A{$row}:J{$row}");
        $sheet->setCellValue("A{$row}", 'Resilience • Integrity • Service • Excellence');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        
        $row++; // Empty row
        
        // Report Title
        $sheet->mergeCells("A{$row}:J{$row}");
        $sheet->setCellValue("A{$row}", $reportType);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        
        if ($startDate && $endDate) {
            $sheet->mergeCells("A{$row}:J{$row}");
            $sheet->setCellValue("A{$row}", 'Period: ' . date('F d, Y', strtotime($startDate)) . ' to ' . date('F d, Y', strtotime($endDate)));
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
        
        $sheet->mergeCells("A{$row}:J{$row}");
        $sheet->setCellValue("A{$row}", 'Generated: ' . date('F d, Y h:i A'));
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        
        $row++; // Empty row
        
        // Summary Section
        if ($includeSummary && $statistics) {
            $sheet->mergeCells("A{$row}:J{$row}");
            $sheet->setCellValue("A{$row}", 'SUMMARY (STATUS OVERVIEW)');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
            $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('FFFFFF');
            $row++;
            
            $row++; // Empty row
            
            $sheet->setCellValue("A{$row}", 'Total Transactions:');
            $sheet->setCellValue("B{$row}", number_format($statistics['total']));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
            
            $row++; // Empty row
            
            // Status table
            $sheet->setCellValue("A{$row}", 'Status');
            $sheet->setCellValue("B{$row}", 'Count');
            $sheet->setCellValue("C{$row}", 'Percentage');
            $headerStyle = $sheet->getStyle("A{$row}:C{$row}");
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
            $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
            $row++;
            
            foreach ($statistics['by_status'] as $status => $count) {
                $percentage = $statistics['total'] > 0 ? round(($count / $statistics['total']) * 100, 1) : 0;
                $sheet->setCellValue("A{$row}", ucfirst($status));
                $sheet->setCellValue("B{$row}", $count);
                $sheet->setCellValue("C{$row}", $percentage . '%');
                $row++;
            }
            
            $row++; // Empty row
            

        }
        
        // Detailed Transactions - ORGANIZED BY STATUS AND PURPOSE, SORTED BY DATE
        if ($includeDetails && $transactions->count() > 0) {
            $sheet->mergeCells("A{$row}:K{$row}");
            $sheet->setCellValue("A{$row}", 'DETAILED TRANSACTIONS (Organized by Status and Purpose, Sorted by Date)');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
            $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('FFFFFF');
            $row++;
            
            $row++; // Empty row
            
            // Group transactions by status first, then by purpose
            $groupedTransactions = $transactions->groupBy('status')->map(function($statusGroup) {
                return $statusGroup->groupBy(function($transaction) {
                    return $transaction->purpose ? $transaction->purpose->name : 'N/A';
                })->map(function($purposeGroup) {
                    // Sort by date (date_requested, then created_at as fallback) in descending order
                    return $purposeGroup->sortByDesc(function($transaction) {
                        return $transaction->date_requested ?? $transaction->created_at;
                    });
                });
            });
            
            // Define status order for better organization
            $statusOrder = ['pending', 'approved', 'completed', 'rejected', 'cancelled'];
            
            // Process each status group in order
            foreach ($statusOrder as $status) {
                if (!isset($groupedTransactions[$status])) continue;
                
                $purposeGroups = $groupedTransactions[$status];
                
                // Add status header
                $sheet->mergeCells("A{$row}:K{$row}");
                $sheet->setCellValue("A{$row}", 'STATUS: ' . strtoupper($status) . ' (' . $purposeGroups->flatten()->count() . ' transactions)');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                $row++;
                
                // Process each purpose within this status
                foreach ($purposeGroups as $purposeName => $transactionsInPurpose) {
                    // Add purpose subheader
                    $sheet->mergeCells("A{$row}:K{$row}");
                    $sheet->setCellValue("A{$row}", '  Purpose: ' . $purposeName . ' (' . $transactionsInPurpose->count() . ' transactions)');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setItalic(true);
                    $row++;
                    
                    // Headers for this section
                    $headers = ['Date Requested', 'Student ID', 'Student Name', 'Course', 'Year', 'Schedule', 'Time', 'Type', 'Status Updated'];
                    $col = 'B'; // Start from column B (leave A empty for indentation)
                    foreach ($headers as $header) {
                        $sheet->setCellValue("{$col}{$row}", $header);
                        $col++;
                    }
                    $headerStyle = $sheet->getStyle("B{$row}:J{$row}");
                    $headerStyle->getFont()->setBold(true)->setSize(9);
                    $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
                    $row++;
                    
                    // Data rows
                    foreach ($transactionsInPurpose as $transaction) {
                        // Use pure relationship to get student name from masterlist
                        $studentName = $this->formatStudentName($transaction->user->masterlist ?? null);
                        
                        $dateRequested = $transaction->date_requested 
                            ? $transaction->date_requested->format('Y-m-d') 
                            : $transaction->created_at->format('Y-m-d');
                        
                        $sheet->setCellValue("B{$row}", $dateRequested);
                        $sheet->setCellValue("C{$row}", $transaction->user->student_id ?? 'N/A');
                        $sheet->setCellValue("D{$row}", $studentName);
                        $sheet->setCellValue("E{$row}", $transaction->user->masterlist->course ?? 'N/A');
                        $sheet->setCellValue("F{$row}", $transaction->user->masterlist->year_level ?? 'N/A');
                        $sheet->setCellValue("G{$row}", $transaction->schedule_date ?? 'N/A');
                        $sheet->setCellValue("H{$row}", $transaction->time_slot ?? 'N/A');
                        $sheet->setCellValue("I{$row}", ucfirst($transaction->request_type ?? 'N/A'));
                        $sheet->setCellValue("J{$row}", $transaction->updated_at->format('Y-m-d H:i'));
                        $row++;
                    }
                    
                    $row++; // Empty row after each purpose group
                }
                
                $row++; // Empty row after each status group
            }
        }
        
        // Feedback Summary
        if ($includeFeedback && $feedbackData) {
            $sheet->mergeCells("A{$row}:J{$row}");
            $sheet->setCellValue("A{$row}", 'FEEDBACK SUMMARY');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
            $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('FFFFFF');
            $row++;
            
            $row++; // Empty row
            
            $sheet->setCellValue("A{$row}", 'Total Feedback Received:');
            $sheet->setCellValue("B{$row}", number_format($feedbackData['total_feedback']));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue("A{$row}", 'Average Rating:');
            $sheet->setCellValue("B{$row}", round($feedbackData['average_rating'], 2) . ' / 5.0');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
            
            $row++; // Empty row
            
            $sheet->setCellValue("A{$row}", 'RATING DISTRIBUTION');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue("A{$row}", 'Rating');
            $sheet->setCellValue("B{$row}", 'Count');
            $sheet->setCellValue("C{$row}", 'Percentage');
            $headerStyle = $sheet->getStyle("A{$row}:C{$row}");
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
            $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
            $row++;
            
            $totalFeedback = $feedbackData['total_feedback'];
            foreach ($feedbackData['rating_distribution'] as $rating => $count) {
                $percentage = $totalFeedback > 0 ? round(($count / $totalFeedback) * 100, 1) : 0;
                $sheet->setCellValue("A{$row}", $rating . ' stars');
                $sheet->setCellValue("B{$row}", $count);
                $sheet->setCellValue("C{$row}", $percentage . '%');
                $row++;
            }
            
            // Add detailed feedback if available
            if (isset($feedbackData['feedback_details']) && count($feedbackData['feedback_details']) > 0) {
                $row++; // Empty row
                $row++; // Empty row
                
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->setCellValue("A{$row}", 'DETAILED FEEDBACK (PER TRANSACTION)');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
                $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('FFFFFF');
                $row++;
                
                // Headers
                $feedbackHeaders = ['Student Name', 'Student ID', 'Transaction Purpose', 'Transaction Date', 'Rating', 'Feedback Message', 'Submitted Date'];
                $col = 'A';
                foreach ($feedbackHeaders as $header) {
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $col++;
                }
                $headerStyle = $sheet->getStyle("A{$row}:G{$row}");
                $headerStyle->getFont()->setBold(true);
                $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('15592F');
                $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
                $row++;
                
                // Data rows
                foreach ($feedbackData['feedback_details'] as $feedback) {
                    // Use pure relationship user.masterlist for student name with middle name hiding
                    $studentName = $this->formatStudentName($feedback->user->masterlist ?? null);
                    
                    // Get purpose name from relationship
                    $purpose = $feedback->purpose ? $feedback->purpose->name : 'N/A';
                    
                    $sheet->setCellValue("A{$row}", $studentName);
                    $sheet->setCellValue("B{$row}", $feedback->user->student_id ?? 'N/A');
                    $sheet->setCellValue("C{$row}", $purpose);
                    $sheet->setCellValue("D{$row}", date('M d, Y', strtotime($feedback->transaction_date ?? $feedback->created_at)));
                    $sheet->setCellValue("E{$row}", $feedback->rating . '/5');
                    $sheet->setCellValue("F{$row}", $feedback->message ?: 'No comment provided');
                    $sheet->setCellValue("G{$row}", date('M d, Y', strtotime($feedback->created_at)));
                    
                    // Wrap text for message column
                    $sheet->getStyle("F{$row}")->getAlignment()->setWrapText(true);
                    $row++;
                }
            }
        }
        
        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create Excel file
        try {
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            
            if ($tempFile === false) {
                throw new \Exception('Failed to create temporary file for Excel export');
            }
            
            \Log::info('Excel temp file created', ['path' => $tempFile]);
            
            $writer->save($tempFile);
            
            if (!file_exists($tempFile)) {
                throw new \Exception('Excel file was not created successfully');
            }
            
            $fileSize = filesize($tempFile);
            \Log::info('Excel file saved', ['size' => $fileSize]);
            
            if ($fileSize === 0) {
                throw new \Exception('Excel file is empty');
            }
            
            $content = file_get_contents($tempFile);
            unlink($tempFile);
            
            \Log::info('Excel content read', ['content_length' => strlen($content)]);
            
            return $content;
        } catch (\Exception $e) {
            \Log::error('Excel generation failed: ' . $e->getMessage());
            throw new \Exception('Failed to generate Excel file: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate CSV report content
     */
    private function generateCSVReport($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate)
    {
        $output = fopen('php://temp', 'r+');
        
        // Add report header
        fputcsv($output, ['NORTHWEST SAMAR STATE UNIVERSITY - SAN JORGE CAMPUS']);
        fputcsv($output, ['STUDENT AFFAIRS AND SERVICES']);
        fputcsv($output, [$reportType]);
        if ($startDate && $endDate) {
            fputcsv($output, ['Period: ' . date('F d, Y', strtotime($startDate)) . ' to ' . date('F d, Y', strtotime($endDate))]);
        }
        fputcsv($output, ['Generated: ' . date('F d, Y h:i A')]);
        fputcsv($output, []); // Empty line
        
        // Add summary section if requested
        if ($includeSummary && $statistics) {
            fputcsv($output, ['SUMMARY (STATUS OVERVIEW)']);
            fputcsv($output, []); // Empty line
            fputcsv($output, ['Total Transactions:', $statistics['total']]);
            fputcsv($output, []); // Empty line
            fputcsv($output, ['Status', 'Count', 'Percentage']);
            foreach ($statistics['by_status'] as $status => $count) {
                $percentage = $statistics['total'] > 0 ? round(($count / $statistics['total']) * 100, 1) : 0;
                fputcsv($output, [ucfirst($status), $count, $percentage . '%']);
            }
            fputcsv($output, []); // Empty line
        }
        
        // Add detailed transactions if requested - ORGANIZED BY STATUS AND PURPOSE, SORTED BY DATE
        if ($includeDetails && $transactions->count() > 0) {
            fputcsv($output, ['DETAILED TRANSACTIONS (Organized by Status and Purpose, Sorted by Date)']);
            fputcsv($output, []); // Empty line
            
            // Group transactions by status first, then by purpose
            $groupedTransactions = $transactions->groupBy('status')->map(function($statusGroup) {
                return $statusGroup->groupBy(function($transaction) {
                    return $transaction->purpose ? $transaction->purpose->name : 'N/A';
                })->map(function($purposeGroup) {
                    // Sort by date (date_requested, then created_at as fallback) in descending order
                    return $purposeGroup->sortByDesc(function($transaction) {
                        return $transaction->date_requested ?? $transaction->created_at;
                    });
                });
            });
            
            // Define status order for better organization
            $statusOrder = ['pending', 'approved', 'completed', 'rejected', 'cancelled'];
            
            // Process each status group in order
            foreach ($statusOrder as $status) {
                if (!isset($groupedTransactions[$status])) continue;
                
                $purposeGroups = $groupedTransactions[$status];
                
                // Add status header
                fputcsv($output, ['STATUS: ' . strtoupper($status) . ' (' . $purposeGroups->flatten()->count() . ' transactions)']);
                fputcsv($output, []); // Empty line
                
                // Process each purpose within this status
                foreach ($purposeGroups as $purposeName => $transactionsInPurpose) {
                    // Add purpose subheader
                    fputcsv($output, ['  Purpose: ' . $purposeName . ' (' . $transactionsInPurpose->count() . ' transactions)']);
                    
                    // Add CSV headers for this section
                    fputcsv($output, [
                        '',
                        'Date Requested',
                        'Student ID',
                        'Student Name',
                        'Course',
                        'Year Level',
                        'Address',
                        'Schedule Date',
                        'Time Slot',
                        'Release Date',
                        'Status Updated',
                        'Request Type'
                    ]);
                    
                    // Add data rows using PURE RELATIONSHIPS
                    foreach ($transactionsInPurpose as $transaction) {
                        // Get student name from user->masterlist relationship with middle name hiding
                        $studentName = $this->formatStudentName($transaction->user->masterlist ?? null);
                        
                        // Get address from user relationship
                        $address = 'N/A';
                        if ($transaction->user) {
                            $address = trim(
                                ($transaction->user->barangay ?? '') . ', ' . 
                                ($transaction->user->municipality ?? '') . ', ' . 
                                ($transaction->user->province ?? '') . ', ' .
                                ($transaction->user->region ?? '')
                            );
                            $address = trim($address, ', ');
                        }
                        
                        // Get course and year level from user->masterlist relationship
                        $course = $transaction->user && $transaction->user->masterlist 
                            ? ($transaction->user->masterlist->course ?? 'N/A') 
                            : 'N/A';
                        $yearLevel = $transaction->user && $transaction->user->masterlist 
                            ? ($transaction->user->masterlist->year_level ?? 'N/A') 
                            : 'N/A';
                        
                        $dateRequested = $transaction->date_requested 
                            ? $transaction->date_requested->format('Y-m-d') 
                            : $transaction->created_at->format('Y-m-d');
                        
                        // Format release_date (only for completed transactions)
                        $releaseDate = 'N/A';
                        if ($transaction->status === 'completed' && $transaction->release_date) {
                            $releaseDate = date('Y-m-d', strtotime($transaction->release_date));
                        }
                        
                        fputcsv($output, [
                            '',
                            $dateRequested,
                            $transaction->user ? $transaction->user->student_id : 'N/A',
                            $studentName,
                            $course,
                            $yearLevel,
                            $address,
                            $transaction->schedule_date,
                            $transaction->time_slot,
                            $releaseDate,
                            $transaction->updated_at->format('Y-m-d H:i:s'),
                            ucfirst($transaction->request_type ?? 'N/A')
                        ]);
                    }
                    fputcsv($output, []); // Empty line after each purpose group
                }
                fputcsv($output, []); // Empty line after each status group
            }
        }
        
        // Add feedback summary if requested
        if ($includeFeedback && $feedbackData) {
            fputcsv($output, ['FEEDBACK SUMMARY']);
            fputcsv($output, []); // Empty line
            fputcsv($output, ['Total Feedback Received:', $feedbackData['total_feedback']]);
            fputcsv($output, ['Average Rating:', round($feedbackData['average_rating'], 2) . ' / 5.0']);
            fputcsv($output, []); // Empty line
            
            fputcsv($output, ['Rating Distribution']);
            fputcsv($output, ['Rating', 'Count', 'Percentage']);
            $totalFeedback = $feedbackData['total_feedback'];
            foreach ($feedbackData['rating_distribution'] as $rating => $count) {
                $percentage = $totalFeedback > 0 ? round(($count / $totalFeedback) * 100, 1) : 0;
                fputcsv($output, [$rating . ' stars', $count, $percentage . '%']);
            }
            fputcsv($output, []); // Empty line
            
            // Add detailed feedback if available
            if (isset($feedbackData['feedback_details']) && count($feedbackData['feedback_details']) > 0) {
                fputcsv($output, ['DETAILED FEEDBACK (PER TRANSACTION)']);
                fputcsv($output, []); // Empty line
                fputcsv($output, ['Student Name', 'Student ID', 'Transaction Purpose', 'Transaction Date', 'Rating', 'Feedback Message', 'Submitted Date']);
                
                foreach ($feedbackData['feedback_details'] as $feedback) {
                    // Use pure relationship user.masterlist for student name with middle name hiding
                    $studentName = $this->formatStudentName($feedback->user->masterlist ?? null);
                    
                    // Get purpose name from relationship
                    $purpose = $feedback->purpose ? $feedback->purpose->name : 'N/A';
                    
                    fputcsv($output, [
                        $studentName,
                        $feedback->user->student_id ?? 'N/A',
                        $purpose,
                        date('M d, Y', strtotime($feedback->transaction_date ?? $feedback->created_at)),
                        $feedback->rating . '/5',
                        $feedback->message ?: 'No comment provided',
                        date('M d, Y', strtotime($feedback->created_at))
                    ]);
                }
                fputcsv($output, []); // Empty line
            }
        }
        
        // If nothing was selected, show a message
        if (!$includeSummary && !$includeDetails && !$includeFeedback) {
            fputcsv($output, ['No report sections selected.']);
            fputcsv($output, ['Please select at least one section to include in the report.']);
        }
        
        fputcsv($output, []); // Empty line
        fputcsv($output, ['--- End of Report ---']);
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Calculate statistics for the report
     */
    private function calculateStatistics($transactions)
    {
        $total = $transactions->count();
        
        $statusCounts = [
            'pending' => $transactions->where('status', 'pending')->count(),
            'approved' => $transactions->where('status', 'approved')->count(),
            'completed' => $transactions->where('status', 'completed')->count(),
            'rejected' => $transactions->where('status', 'rejected')->count(),
            'cancelled' => $transactions->where('status', 'cancelled')->count(),
        ];
        
        // Group by purpose using PURE RELATIONSHIP
        $purposeCounts = $transactions->groupBy(function($transaction) {
            return $transaction->purpose ? $transaction->purpose->name : 'Unknown';
        })->map(function($group) {
            return $group->count();
        })->sortDesc()->take(5);
        
        return [
            'total' => $total,
            'by_status' => $statusCounts,
            'by_purpose' => $purposeCounts,
        ];
    }
    
    /**
     * Format student name properly - hide middle name if it's NA variations
     */
    private function formatStudentName($masterlist)
    {
        if (!$masterlist) {
            return 'N/A';
        }
        
        $fname = $masterlist->fname ?? '';
        $mname = $masterlist->mname ?? '';
        $lname = $masterlist->lname ?? '';
        
        // Check if middle name should be hidden (NA, N.A, N A, N.A., etc.)
        $hideMiddle = empty($mname) || preg_match('/^N\.?\s*A\.?$/i', trim($mname));
        
        if ($hideMiddle) {
            return trim("{$fname} {$lname}");
        }
        
        return trim("{$fname} {$mname} {$lname}");
    }

    /**
     * Get feedback data for the report - Pure Relationship
     */
    private function getFeedbackData($startDate, $endDate)
    {
        try {
            // Use pure relationships: user.masterlist for user data, purpose for purpose data
            $query = \App\Models\Feedback::with(['user.masterlist', 'purpose', 'transaction']);
            
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            
            $feedbacks = $query->get();
            
            return [
                'total_feedback' => $feedbacks->count(),
                'average_rating' => $feedbacks->count() > 0 ? round($feedbacks->avg('rating'), 2) : 0,
                'rating_distribution' => [
                    '5' => $feedbacks->where('rating', 5)->count(),
                    '4' => $feedbacks->where('rating', 4)->count(),
                    '3' => $feedbacks->where('rating', 3)->count(),
                    '2' => $feedbacks->where('rating', 2)->count(),
                    '1' => $feedbacks->where('rating', 1)->count(),
                ],
                'feedback_details' => $feedbacks
            ];
        } catch (\Exception $e) {
            \Log::error('Error fetching feedback data for report: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            // Return empty data structure to prevent breaking the report
            return [
                'total_feedback' => 0,
                'average_rating' => 0,
                'rating_distribution' => [
                    '5' => 0,
                    '4' => 0,
                    '3' => 0,
                    '2' => 0,
                    '1' => 0,
                ],
                'feedback_details' => collect([])
            ];
        }
    }
    
    /**
     * Export transactions to CSV
     */
    private function exportToCsv($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate)
    {
        $dateRange = ($startDate && $endDate) ? $startDate . '_to_' . $endDate : date('Y-m-d');
        $filename = 'transactions_report_' . $dateRange . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Add report header
            fputcsv($file, ['NORTHWEST SAMAR STATE UNIVERSITY - SAN JORGE CAMPUS']);
            fputcsv($file, ['STUDENT AFFAIRS AND SERVICES']);
            fputcsv($file, [$reportType]);
            if ($startDate && $endDate) {
                fputcsv($file, ['Period: ' . date('F d, Y', strtotime($startDate)) . ' to ' . date('F d, Y', strtotime($endDate))]);
            }
            fputcsv($file, ['Generated: ' . date('F d, Y h:i A')]);
            fputcsv($file, []); // Empty line
            
            // Add summary section if requested
            if ($includeSummary && $statistics) {
                fputcsv($file, ['SUMMARY (STATUS OVERVIEW)']);
                fputcsv($file, []); // Empty line
                fputcsv($file, ['Total Transactions:', $statistics['total']]);
                fputcsv($file, []); // Empty line
                fputcsv($file, ['Status', 'Count', 'Percentage']);
                foreach ($statistics['by_status'] as $status => $count) {
                    $percentage = $statistics['total'] > 0 ? round(($count / $statistics['total']) * 100, 1) : 0;
                    fputcsv($file, [ucfirst($status), $count, $percentage . '%']);
                }
                fputcsv($file, []); // Empty line
                fputcsv($file, []); // Empty line
            }
            
            // Add detailed transactions if requested
            if ($includeDetails && $transactions->count() > 0) {
                fputcsv($file, ['DETAILED TRANSACTIONS']);
                fputcsv($file, []); // Empty line
                
                // Add CSV headers
                fputcsv($file, [
                    'Date',
                    'Student ID',
                    'Student Name',
                    'Course',
                    'Year Level',
                    'Purpose',
                    'Address',
                    'Schedule Date',
                    'Time Slot',
                    'Status',
                    'Created At'
                ]);
                
                // Add data rows
                foreach ($transactions as $transaction) {
                    // Use pure relationship to get student name from masterlist
                    $studentName = $this->formatStudentName($transaction->user->masterlist ?? null);
                    
                    // Get address from user relationship
                    $address = $transaction->user 
                        ? trim(($transaction->user->barangay ?? '') . ', ' . 
                              ($transaction->user->municipality ?? '') . ', ' . 
                              ($transaction->user->province ?? ''))
                        : 'N/A';
                    
                    fputcsv($file, [
                        $transaction->created_at->format('Y-m-d'),
                        $transaction->user->student_id ?? 'N/A',
                        $studentName,
                        $transaction->user->masterlist->course ?? 'N/A',
                        $transaction->user->masterlist->year_level ?? 'N/A',
                        $transaction->purpose->name ?? 'N/A',
                        $address,
                        $transaction->schedule_date,
                        $transaction->time_slot,
                        ucfirst($transaction->status),
                        $transaction->created_at->format('Y-m-d H:i:s')
                    ]);
                }
                fputcsv($file, []); // Empty line
            }
            
            // Add feedback summary if requested
            if ($includeFeedback && $feedbackData) {
                fputcsv($file, ['FEEDBACK SUMMARY']);
                fputcsv($file, []); // Empty line
                fputcsv($file, ['Total Feedback Received:', $feedbackData['total_feedback']]);
                fputcsv($file, ['Average Rating:', round($feedbackData['average_rating'], 2) . ' / 5.0']);
                fputcsv($file, []); // Empty line
                
                fputcsv($file, ['Rating Distribution']);
                fputcsv($file, ['Rating', 'Count', 'Percentage']);
                $totalFeedback = $feedbackData['total_feedback'];
                foreach ($feedbackData['rating_distribution'] as $rating => $count) {
                    $percentage = $totalFeedback > 0 ? round(($count / $totalFeedback) * 100, 1) : 0;
                    fputcsv($file, [$rating . ' stars', $count, $percentage . '%']);
                }
                fputcsv($file, []); // Empty line
                
                // Add detailed feedback list
                if (isset($feedbackData['feedback_details']) && count($feedbackData['feedback_details']) > 0) {
                    fputcsv($file, ['DETAILED FEEDBACK (PER TRANSACTION)']);
                    fputcsv($file, []); // Empty line
                    fputcsv($file, ['Student Name', 'Student ID', 'Transaction Purpose', 'Transaction Date', 'Rating', 'Feedback Message', 'Submitted Date']);
                    
                    foreach ($feedbackData['feedback_details'] as $feedback) {
                        $studentName = trim(($feedback->user->fname ?? '') . ' ' . ($feedback->user->mname ?? '') . ' ' . ($feedback->user->lname ?? '')) ?: 'N/A';
                        $purpose = $feedback->transaction_purpose ?? 'N/A';
                        
                        fputcsv($file, [
                            $studentName,
                            $feedback->user->student_id ?? 'N/A',
                            $purpose,
                            date('M d, Y', strtotime($feedback->transaction_date ?? $feedback->created_at)),
                            $feedback->rating . '/5',
                            $feedback->message ?: 'No comment provided',
                            date('M d, Y', strtotime($feedback->created_at))
                        ]);
                    }
                    fputcsv($file, []); // Empty line
                }
            }
            
            // If nothing was selected, show a message
            if (!$includeSummary && !$includeDetails && !$includeFeedback) {
                fputcsv($file, ['No report sections selected.']);
                fputcsv($file, ['Please select at least one section to include in the report.']);
            }
            
            fputcsv($file, []); // Empty line
            fputcsv($file, ['--- End of Report ---']);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get recent generated reports from database
     */
    public function getRecentReports()
    {
        $reports = ExportedReport::with('staff:id,fname,lname')
            ->where('status', 'success') // Only show successful exports
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->report_name,
                    'date' => $report->created_at->format('F j, Y'),
                    'format' => $report->file_format,
                    'size' => $report->file_size,
                    'download_url' => '/reports/download/' . $report->id,
                    'generated_by' => $report->staff 
                        ? trim($report->staff->fname . ' ' . $report->staff->lname)
                        : 'System Administrator'
                ];
            });
        
        return response()->json([
            'reports' => $reports
        ]);
    }
    
    /**
     * Download a specific exported report
     */
    public function downloadReport($id)
    {
        $report = ExportedReport::findOrFail($id);
        
        // Check if export was successful
        if ($report->status !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'This report export failed and cannot be downloaded',
                'error' => $report->error_message
            ], 400);
        }
        
        if (!Storage::disk('public')->exists($report->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Report file not found'
            ], 404);
        }
        
        $fileContent = Storage::disk('public')->get($report->file_path);
        
        // Get the original filename from the file_path
        $originalFilename = basename($report->file_path);
        
        // Determine content type and correct file extension based on the stored file_format field
        $format = strtolower($report->file_format);
        $contentType = 'text/csv';
        $correctExtension = 'csv';
        
        if ($format === 'pdf') {
            $contentType = 'application/pdf';
            $correctExtension = 'pdf';
        } elseif (in_array($format, ['xlsx', 'excel', 'xls'])) {
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $correctExtension = 'xlsx';
        } elseif ($format === 'csv') {
            $contentType = 'text/csv';
            $correctExtension = 'csv';
        }
        
        // Get filename without extension and rebuild with correct extension
        $filenameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
        $downloadFilename = $filenameWithoutExt . '.' . $correctExtension;
        
        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $downloadFilename . '"',
        ];
        
        return response()->make($fileContent, 200, $headers);
    }
    
    /**
     * Clear all exported reports
     */
    public function clearAllReports()
    {
        try {
            // Get all reports
            $reports = ExportedReport::all();
            
            // Delete files from storage
            foreach ($reports as $report) {
                if (Storage::disk('public')->exists($report->file_path)) {
                    Storage::disk('public')->delete($report->file_path);
                }
            }
            
            // Delete database records
            ExportedReport::truncate();
            
            return response()->json([
                'success' => true,
                'message' => 'All reports cleared successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error clearing reports: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear reports'
            ], 500);
        }
    }
}
