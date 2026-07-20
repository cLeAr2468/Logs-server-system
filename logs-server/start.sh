#!/bin/bash

echo "Starting Laravel application..."

# Create storage symlink (force recreation if exists)
echo "Creating storage link..."
php artisan storage:link --force || echo "Storage link already exists or failed"

# Clear any cached config
php artisan config:clear
php artisan cache:clear

# Start the server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
