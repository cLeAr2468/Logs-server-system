<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\ExportedReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get dashboard statistics for reports
     */
    public function getStatistics()
    {
        // Total transactions
        $totalTransactions = Transaction::count();
        
        // Calculate monthly target (assume 6500 as target)
        $monthlyTarget = 6500;
        $targetPercentage = ($totalTransactions / $monthlyTarget) * 100;
        
        // Average processing time (mock for now - in reality calculate from timestamps)
        $avgProcessingTime = '12.5 min';
        
        // Most requested purpose
        $mostRequested = Transaction::select('purpose', DB::raw('count(*) as count'))
            ->groupBy('purpose')
            ->orderBy('count', 'desc')
            ->first();
        
        // Completion rate
        $completedCount = Transaction::where('status', 'completed')->count();
        $completionRate = $totalTransactions > 0 
            ? round(($completedCount / $totalTransactions) * 100, 1) 
            : 0;
        
        return response()->json([
            'statistics' => [
                'total_transactions' => $totalTransactions,
                'target_percentage' => round($targetPercentage, 1),
                'avg_processing_time' => $avgProcessingTime,
                'most_requested' => [
                    'purpose' => $mostRequested->purpose ?? 'N/A',
                    'count' => $mostRequested->count ?? 0
                ],
                'completion_rate' => $completionRate
            ]
        ]);
    }
    
    /**
     * Get transactions by purpose (for bar chart)
     */
    public function getTransactionsByPurpose()
    {
        $purposeData = Transaction::select('purpose', DB::raw('count(*) as value'))
            ->groupBy('purpose')
            ->orderBy('value', 'desc')
            ->get()
            ->map(function ($item, $index) {
                $colors = ['#15592F', '#f59e0b', '#3b82f6', '#155d59', '#8b5cf6', '#ef4444'];
                return [
                    'name' => $item->purpose,
                    'value' => $item->value,
                    'fill' => $colors[$index % count($colors)]
                ];
            });
        
        return response()->json([
            'data' => $purposeData
        ]);
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
        $request->validate([
            'format' => 'sometimes|in:csv,excel,pdf',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'report_type' => 'sometimes|string',
            'include_summary' => 'sometimes|in:0,1',
            'include_details' => 'sometimes|in:0,1',
            'include_feedback' => 'sometimes|in:0,1',
        ]);
        
        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $reportType = $request->input('report_type', 'Monthly Transaction Summary');
        $includeSummary = $request->input('include_summary', '1') === '1';
        $includeDetails = $request->input('include_details', '1') === '1';
        $includeFeedback = $request->input('include_feedback', '0') === '1';
        
        // Build transactions query
        $query = Transaction::with('user');
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        // Calculate statistics if summary is included
        $statistics = null;
        if ($includeSummary) {
            $statistics = $this->calculateStatistics($transactions);
        }
        
        // Get feedback data if feedback is included
        $feedbackData = null;
        if ($includeFeedback) {
            $feedbackData = $this->getFeedbackData($startDate, $endDate);
        }
        
        // Generate filename
        $dateRange = ($startDate && $endDate) ? $startDate . '_to_' . $endDate : date('Y-m-d');
        $filename = 'transactions_report_' . $dateRange . '.' . $format;
        
        // Save report to database
        $staffId = $request->user() ? $request->user()->id : null;
        
        // Generate file content
        $fileContent = $this->generateReportContent($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
        
        // Store file
        $filePath = 'reports/' . $filename;
        Storage::disk('public')->put($filePath, $fileContent);
        
        // Get file size
        $fileSize = Storage::disk('public')->size($filePath);
        $fileSizeFormatted = $this->formatBytes($fileSize);
        
        // Save to database
        ExportedReport::create([
            'staff_id' => $staffId,
            'report_name' => $reportType . ' - ' . ($dateRange),
            'report_type' => $reportType,
            'file_format' => strtoupper($format),
            'file_path' => $filePath,
            'file_size' => $fileSizeFormatted,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'include_summary' => $includeSummary,
            'include_details' => $includeDetails,
            'include_feedback' => $includeFeedback,
        ]);
        
        // Return file for download
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response()->make($fileContent, 200, $headers);
    }
    
    /**
     * Generate report content as CSV
     */
    private function generateReportContent($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate)
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
            
            fputcsv($output, ['Top Requested Purposes']);
            fputcsv($output, ['Purpose', 'Count']);
            foreach ($statistics['by_purpose'] as $purpose => $count) {
                fputcsv($output, [$purpose, $count]);
            }
            fputcsv($output, []); // Empty line
            fputcsv($output, []); // Empty line
        }
        
        // Add detailed transactions if requested
        if ($includeDetails && $transactions->count() > 0) {
            fputcsv($output, ['DETAILED TRANSACTIONS']);
            fputcsv($output, []); // Empty line
            
            // Add CSV headers
            fputcsv($output, [
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
                $studentName = $transaction->user 
                    ? trim($transaction->user->fname . ' ' . $transaction->user->mname . ' ' . $transaction->user->lname)
                    : 'N/A';
                
                $address = trim($transaction->street_house_no . ', ' . 
                              $transaction->brgy . ', ' . 
                              $transaction->municipality . ', ' . 
                              $transaction->province);
                
                fputcsv($output, [
                    $transaction->created_at->format('Y-m-d'),
                    $transaction->user->student_id ?? 'N/A',
                    $studentName,
                    $transaction->user->course ?? 'N/A',
                    $transaction->user->year_level ?? 'N/A',
                    $transaction->purpose,
                    $address,
                    $transaction->schedule_date,
                    $transaction->time_slot,
                    ucfirst($transaction->status),
                    $transaction->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fputcsv($output, []); // Empty line
        }
        
        // Add feedback summary if requested
        if ($includeFeedback && $feedbackData) {
            fputcsv($output, ['FEEDBACK SUMMARY']);
            fputcsv($output, []); // Empty line
            fputcsv($output, ['Total Feedback Received:', $feedbackData['total_feedback']]);
            fputcsv($output, ['Average Rating:', round($feedbackData['average_rating'], 2) . ' / 5.0']);
            fputcsv($output, []); // Empty line
            
            fputcsv($output, ['Rating Distribution']);
            fputcsv($output, ['Rating', 'Count']);
            foreach ($feedbackData['rating_distribution'] as $rating => $count) {
                fputcsv($output, [$rating . ' stars', $count]);
            }
            fputcsv($output, []); // Empty line
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
        
        $purposeCounts = $transactions->groupBy('purpose')->map(function($group) {
            return $group->count();
        })->sortDesc()->take(5);
        
        return [
            'total' => $total,
            'by_status' => $statusCounts,
            'by_purpose' => $purposeCounts,
        ];
    }
    
    /**
     * Get feedback data for the report
     */
    private function getFeedbackData($startDate, $endDate)
    {
        $query = \App\Models\Feedback::query();
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $feedbacks = $query->get();
        
        return [
            'total_feedback' => $feedbacks->count(),
            'average_rating' => $feedbacks->avg('rating') ?? 0,
            'rating_distribution' => [
                '5' => $feedbacks->where('rating', 5)->count(),
                '4' => $feedbacks->where('rating', 4)->count(),
                '3' => $feedbacks->where('rating', 3)->count(),
                '2' => $feedbacks->where('rating', 2)->count(),
                '1' => $feedbacks->where('rating', 1)->count(),
            ]
        ];
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
                
                fputcsv($file, ['Top Requested Purposes']);
                fputcsv($file, ['Purpose', 'Count']);
                foreach ($statistics['by_purpose'] as $purpose => $count) {
                    fputcsv($file, [$purpose, $count]);
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
                    $studentName = $transaction->user 
                        ? trim($transaction->user->fname . ' ' . $transaction->user->mname . ' ' . $transaction->user->lname)
                        : 'N/A';
                    
                    $address = trim($transaction->street_house_no . ', ' . 
                                  $transaction->brgy . ', ' . 
                                  $transaction->municipality . ', ' . 
                                  $transaction->province);
                    
                    fputcsv($file, [
                        $transaction->created_at->format('Y-m-d'),
                        $transaction->user->student_id ?? 'N/A',
                        $studentName,
                        $transaction->user->course ?? 'N/A',
                        $transaction->user->year_level ?? 'N/A',
                        $transaction->purpose,
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
                fputcsv($file, ['Rating', 'Count']);
                foreach ($feedbackData['rating_distribution'] as $rating => $count) {
                    fputcsv($file, [$rating . ' stars', $count]);
                }
                fputcsv($file, []); // Empty line
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
        
        if (!Storage::disk('public')->exists($report->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Report file not found'
            ], 404);
        }
        
        $fileContent = Storage::disk('public')->get($report->file_path);
        $filename = basename($report->file_path);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response()->make($fileContent, 200, $headers);
    }
}
