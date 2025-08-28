<?php

namespace App\Traits;

trait HasMediaUrls
{
    /**
     * Generate the correct public URL for storage files
     * This ensures URLs work in both local and production environments
     */
    public function generateStorageUrl($path)
    {
        if (!$path) {
            return null;
        }
        
        // If it's already a full URL, return as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Get the base URL - prefer production URL if available
        $baseUrl = $this->getBaseUrl();
        
        // Ensure the path starts with storage/
        $storagePath = $path;
        if (strpos($path, 'storage/') !== 0) {
            $storagePath = 'storage/' . ltrim($path, '/');
        }
        
        return $baseUrl . '/' . $storagePath;
    }
    
    /**
     * Get the correct base URL for the application
     */
    private function getBaseUrl()
    {
        // Check if we're in production based on APP_ENV or domain
        $appEnv = env('APP_ENV', 'local');
        $appUrl = env('APP_URL', 'http://localhost');
        
        // If APP_URL is set to production domain, use it
        if (strpos($appUrl, 'luckymillion.online') !== false) {
            return $appUrl;
        }
        
        // If in production environment, force HTTPS production URL
        if ($appEnv === 'production') {
            return 'https://luckymillion.online';
        }
        
        // Check if current request is from production domain
        if (request() && request()->getHost() === 'luckymillion.online') {
            $scheme = request()->isSecure() ? 'https' : 'http';
            return $scheme . '://luckymillion.online';
        }
        
        // Fall back to configured APP_URL or localhost
        return $appUrl;
    }
}
