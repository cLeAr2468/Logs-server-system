<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masterlist Record Deleted</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 3px solid #dc2626;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #dc2626;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 25px;
        }
        .info-section {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            min-width: 120px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .note {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Masterlist Record Deleted</h1>
        </div>

        <div class="content">
            <p>Dear {{ $student->fname }} {{ $student->lname }},</p>
            
            <p>This is to inform you that your record has been removed from the masterlist system.</p>

            <div class="info-section">
                <h3 style="margin-top: 0; color: #dc2626;">Deleted Record Information</h3>
                
                <div class="info-row">
                    <span class="info-label">Student Number:</span>
                    <span>{{ $student->student_id }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span>{{ $student->fname }} {{ $student->mname }} {{ $student->lname }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span>{{ $student->email }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Program:</span>
                    <span>{{ $student->course }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Year Level:</span>
                    <span>{{ $student->year_level }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Deleted By:</span>
                    <span>{{ $deletedBy }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Deletion Date:</span>
                    <span>{{ now()->format('F d, Y h:i A') }}</span>
                </div>
            </div>

            <div class="note">
                <strong>Note:</strong> If you believe this deletion was made in error, please contact your administrator immediately for assistance.
            </div>

            <p>If you have any questions or concerns, please don't hesitate to reach out to your administrator.</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from the Logs Management System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
