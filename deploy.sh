#!/bin/bash
set -e

echo "🚀 Starting FunLynk deployment..."

# Pull the latest code (uncomment if deploying via git on server)
# git pull origin main

echo "📦 Building and starting Docker containers..."
docker compose up -d --build

echo "⏳ Waiting for services to initialize (5 seconds)..."
sleep 5

echo "🛠 Running post-deployment tasks inside the app container..."

# Run database migrations
echo "-> Running database migrations..."
docker compose exec -T app php artisan migrate --force

# Cache optimization exactly suited for Laravel production
echo "-> Caching configuration..."
docker compose exec -T app php artisan config:cache

echo "-> Caching routes..."
docker compose exec -T app php artisan route:cache

echo "-> Caching views..."
docker compose exec -T app php artisan view:cache

# Restart Horizon workers to pick up new code
echo "-> Restarting Horizon queue workers..."
docker compose exec -T app php artisan horizon:terminate

# Reload Octane (RoadRunner) to load new code into memory
echo "-> Reloading Octane..."
docker compose exec -T app php artisan octane:reload --server=roadrunner

echo "✅ Deployment complete! The FunLynk app is up and running."
