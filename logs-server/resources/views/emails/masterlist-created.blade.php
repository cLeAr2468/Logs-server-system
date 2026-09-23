<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Masterlist</title>
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
            border-bottom: 3px solid #16a34a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #16a34a;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 25px;
        }
        .info-section {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
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
        .welcome-box {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Welcome to the Masterlist!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $student->fname }} {{ $student->lname }},</p>
            
            <div class="welcome-box">
                <p style="margin: 0;"><strong>Great news!</strong> You have been successfully added to the masterlist system.</p>
            </div>

            <p>Your information has been registered in our system. Below are your details:</p>

            <div class="info-section">
                <h3 style="margin-top: 0; color: #16a34a;">Your Information</h3>
                
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
                    <span class="info-label">Status:</span>
                    <span>{{ $student->status }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Added By:</span>
                    <span>{{ $createdBy }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Registration Date:</span>
                    <span>{{ now()->format('F d, Y h:i A') }}</span>
                </div>
            </div>

            <p>Please review your information carefully. If you notice any discrepancies, contact your administrator immediately.</p>
            
            <p>You will receive email notifications whenever your record is updated in the system.</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from the Logs Management System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
