<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function getMonthlyTrends()
    {
        $monthlyData = Transaction::select(
                DB::raw('DATE_FORMAT(created_at, "%b") as month'),
                DB::raw('count(*) as value')
            )
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get();
        
        return response()->json([
            'data' => $monthlyData
        ]);
    }
    
    /**
     * Export report to CSV
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
        
        // Export based on format
        if ($format === 'csv') {
            return $this->exportToCsv($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
        } elseif ($format === 'excel') {
            return $this->exportToCsv($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
        } elseif ($format === 'pdf') {
            return $this->exportToCsv($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
        }
        
        return $this->exportToCsv($transactions, $statistics, $feedbackData, $includeSummary, $includeDetails, $includeFeedback, $reportType, $startDate, $endDate);
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
     * Get recent generated reports (mock data for now)
     */
    public function getRecentReports()
    {
        // In a real application, you would store generated reports in a database
        // For now, we'll return mock data
        $reports = [
            [
                'name' => 'Monthly Transaction Summary - ' . Carbon::now()->format('F'),
                'date' => Carbon::now()->subDays(1)->format('F j, Y'),
                'format' => 'CSV',
                'size' => '1.2 MB',
                'download_url' => '/reports/export?format=csv'
            ],
            [
                'name' => 'Service Request Analytics',
                'date' => Carbon::now()->subDays(3)->format('F j, Y'),
                'format' => 'CSV',
                'size' => '980 KB',
                'download_url' => '/reports/export?format=csv'
            ],
            [
                'name' => 'Complete Transaction Report',
                'date' => Carbon::now()->subDays(6)->format('F j, Y'),
                'format' => 'CSV',
                'size' => '2.4 MB',
                'download_url' => '/reports/export?format=csv'
            ],
        ];
        
        return response()->json([
            'reports' => $reports
        ]);
    }
}
