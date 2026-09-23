<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information Updated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #15592F;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .info-box {
            background-color: white;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #15592F;
            border-radius: 3px;
        }
        .changes-list {
            background-color: #fff3cd;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
            border-radius: 3px;
        }
        .changes-list h3 {
            margin-top: 0;
            color: #856404;
        }
        .change-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .change-item:last-child {
            border-bottom: none;
        }
        .change-label {
            font-weight: bold;
            color: #555;
        }
        .old-value {
            color: #d32f2f;
            text-decoration: line-through;
        }
        .new-value {
            color: #388e3c;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #777;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #15592F;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #124b28;
        }
        .alert {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Information Updated</h1>
        <p>NORTHWEST SAMAR STATE UNIVERSITY - SAN JORGE CAMPUS</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $studentData->fname }} {{ $studentData->lname }}</strong>,</p>

        <p>This is to inform you that your student information in our masterlist has been updated by our administrative staff.</p>

        <div class="info-box">
            <h3>Your Current Information:</h3>
            <p><strong>Student ID:</strong> {{ $studentData->student_id }}</p>
            <p><strong>Name:</strong> {{ $studentData->fname }} {{ $studentData->mname ? $studentData->mname . ' ' : '' }}{{ $studentData->lname }}</p>
            <p><strong>Email:</strong> {{ $studentData->email }}</p>
            <p><strong>Course:</strong> {{ $studentData->course }}</p>
            <p><strong>Year Level:</strong> {{ $studentData->year_level }}</p>
            <p><strong>Status:</strong> {{ $studentData->status }}</p>
        </div>

        @if (!empty($changes))
        <div class="changes-list">
            <h3>📝 What Was Changed:</h3>
            @foreach ($changes as $change)
            <div class="change-item">
                {!! $change !!}
            </div>
            @endforeach
        </div>
        @endif

        <div class="alert">
            <p><strong>ℹ️ Note:</strong> These changes were made by <strong>{{ $updatedBy }}</strong> on <strong>{{ now()->format('F d, Y \a\t h:i A') }}</strong></p>
        </div>

        <p>If you have any questions or concerns about these changes, please contact the Student Affairs Office.</p>

        <p style="margin-top: 30px;">Best regards,<br>
        <strong>Student Affairs and Services</strong><br>
        Northwest Samar State University<br>
        San Jorge Campus</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>© {{ date('Y') }} Northwest Samar State University - San Jorge Campus</p>
    </div>
</body>
</html>
