#!/bin/bash
# FunLynk Deployment Script
# This script handles the complete deployment process including database seeding

set -e  # Exit on error

echo "🚀 Starting FunLynk deployment..."

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
echo "📦 Installing Node dependencies..."
npm ci --audit false

echo "🏗️  Building frontend assets..."
npm run build

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Seed location data BEFORE indexing
echo "🌍 Seeding location data..."
php artisan db:seed --class=LocationSeeder

# Clear and cache config/routes/views
echo "🔧 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create Meilisearch indexes
echo "🔍 Creating Meilisearch indexes..."
php artisan scout:index posts_index
php artisan scout:index activities_index
php artisan scout:index users_index
php artisan scout:index locations_index

# Sync index settings (filterable/sortable attributes)
echo "⚙️  Syncing Meilisearch index settings..."
php artisan scout:sync-index-settings

# Index search data (Meilisearch) - NOW data exists
echo "📇 Indexing search data..."
php artisan scout:import "App\Models\Post"
php artisan scout:import "App\Models\Activity"
php artisan scout:import "App\Models\User"
php artisan scout:import "App\Models\Location"

# Restart queue workers (if using Laravel Cloud queues)
# Uncomment if you're using queue workers
# echo "♻️  Restarting queue workers..."
# php artisan queue:restart

echo "✅ Deployment complete!"

