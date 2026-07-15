<?php

/**
 * Email Testing Script
 * 
 * Run this script to test if your email configuration is working
 * Usage: php test-email.php your-email@example.com
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;

// Get email from command line argument
$recipientEmail = $argv[1] ?? null;

if (!$recipientEmail) {
    echo "❌ Error: Please provide a recipient email address\n";
    echo "Usage: php test-email.php your-email@example.com\n";
    exit(1);
}

// Validate email
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Error: Invalid email address format\n";
    exit(1);
}

echo "📧 Testing Email Configuration\n";
echo "================================\n\n";
echo "Configuration:\n";
echo "  Mail Driver: " . config('mail.default') . "\n";
echo "  From Address: " . config('mail.from.address') . "\n";
echo "  From Name: " . config('mail.from.name') . "\n";
echo "  To Address: $recipientEmail\n\n";

echo "Sending test OTP email...\n";

try {
    // Generate test OTP
    $testOtp = '123456';
    
    // Send email
    Mail::to($recipientEmail)->send(new SendOtpMail($testOtp, 'Test User'));
    
    echo "✅ SUCCESS! Test email sent successfully!\n\n";
    echo "Next steps:\n";
    echo "  1. Check the recipient's inbox: $recipientEmail\n";
    echo "  2. Check spam/junk folder if not in inbox\n";
    echo "  3. Verify OTP code is: $testOtp\n";
    echo "  4. Check Laravel logs: storage/logs/laravel.log\n";
    
} catch (\Exception $e) {
    echo "❌ FAILED! Error sending email:\n\n";
    echo "Error Message: " . $e->getMessage() . "\n\n";
    echo "Possible causes:\n";
    
    if (strpos($e->getMessage(), 'Authentication') !== false) {
        echo "  • Invalid API key or credentials\n";
        echo "  • Check RESEND_API_KEY in .env file\n";
    } elseif (strpos($e->getMessage(), 'not verified') !== false || strpos($e->getMessage(), 'not allowed') !== false) {
        echo "  • ⚠️  Resend sandbox restriction detected!\n";
        echo "  • Email address '$recipientEmail' is not whitelisted\n";
        echo "  • Solution: Verify your domain or whitelist this email in Resend\n";
    } elseif (strpos($e->getMessage(), 'Connection') !== false || strpos($e->getMessage(), 'timeout') !== false) {
        echo "  • Network connection issue\n";
        echo "  • Firewall blocking SMTP ports\n";
    } else {
        echo "  • Check mail configuration in .env file\n";
        echo "  • Verify email service credentials\n";
    }
    
    echo "\nFor detailed troubleshooting, see: EMAIL_FIX_GUIDE.md\n";
    echo "\nFull error trace:\n";
    echo $e->getTraceAsString() . "\n";
    
    exit(1);
}

echo "\n================================\n";
echo "Test completed!\n";
