<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Profile;
use App\Models\Post;

class FixImageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:image-urls {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix image URLs to use correct domain for production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Fixing image URLs for production...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Fix profile avatars and cover photos
        $this->fixProfileImages($dryRun);
        
        // Fix post media URLs
        $this->fixPostMedia($dryRun);
        
        $this->info('Image URL fixing complete!');
    }
    
    private function fixProfileImages($dryRun)
    {
        $this->info('Fixing profile images...');
        
        $profiles = Profile::whereNotNull('avatar')
                          ->orWhereNotNull('cover_photo')
                          ->get();
        
        $avatarCount = 0;
        $coverCount = 0;
        
        foreach ($profiles as $profile) {
            $changes = [];
            
            // Fix avatar
            if ($profile->avatar && !filter_var($profile->avatar, FILTER_VALIDATE_URL)) {
                $oldAvatar = $profile->avatar;
                $newAvatar = $profile->avatar;
                
                // Remove 'storage/' prefix if it exists
                if (strpos($newAvatar, 'storage/') === 0) {
                    $newAvatar = substr($newAvatar, 8);
                }
                
                if ($oldAvatar !== $newAvatar) {
                    $changes['avatar'] = $newAvatar;
                    $avatarCount++;
                    $this->line("Profile {$profile->id}: avatar {$oldAvatar} -> {$newAvatar}");
                }
            }
            
            // Fix cover photo
            if ($profile->cover_photo && !filter_var($profile->cover_photo, FILTER_VALIDATE_URL)) {
                $oldCover = $profile->cover_photo;
                $newCover = $profile->cover_photo;
                
                // Remove 'storage/' prefix if it exists
                if (strpos($newCover, 'storage/') === 0) {
                    $newCover = substr($newCover, 8);
                }
                
                if ($oldCover !== $newCover) {
                    $changes['cover_photo'] = $newCover;
                    $coverCount++;
                    $this->line("Profile {$profile->id}: cover_photo {$oldCover} -> {$newCover}");
                }
            }
            
            // Apply changes
            if (!$dryRun && !empty($changes)) {
                $profile->update($changes);
            }
        }
        
        $this->info("Fixed {$avatarCount} avatars and {$coverCount} cover photos");
    }
    
    private function fixPostMedia($dryRun)
    {
        $this->info('Fixing post media URLs...');
        
        $posts = Post::withMedia()->get();
        
        $postCount = 0;
        
        foreach ($posts as $post) {
            if (!is_array($post->media)) {
                continue;
            }
            
            $updatedMedia = [];
            $hasChanges = false;
            
            foreach ($post->media as $mediaPath) {
                $newPath = $mediaPath;
                
                // Skip if already a full URL
                if (filter_var($mediaPath, FILTER_VALIDATE_URL)) {
                    $updatedMedia[] = $mediaPath;
                    continue;
                }
                
                // Remove 'storage/' prefix if it exists
                if (strpos($newPath, 'storage/') === 0) {
                    $newPath = substr($newPath, 8);
                    $hasChanges = true;
                }
                
                $updatedMedia[] = $newPath;
                
                if ($mediaPath !== $newPath) {
                    $this->line("Post {$post->id}: media {$mediaPath} -> {$newPath}");
                }
            }
            
            if ($hasChanges) {
                $postCount++;
                if (!$dryRun) {
                    $post->update(['media' => $updatedMedia]);
                }
            }
        }
        
        $this->info("Fixed media URLs in {$postCount} posts");
    }
}
