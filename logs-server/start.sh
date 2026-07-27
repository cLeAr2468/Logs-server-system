#!/bin/bash

echo "Starting Laravel application..."

# Ensure storage directories exist with proper permissions
echo "Setting up storage directories..."
mkdir -p storage/app/public/announcements
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create storage symlink (force recreation if exists)
echo "Creating storage link..."
php artisan storage:link --force || echo "Storage link already exists or failed"

# Clear any cached config
php artisan config:clear
php artisan cache:clear

# Start the server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
