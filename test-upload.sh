#!/bin/bash

echo "Testing Profile Upload..."

# Get token
echo "Getting auth token..."
TOKEN_RESPONSE=$(curl -s -X POST https://luckymillion.online/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"amk@gmail.com","password":"123456viP"}')

TOKEN=$(echo $TOKEN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "Failed to get token"
    echo "Response: $TOKEN_RESPONSE"
    exit 1
fi

echo "Token obtained: ${TOKEN:0:20}..."

# Create a small test image (1x1 pixel PNG)
echo "Creating test image..."
echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" | base64 -d > test-avatar.png

echo "Testing avatar upload..."
UPLOAD_RESPONSE=$(curl -s -X PUT https://luckymillion.online/api/profiles \
  -H "Authorization: Bearer $TOKEN" \
  -F "avatar=@test-avatar.png" \
  -F "bio=Testing avatar upload")

echo "Upload response:"
echo "$UPLOAD_RESPONSE"

# Clean up
rm -f test-avatar.png

echo "Test completed. Check Laravel logs for details."
