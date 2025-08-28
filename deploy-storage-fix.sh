#!/bin/bash

# DigitalOcean Storage Setup Script for Profile Images
# Run this script on your DigitalOcean server as root or with sudo

echo "=== Setting up storage for profile images on DigitalOcean ==="

# Define paths
APP_ROOT="/var/www/html/new-example-app"
STORAGE_PATH="$APP_ROOT/storage"
PUBLIC_STORAGE="$STORAGE_PATH/app/public"

echo "1. Creating storage directories..."

# Create storage directories
mkdir -p "$PUBLIC_STORAGE/avatars"
mkdir -p "$PUBLIC_STORAGE/covers"
mkdir -p "$PUBLIC_STORAGE/posts"

echo "2. Setting up symbolic link..."

# Remove existing symlink if it exists
if [ -L "$APP_ROOT/public/storage" ]; then
    echo "Removing existing storage symlink..."
    rm "$APP_ROOT/public/storage"
fi

# Create new symbolic link
ln -sf "$PUBLIC_STORAGE" "$APP_ROOT/public/storage"

echo "3. Setting proper permissions..."

# Set ownership to web server user (assuming www-data, adjust if different)
chown -R www-data:www-data "$STORAGE_PATH"
chown -R www-data:www-data "$APP_ROOT/public/storage"

# Set proper permissions
chmod -R 755 "$STORAGE_PATH"
chmod -R 755 "$PUBLIC_STORAGE"

# Ensure Laravel can write to storage
chmod -R 775 "$STORAGE_PATH/app"
chmod -R 775 "$STORAGE_PATH/framework"
chmod -R 775 "$STORAGE_PATH/logs"

echo "4. Setting up Nginx configuration..."

# Copy the updated nginx configuration
NGINX_CONFIG="/etc/nginx/sites-available/luckymillion.online"

if [ -f "nginx-websocket.conf" ]; then
    echo "Copying nginx configuration..."
    cp nginx-websocket.conf "$NGINX_CONFIG"
    
    # Test nginx configuration
    echo "Testing nginx configuration..."
    nginx -t
    
    if [ $? -eq 0 ]; then
        echo "Nginx configuration is valid. Reloading..."
        systemctl reload nginx
    else
        echo "ERROR: Nginx configuration is invalid. Please check the config."
        exit 1
    fi
else
    echo "WARNING: nginx-websocket.conf not found in current directory."
    echo "Please manually update your nginx configuration."
fi

echo "5. Setting up Laravel environment..."

# Ensure APP_URL is set correctly
cd "$APP_ROOT"

# Check if .env exists
if [ -f ".env" ]; then
    # Update APP_URL in .env file
    sed -i 's|APP_URL=.*|APP_URL=https://luckymillion.online|g' .env
    
    # Ensure APP_ENV is production
    sed -i 's|APP_ENV=.*|APP_ENV=production|g' .env
    
    # Clear cache and fix image URLs
    if [ -f "artisan" ]; then
        echo "Clearing Laravel caches..."
        php artisan config:clear
        php artisan cache:clear
        php artisan view:clear
        php artisan route:clear
        
        echo "Fixing image URLs in database..."
        php artisan fix:image-urls
    fi
else
    echo "WARNING: .env file not found. Please create it from .env.example"
fi

echo "6. Verifying setup..."

# Check if storage symlink works
if [ -L "$APP_ROOT/public/storage" ] && [ -d "$APP_ROOT/public/storage" ]; then
    echo "✅ Storage symlink is working correctly"
else
    echo "❌ Storage symlink is not working"
fi

# Check permissions
if [ -w "$PUBLIC_STORAGE" ]; then
    echo "✅ Storage directory is writable"
else
    echo "❌ Storage directory is not writable"
fi

# Test if nginx can serve a test file
TEST_FILE="$PUBLIC_STORAGE/test.txt"
echo "test" > "$TEST_FILE"

if [ -f "$TEST_FILE" ]; then
    echo "✅ Test file created successfully"
    rm "$TEST_FILE"
else
    echo "❌ Cannot create test file in storage"
fi

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Copy this script to your DigitalOcean server"
echo "2. Make it executable: chmod +x deploy-storage-fix.sh"
echo "3. Run it as root: sudo ./deploy-storage-fix.sh"
echo "4. Test uploading a profile image"
echo ""
echo "Your uploaded images will be accessible at:"
echo "https://luckymillion.online/storage/avatars/filename.jpg"
echo "https://luckymillion.online/storage/covers/filename.jpg"
echo ""
