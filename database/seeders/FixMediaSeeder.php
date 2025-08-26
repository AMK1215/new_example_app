<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use Faker\Factory as Faker;

class FixMediaSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        $this->command->info('Fixing media URLs in existing posts...');
        
        // Get posts with media that might have placeholder issues
        $postsWithMedia = Post::whereNotNull('media')->whereRaw("json_array_length(media) > 0")->get();
        
        $fixedCount = 0;
        
        foreach ($postsWithMedia as $post) {
            $media = $post->media;
            $newMedia = [];
            
            foreach ($media as $mediaUrl) {
                // Check if it's a placeholder URL that might fail
                if (str_contains($mediaUrl, 'via.placeholder.com') || 
                    str_contains($mediaUrl, 'faker') ||
                    str_contains($mediaUrl, 'lorempixel')) {
                    
                    // Replace with reliable sources based on post type
                    if ($post->type === 'image') {
                        $imageSources = [
                            'https://picsum.photos/800/600?random=' . $faker->numberBetween(1, 1000),
                            'https://source.unsplash.com/800x600/?nature&sig=' . $faker->numberBetween(1, 1000),
                            'https://source.unsplash.com/800x600/?landscape&sig=' . $faker->numberBetween(1, 1000),
                            'https://source.unsplash.com/800x600/?city&sig=' . $faker->numberBetween(1, 1000),
                            'https://source.unsplash.com/800x600/?food&sig=' . $faker->numberBetween(1, 1000),
                            'https://source.unsplash.com/800x600/?art&sig=' . $faker->numberBetween(1, 1000),
                        ];
                        $newMedia[] = $faker->randomElement($imageSources);
                    } elseif ($post->type === 'video') {
                        $videoSources = [
                            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                        ];
                        $newMedia[] = $faker->randomElement($videoSources);
                    } else {
                        // Keep original for other types
                        $newMedia[] = $mediaUrl;
                    }
                    
                    $fixedCount++;
                } else {
                    // Keep good URLs
                    $newMedia[] = $mediaUrl;
                }
            }
            
            // Update the post with new media
            $post->update(['media' => $newMedia]);
        }
        
        $this->command->info("✅ Fixed media URLs in {$fixedCount} posts!");
        $this->command->info('📱 Posts now use reliable image and video sources');
        $this->command->info('🖼️ No more placeholder image errors');
    }
}
