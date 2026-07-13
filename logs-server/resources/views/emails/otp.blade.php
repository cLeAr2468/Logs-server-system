<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .email-body {
            padding: 40px 30px;
            color: #333333;
        }
        .email-body h2 {
            color: #15803d;
            font-size: 22px;
            margin-bottom: 20px;
        }
        .email-body p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #555555;
        }
        .otp-box {
            background-color: #f0fdf4;
            border: 2px dashed #16a34a;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #15803d;
            margin: 10px 0;
        }
        .otp-label {
            font-size: 14px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .warning-box {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .warning-box p {
            margin: 0;
            font-size: 14px;
            color: #991b1b;
        }
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #1e40af;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 25px 30px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .support-link {
            color: #15803d;
            text-decoration: none;
            font-weight: 600;
        }
        .support-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔒 Password Reset Request</h1>
        </div>

        <div class="email-body">
            <h2>Hello, {{ $userName }}!</h2>

            <p>
                We received a request to reset your password for your Logs System account. 
                Use the verification code below to complete the password reset process.
            </p>

            <div class="otp-box">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="info-box">
                <p>
                    ⏰ <strong>This code will expire in 5 minutes.</strong> Please use it promptly to reset your password.
                </p>
            </div>

            <p>
                If you didn't request a password reset, please ignore this email. 
                Your account remains secure and no changes have been made.
            </p>

            <div class="warning-box">
                <p>
                    ⚠️ <strong>Security Note:</strong> Never share this code with anyone. 
                    The Logs System team will never ask you for this code.
                </p>
            </div>

            <p style="margin-top: 30px;">
                Best regards,<br>
                <strong>Logs System Team</strong>
            </p>
        </div>

        <div class="email-footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>
                If you need assistance, contact our support team at 
                <a href="mailto:support@logssystem.com" class="support-link">support@logssystem.com</a>
            </p>
            <p style="margin-top: 15px;">
                © {{ date('Y') }} Logs System. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
