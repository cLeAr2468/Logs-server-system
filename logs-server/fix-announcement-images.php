<?php
/**
 * Script to check and fix announcement image paths
 * Run with: php fix-announcement-images.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Announcement;
use Illuminate\Support\Facades\Storage;

echo "=== Announcement Image Path Checker ===\n\n";

$announcements = Announcement::all();

foreach ($announcements as $announcement) {
    echo "ID: {$announcement->id}\n";
    echo "Title: {$announcement->title}\n";
    echo "Cover Image Path: " . ($announcement->cover_image ?: 'NULL') . "\n";
    
    if ($announcement->cover_image) {
        // Check if file exists
        $exists = Storage::disk('public')->exists($announcement->cover_image);
        echo "File Exists: " . ($exists ? 'YES' : 'NO') . "\n";
        
        if ($exists) {
            $fullPath = Storage::disk('public')->path($announcement->cover_image);
            echo "Full Path: {$fullPath}\n";
        }
        
        // Check if path has spaces and fix
        if (strpos($announcement->cover_image, ' ') !== false) {
            $newPath = str_replace(' ', '', $announcement->cover_image);
            echo "WARNING: Path contains spaces!\n";
            echo "Current: {$announcement->cover_image}\n";
            echo "Fixed: {$newPath}\n";
            
            // Uncomment to apply fix:
            // $announcement->cover_image = $newPath;
            // $announcement->save();
            // echo "FIXED!\n";
        }
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

// List all files in announcements directory
echo "\n=== Files in storage/app/public/announcements ===\n";
$files = Storage::disk('public')->files('announcements');
foreach ($files as $file) {
    echo "- {$file}\n";
}

echo "\nDone!\n";
