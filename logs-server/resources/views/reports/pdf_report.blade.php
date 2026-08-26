<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #15592F;
        }
        
        .university-name {
            font-size: 16px;
            font-weight: bold;
            color: #15592F;
            text-transform: uppercase;
            margin: 5px 0;
        }
        
        .campus-name {
            font-size: 10px;
            color: #666;
            margin: 3px 0;
        }
        
        .report-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #15592F;
        }
        
        .report-subtitle {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .period-info {
            text-align: center;
            font-size: 10px;
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #15592F;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        
        .content-text {
            text-align: justify;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .services-list {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .services-list li {
            margin-bottom: 6px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9px;
        }
        
        table thead th {
            background-color: #15592F;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #15592F;
        }
        
        table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .summary-table {
            width: 60%;
            margin: 15px auto;
        }
        
        .summary-table td {
            padding: 8px;
        }
        
        .summary-table .label {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .summary-table .value {
            text-align: right;
            font-weight: bold;
            color: #15592F;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-cancelled {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .footer-section {
            margin-top: 40px;
        }
        
        .signature-block {
            margin-top: 30px;
        }
        
        .signature-line {
            margin-top: 40px;
        }
        
        .signature-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .signature-title {
            font-size: 9px;
            color: #666;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="university-name">NORTHWEST SAMAR STATE UNIVERSITY</div>
        <div class="campus-name">San Jorge Campus</div>
        <div class="campus-name">Resilience • Integrity • Service • Excellence</div>
    </div>
    
    <!-- Report Title -->
    <div class="report-title">{{ $reportTitle }}</div>
    
    @if(isset($startDate) && isset($endDate))
    <div class="period-info">
        <strong>Period:</strong> {{ date('F d, Y', strtotime($startDate)) }} to {{ date('F d, Y', strtotime($endDate)) }}
    </div>
    @endif
    
    <div class="report-subtitle">
        Generated: {{ $generatedAt ?? date('F d, Y h:i A') }}
    </div>
    
    <!-- Introduction Text -->
    @if(isset($introText))
    <div class="content-text">
        {{ $introText }}
    </div>
    @endif
    
    <!-- Services List -->
    @if(isset($servicesList) && is_array($servicesList))
    <div class="content-text">
        The following frontline services were provided:
    </div>
    <ul class="services-list">
        @foreach($servicesList as $service)
        <li>{{ $service }}</li>
        @endforeach
    </ul>
    @endif
    
    <!-- Summary Section -->
    @if(isset($includeSummary) && $includeSummary && isset($statistics))
    <div class="section-title">SUMMARY (STATUS OVERVIEW)</div>
    
    <table class="summary-table">
        <tr>
            <td class="label">Total Transactions:</td>
            <td class="value">{{ number_format($statistics['total']) }}</td>
        </tr>
    </table>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Status</th>
                <th style="width: 25%; text-align: center;">Count</th>
                <th style="width: 25%; text-align: center;">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistics['by_status'] as $status => $count)
            <tr>
                <td>
                    <span class="status-badge status-{{ $status }}">{{ ucfirst($status) }}</span>
                </td>
                <td style="text-align: center;">{{ number_format($count) }}</td>
                <td style="text-align: center;">
                    {{ $statistics['total'] > 0 ? round(($count / $statistics['total']) * 100, 1) : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    @if(isset($statistics['by_purpose']) && count($statistics['by_purpose']) > 0)
    <div class="section-title">TOP REQUESTED PURPOSES</div>
    <table>
        <thead>
            <tr>
                <th style="width: 70%;">Purpose</th>
                <th style="width: 30%; text-align: center;">Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistics['by_purpose'] as $purpose => $count)
            <tr>
                <td>{{ $purpose }}</td>
                <td style="text-align: center;">{{ number_format($count) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @endif
    
    <!-- Detailed Transactions -->
    @if(isset($includeDetails) && $includeDetails && isset($transactions) && count($transactions) > 0)
    <div class="page-break"></div>
    <div class="section-title">DETAILED TRANSACTIONS</div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Purpose</th>
                <th>Schedule</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ date('Y-m-d', strtotime($transaction->created_at)) }}</td>
                <td>{{ $transaction->user->student_id ?? 'N/A' }}</td>
                <td>{{ trim(($transaction->user->fname ?? '') . ' ' . ($transaction->user->lname ?? '')) ?: 'N/A' }}</td>
                <td>{{ $transaction->user->course ?? 'N/A' }}</td>
                <td>{{ $transaction->user->year_level ?? 'N/A' }}</td>
                <td>{{ $transaction->purpose }}</td>
                <td>{{ $transaction->schedule_date ?? 'N/A' }}</td>
                <td>
                    <span class="status-badge status-{{ $transaction->status }}">
                        {{ ucfirst($transaction->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    <!-- Feedback Summary -->
    @if(isset($includeFeedback) && $includeFeedback && isset($feedbackData))
    <div class="page-break"></div>
    <div class="section-title">FEEDBACK SUMMARY</div>
    
    <table class="summary-table">
        <tr>
            <td class="label">Total Feedback Received:</td>
            <td class="value">{{ number_format($feedbackData['total_feedback']) }}</td>
        </tr>
        <tr>
            <td class="label">Average Rating:</td>
            <td class="value">{{ number_format($feedbackData['average_rating'], 2) }} / 5.0</td>
        </tr>
    </table>
    
    <div class="section-title">RATING DISTRIBUTION</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Rating</th>
                <th style="width: 50%; text-align: center;">Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($feedbackData['rating_distribution'] as $rating => $count)
            <tr>
                <td>{{ $rating }} stars</td>
                <td style="text-align: center;">{{ number_format($count) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    <!-- Conclusion Text -->
    @if(isset($conclusionText))
    <div class="content-text" style="margin-top: 20px;">
        {{ $conclusionText }}
    </div>
    @endif
    
    <!-- Signatures -->
    <div class="footer-section">
        <div class="signature-block">
            <div style="margin-bottom: 5px;">Prepared by:</div>
            <div class="signature-line">
                <div class="signature-name">{{ $preparedBy ?? 'SYSTEM ADMINISTRATOR' }}</div>
                <div class="signature-title">{{ $preparedByTitle ?? 'SAS Office' }}</div>
            </div>
        </div>
        
        <div class="signature-block" style="text-align: right;">
            <div style="margin-bottom: 5px;">Noted by:</div>
            <div class="signature-line">
                <div class="signature-name">{{ $notedBy ?? 'PERLA S. MANLOLO, MAT' }}</div>
                <div class="signature-title">{{ $notedByTitle ?? 'Head, Student Affairs and Services' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
