@echo off
echo ===============================================
echo Fixing Announcements Table
echo ===============================================
echo.

cd /d c:\xampp\htdocs\Logs-server-system\logs-server

echo Step 1: Running migrations...
php artisan migrate

echo.
echo ===============================================
echo Done! Try creating an announcement again.
echo ===============================================
pause
