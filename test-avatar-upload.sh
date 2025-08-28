#!/bin/bash

echo "=== Testing Avatar Upload ==="

# Get a fresh login token
TOKEN_RESPONSE=$(curl -s -X POST https://luckymillion.online/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"amk@gmail.com","password":"123456viP"}')

TOKEN=$(echo $TOKEN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

echo "Login token: $TOKEN"

# Create a test image file
echo "Creating test image..."
echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" | base64 -d > test-avatar.png

# Test avatar upload
echo "Testing avatar upload..."
curl -X PUT https://luckymillion.online/api/profiles \
  -H "Authorization: Bearer $TOKEN" \
  -F "avatar=@test-avatar.png" \
  -F "bio=Testing avatar upload from API"

echo ""
echo "=== Checking if avatar was stored ==="
ls -la /var/www/html/new-example-app/storage/app/public/avatars/

# Clean up
rm -f test-avatar.png

echo "Test completed!"
