#!/bin/bash

echo "Starting Laravel application..."

# Clear any cached config
php artisan config:clear
php artisan cache:clear

# Start the server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
