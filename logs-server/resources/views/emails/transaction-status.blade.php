<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Status Update</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #15592F 0%, #1a6e3a 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #15592F;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin: 15px 0;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .appointment-details {
            background-color: #f8f9fa;
            border-left: 4px solid #15592F;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-row {
            margin: 10px 0;
            display: flex;
            gap: 10px;
        }
        .detail-label {
            font-weight: 600;
            color: #15592F;
            min-width: 120px;
        }
        .detail-value {
            color: #555;
        }
        .message-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box.success {
            background-color: #d4edda;
            border-left-color: #28a745;
        }
        .message-box.error {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔔 Appointment Status Update</h1>
        </div>

        <div class="email-body">
            <p class="greeting">Dear {{ $studentName }},</p>

            @if($status === 'approved')
                <div class="message-box success">
                    <p style="margin: 0;">
                        <strong>Good news!</strong> Your appointment has been approved by the administrator.
                    </p>
                </div>
                <p>Your appointment request has been reviewed and <strong style="color: #28a745;">APPROVED</strong>.</p>
                <p>Please make sure to arrive on time for your scheduled appointment.</p>
            @elseif($status === 'rejected')
                <div class="message-box error">
                    <p style="margin: 0;">
                        <strong>Notice:</strong> Your appointment has been rejected by the administrator.
                    </p>
                </div>
                <p>We regret to inform you that your appointment request has been <strong style="color: #dc3545;">REJECTED</strong>.</p>
                <p>If you have any questions or would like to reschedule, please contact the Student Affairs and Services office.</p>
            @elseif($status === 'completed')
                <div class="message-box success">
                    <p style="margin: 0;">
                        <strong>Thank you!</strong> Your appointment has been completed.
                    </p>
                </div>
                <p>Your appointment has been marked as <strong style="color: #0c5460;">COMPLETED</strong>.</p>
                <p>We hope you had a positive experience with our service. If you need any further assistance, feel free to create a new appointment.</p>
            @endif

            <div class="appointment-details">
                <h3 style="margin-top: 0; color: #15592F;">📋 Appointment Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Purpose:</span>
                    <span class="detail-value">{{ $transaction->purpose }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Schedule Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($transaction->schedule_date)->format('F d, Y') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Time Slot:</span>
                    <span class="detail-value">{{ $transaction->time_slot }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">{{ $transaction->street_house_no }}, {{ $transaction->brgy }}, {{ $transaction->municipality }}, {{ $transaction->province }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-{{ $status }}">
                            {{ strtoupper($status) }}
                        </span>
                    </span>
                </div>
            </div>

            @if($status === 'approved')
                <p style="margin-top: 25px;">
                    <strong>What's next?</strong>
                </p>
                <ul style="color: #555;">
                    <li>Keep this appointment date and time in mind</li>
                    <li>Prepare any necessary documents</li>
                    <li>Arrive at least 10 minutes before your scheduled time</li>
                    <li>Check your appointments dashboard for any updates</li>
                </ul>
            @elseif($status === 'rejected')
                <p style="margin-top: 25px;">
                    <strong>Need help?</strong>
                </p>
                <p style="color: #555;">
                    If you believe this was a mistake or need clarification, please contact the Student Affairs and Services office or create a new appointment with updated information.
                </p>
            @elseif($status === 'completed')
                <p style="margin-top: 25px;">
                    <strong>We value your feedback!</strong>
                </p>
                <p style="color: #555;">
                    Please consider sharing your experience by submitting feedback through your student dashboard.
                </p>
            @endif

        </div>

        <div class="footer">
            <p><strong>Northwest Samar State University</strong></p>
            <p>San Jorge Campus - Student Affairs and Services</p>
            <p style="margin-top: 15px;">
                This is an automated email. Please do not reply to this message.<br>
                For inquiries, please contact the Student Affairs office.
            </p>
        </div>
    </div>
</body>
</html>
