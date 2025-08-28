<?php
echo "=== PHP Upload Configuration Check ===\n\n";

// Check upload settings
echo "file_uploads: " . (ini_get('file_uploads') ? 'enabled' : 'disabled') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

// Check storage permissions
$storagePath = __DIR__ . '/storage/app/public';
echo "\nStorage directory: $storagePath\n";
echo "Storage writable: " . (is_writable($storagePath) ? 'YES' : 'NO') . "\n";

$avatarsPath = $storagePath . '/avatars';
echo "Avatars directory: $avatarsPath\n";
echo "Avatars exists: " . (is_dir($avatarsPath) ? 'YES' : 'NO') . "\n";
echo "Avatars writable: " . (is_writable($avatarsPath) ? 'YES' : 'NO') . "\n";

$coversPath = $storagePath . '/covers';
echo "Covers directory: $coversPath\n";
echo "Covers exists: " . (is_dir($coversPath) ? 'YES' : 'NO') . "\n";
echo "Covers writable: " . (is_writable($coversPath) ? 'YES' : 'NO') . "\n";

// Check symlink
$symlinkPath = __DIR__ . '/public/storage';
echo "\nSymlink path: $symlinkPath\n";
echo "Symlink exists: " . (is_link($symlinkPath) ? 'YES' : 'NO') . "\n";
echo "Symlink valid: " . (is_dir($symlinkPath) ? 'YES' : 'NO') . "\n";

// Test file creation
echo "\n=== Testing File Creation ===\n";
$testFile = $avatarsPath . '/test-' . time() . '.txt';
$result = file_put_contents($testFile, 'test content');

if ($result !== false) {
    echo "✅ Test file created successfully\n";
    unlink($testFile); // Clean up
} else {
    echo "❌ Failed to create test file\n";
    echo "Error: " . error_get_last()['message'] . "\n";
}
?>
