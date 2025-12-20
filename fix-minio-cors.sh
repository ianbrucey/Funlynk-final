#!/bin/bash

# Fix MinIO CORS and bucket policy for FunLynk

echo "=== Fixing MinIO CORS and Bucket Policy ==="

# 1. Set bucket policy to allow public read
echo "Setting public read policy..."
docker exec minio mc anonymous set download myminio/funlynk-production

# 2. Create CORS configuration
echo "Creating CORS configuration..."
cat > /tmp/cors.json <<'EOF'
{
  "CORSRules": [
    {
      "AllowedOrigins": ["*"],
      "AllowedMethods": ["GET", "HEAD"],
      "AllowedHeaders": ["*"],
      "ExposeHeaders": ["ETag"],
      "MaxAgeSeconds": 3600
    }
  ]
}
EOF

# 3. Apply CORS configuration
echo "Applying CORS configuration..."
docker exec -i minio mc cors set /tmp/cors.json myminio/funlynk-production

# 4. Verify configuration
echo ""
echo "=== Verification ==="
echo "Bucket policy:"
docker exec minio mc anonymous get myminio/funlynk-production

echo ""
echo "CORS configuration:"
docker exec minio mc cors get myminio/funlynk-production

echo ""
echo "=== Done ==="
echo "MinIO should now allow public access to images with proper CORS headers"

