#!/bin/bash

# Post-Deployment Script for Railway
# Run this in Railway console after first deployment

echo "🚀 Starting post-deployment setup..."

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Create storage symlink
echo "🔗 Creating storage link..."
php artisan storage:link

# Cache configuration
echo "⚡ Caching configuration..."
php artisan config:cache

# Cache routes
echo "🛣️ Caching routes..."
php artisan route:cache

# Clear any old caches
echo "🧹 Clearing old caches..."
php artisan view:clear

# Display application info
echo "📋 Application information:"
php artisan about

# Test database connection
echo "🔌 Testing database connection..."
php artisan db:show

echo "✅ Post-deployment setup complete!"
echo ""
echo "🔗 Test your API at: /api/health"
echo "📝 Next: Add FRONTEND_URL and CLIENT_URL to environment variables"
