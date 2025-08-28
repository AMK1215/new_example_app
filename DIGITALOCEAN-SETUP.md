# DigitalOcean Profile Image Setup Guide

## Issue
Profile images are uploaded successfully but not displaying because:
1. Storage symlink is missing on the DigitalOcean server
2. Nginx is not configured to serve uploaded files
3. File permissions may be incorrect

## Solution

### Step 1: Upload Files to Server
Upload these files to your DigitalOcean server:
- `nginx-websocket.conf` - Updated Nginx configuration
- `deploy-storage-fix.sh` - Automated setup script

```bash
# Upload via SCP (replace with your server details)
scp nginx-websocket.conf root@luckymillion.online:/var/www/html/new-example-app/
scp deploy-storage-fix.sh root@luckymillion.online:/var/www/html/new-example-app/
```

### Step 2: Run the Setup Script
```bash
# SSH into your server
ssh root@luckymillion.online

# Navigate to your app directory
cd /var/www/html/new-example-app

# Make the script executable
chmod +x deploy-storage-fix.sh

# Run the setup script
sudo ./deploy-storage-fix.sh
```

### Step 3: Manual Verification (if script fails)

#### Create Storage Directories
```bash
mkdir -p /var/www/html/new-example-app/storage/app/public/avatars
mkdir -p /var/www/html/new-example-app/storage/app/public/covers
```

#### Create Storage Symlink
```bash
cd /var/www/html/new-example-app
ln -sf /var/www/html/new-example-app/storage/app/public /var/www/html/new-example-app/public/storage
```

#### Set Permissions
```bash
chown -R www-data:www-data /var/www/html/new-example-app/storage
chmod -R 755 /var/www/html/new-example-app/storage/app/public
```

#### Update Nginx Configuration
```bash
# Copy the nginx config
cp /var/www/html/new-example-app/nginx-websocket.conf /etc/nginx/sites-available/luckymillion.online

# Test nginx config
nginx -t

# Reload nginx
systemctl reload nginx
```

### Step 4: Test the Setup

#### Test Storage Symlink
```bash
# Create a test file
echo "test" > /var/www/html/new-example-app/storage/app/public/test.txt

# Check if accessible via web
curl https://luckymillion.online/storage/test.txt

# Clean up
rm /var/www/html/new-example-app/storage/app/public/test.txt
```

#### Test Profile Image Upload
1. Go to your profile page: `https://luckymillion.online/profile`
2. Click the camera icon on your avatar
3. Upload an image
4. The image should now display correctly

### Important Nginx Configuration Changes

The updated `nginx-websocket.conf` includes:

1. **Storage Location Block**:
   ```nginx
   location /storage/ {
       alias /var/www/html/new-example-app/storage/app/public/;
       expires 30d;
       add_header Cache-Control "public, immutable";
   }
   ```

2. **Security**:
   - Only allows image file types (jpg, png, gif, etc.)
   - Blocks access to other file types
   - Adds proper security headers

3. **Performance**:
   - Sets long cache headers for uploaded images
   - Disables access logs for static files

### Troubleshooting

#### Images Still Not Loading?
1. Check storage symlink: `ls -la /var/www/html/new-example-app/public/storage`
2. Check permissions: `ls -la /var/www/html/new-example-app/storage/app/public/`
3. Check nginx error logs: `tail -f /var/log/nginx/error.log`
4. Test direct file access: `curl -I https://luckymillion.online/storage/avatars/yourfile.jpg`

#### Permission Errors?
```bash
# Fix ownership
chown -R www-data:www-data /var/www/html/new-example-app/storage

# Fix permissions
chmod -R 755 /var/www/html/new-example-app/storage/app/public
```

#### Nginx Errors?
```bash
# Test configuration
nginx -t

# Check syntax
nginx -T | grep -A 10 -B 10 "storage"

# Reload after fixes
systemctl reload nginx
```

### File Structure After Setup
```
/var/www/html/new-example-app/
├── public/
│   └── storage/ → ../storage/app/public/ (symlink)
├── storage/
│   └── app/
│       └── public/
│           ├── avatars/
│           └── covers/
└── ...
```

### Expected Result
- Profile images upload successfully ✅
- Images display in the application ✅
- Images are accessible via: `https://luckymillion.online/storage/avatars/filename.jpg` ✅
- Proper caching and security headers ✅
