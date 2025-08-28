<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Profile Upload Process ===\n\n";

// Check if the ProfileController exists and has the right methods
$controllerFile = app_path('Http/Controllers/Api/ProfileController.php');
if (file_exists($controllerFile)) {
    echo "✅ ProfileController exists\n";
    
    $content = file_get_contents($controllerFile);
    
    if (strpos($content, 'function update') !== false) {
        echo "✅ ProfileController has update method\n";
    } else {
        echo "❌ ProfileController missing update method\n";
    }
    
    if (strpos($content, 'avatar') !== false) {
        echo "✅ ProfileController handles avatar uploads\n";
    } else {
        echo "❌ ProfileController doesn't handle avatars\n";
    }
    
    if (strpos($content, 'cover_photo') !== false) {
        echo "✅ ProfileController handles cover photo uploads\n";
    } else {
        echo "❌ ProfileController doesn't handle cover photos\n";
    }
} else {
    echo "❌ ProfileController not found\n";
}

// Check routes
echo "\nChecking API routes...\n";
$routesFile = base_path('routes/api.php');
if (file_exists($routesFile)) {
    $content = file_get_contents($routesFile);
    
    if (strpos($content, 'ProfileController') !== false) {
        echo "✅ ProfileController routes exist\n";
    } else {
        echo "❌ ProfileController routes missing\n";
    }
}

echo "\nNext: Try uploading a profile image and check the network tab in browser dev tools\n";
