# Development Guide

This guide covers the development workflow, coding standards, testing procedures, and contribution guidelines for AMKSocial.

## Table of Contents

- [Development Environment Setup](#development-environment-setup)
- [Project Structure](#project-structure)
- [Coding Standards](#coding-standards)
- [Git Workflow](#git-workflow)
- [Testing](#testing)
- [Debugging](#debugging)
- [Performance Guidelines](#performance-guidelines)
- [Security Guidelines](#security-guidelines)
- [Contributing](#contributing)
- [Code Review Process](#code-review-process)

## Development Environment Setup

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 18.x+
- npm 9.x+
- Database (PostgreSQL/MySQL/SQLite)
- Redis (optional, for caching)
- Git

### Local Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/your-username/amksocial.git
   cd amksocial
   ```

2. **Backend Setup**
   ```bash
   # Install PHP dependencies
   composer install
   
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   
   # Configure database in .env
   # Run migrations
   php artisan migrate
   
   # Seed database (optional)
   php artisan db:seed
   
   # Create storage link
   php artisan storage:link
   ```

3. **Frontend Setup**
   ```bash
   cd social_react
   npm install
   ```

4. **Start Development Servers**
   ```bash
   # Terminal 1: Laravel backend
   php artisan serve
   
   # Terminal 2: Queue worker (optional)
   php artisan queue:work
   
   # Terminal 3: WebSocket server (optional)
   php artisan reverb:start
   
   # Terminal 4: React frontend
   cd social_react
   npm run dev
   ```

### Docker Setup (Alternative)

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: amksocial
      POSTGRES_USER: amksocial
      POSTGRES_PASSWORD: password
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7
    ports:
      - "6379:6379"

  frontend:
    build:
      context: ./social_react
      dockerfile: Dockerfile
    ports:
      - "5173:5173"
    volumes:
      - ./social_react:/app
    depends_on:
      - app

volumes:
  postgres_data:
```

Start with Docker:

```bash
docker-compose up -d
```

## Project Structure

### Backend Structure

```
app/
├── Console/
│   └── Commands/          # Artisan commands
├── Events/               # Event classes
├── Http/
│   ├── Controllers/
│   │   └── Api/          # API controllers
│   ├── Middleware/       # Custom middleware
│   └── Requests/         # Form requests
├── Models/               # Eloquent models
├── Providers/            # Service providers
├── Services/             # Business logic services
└── Traits/               # Reusable traits

database/
├── factories/            # Model factories
├── migrations/           # Database migrations
└── seeders/             # Database seeders

routes/
├── api.php              # API routes
├── channels.php         # Broadcast channels
└── web.php              # Web routes

tests/
├── Feature/             # Feature tests
└── Unit/                # Unit tests
```

### Frontend Structure

```
social_react/src/
├── components/
│   ├── auth/            # Authentication components
│   ├── chat/            # Chat components
│   ├── comments/        # Comment components
│   ├── friendship/      # Friend management
│   ├── layout/          # Layout components
│   ├── posts/           # Post components
│   └── profile/         # Profile components
├── contexts/            # React contexts
├── hooks/               # Custom hooks
├── services/            # API services
├── utils/               # Utility functions
└── config/              # Configuration
```

## Coding Standards

### PHP Standards (PSR-12)

Follow PSR-12 coding standards with these additions:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Post model for social media posts
 *
 * @property int $id
 * @property string $content
 * @property string $type
 * @property array $media
 */
class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'content',
        'type',
        'media',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'media' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post's like count.
     */
    public function getLikeCountAttribute(): int
    {
        return $this->likes()->count();
    }
}
```

### JavaScript/React Standards

Use ESLint configuration:

```json
{
  "extends": [
    "react-app",
    "react-app/jest"
  ],
  "rules": {
    "indent": ["error", 2],
    "quotes": ["error", "single"],
    "semi": ["error", "always"],
    "no-unused-vars": "error",
    "react/prop-types": "warn",
    "react-hooks/exhaustive-deps": "warn"
  }
}
```

Component structure:

```jsx
import React, { useState, useEffect, useCallback } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { api } from '../services/api';
import LoadingSpinner from './LoadingSpinner';
import ErrorMessage from './ErrorMessage';

/**
 * PostCard component for displaying individual posts
 * 
 * @param {Object} props
 * @param {Object} props.post - Post object
 * @param {Function} props.onLike - Like callback
 * @param {Function} props.onComment - Comment callback
 */
const PostCard = ({ post, onLike, onComment }) => {
  // 1. State declarations
  const [isLiking, setIsLiking] = useState(false);
  const [showComments, setShowComments] = useState(false);

  // 2. API calls and mutations
  const { data: comments, isLoading } = useQuery({
    queryKey: ['comments', post.id],
    queryFn: () => api.get(`/posts/${post.id}/comments`),
    enabled: showComments,
  });

  const likeMutation = useMutation({
    mutationFn: () => api.post(`/posts/${post.id}/like`),
    onSuccess: onLike,
  });

  // 3. Event handlers
  const handleLike = useCallback(async () => {
    if (isLiking) return;
    
    setIsLiking(true);
    try {
      await likeMutation.mutateAsync();
    } finally {
      setIsLiking(false);
    }
  }, [isLiking, likeMutation]);

  const handleToggleComments = useCallback(() => {
    setShowComments(prev => !prev);
  }, []);

  // 4. Effects
  useEffect(() => {
    // Side effects
  }, []);

  // 5. Early returns
  if (!post) {
    return <ErrorMessage message="Post not found" />;
  }

  // 6. Render
  return (
    <div className="post-card">
      {/* Component JSX */}
    </div>
  );
};

export default PostCard;
```

### Database Standards

#### Migration Standards

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('type')->default('text');
            $table->json('media')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
            // Add indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['is_public', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

#### Model Standards

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    // 1. Table configuration
    protected $table = 'posts';
    protected $primaryKey = 'id';
    public $timestamps = true;

    // 2. Mass assignment
    protected $fillable = [
        'user_id',
        'content',
        'type',
        'media',
        'metadata',
        'is_public',
    ];

    // 3. Hidden attributes
    protected $hidden = [
        'user_id',
    ];

    // 4. Casts
    protected $casts = [
        'media' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 5. Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    // 6. Accessors and Mutators
    public function getMediaUrlsAttribute(): array
    {
        if (!$this->media) {
            return [];
        }

        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $this->media);
    }

    // 7. Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // 8. Helper methods
    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function getLikeCount(): int
    {
        return $this->likes()->count();
    }
}
```

## Git Workflow

### Branch Naming Convention

- `main` - Production-ready code
- `develop` - Development branch
- `feature/description` - New features
- `bugfix/description` - Bug fixes
- `hotfix/description` - Critical fixes
- `release/version` - Release preparation

### Commit Message Format

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

Examples:

```
feat(posts): add video upload functionality

- Added video file validation
- Implemented video processing
- Updated frontend video player

Closes #123
```

```
fix(auth): resolve token expiration issue

Fixed bug where expired tokens weren't properly handled,
causing users to see error messages instead of login prompt.

Fixes #456
```

### Development Workflow

1. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/new-feature-name
   ```

2. **Development**
   ```bash
   # Make changes
   git add .
   git commit -m "feat(scope): description"
   
   # Push regularly
   git push origin feature/new-feature-name
   ```

3. **Create Pull Request**
   - Ensure tests pass
   - Update documentation
   - Request code review
   - Address feedback

4. **Merge to Develop**
   ```bash
   git checkout develop
   git pull origin develop
   git merge feature/new-feature-name
   git push origin develop
   git branch -d feature/new-feature-name
   ```

## Testing

### Backend Testing

#### Unit Tests

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();
        
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post content',
            'type' => 'text',
            'is_public' => true,
        ]);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('Test post content', $post->content);
        $this->assertTrue($post->is_public);
    }

    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    public function test_post_can_be_liked(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->assertFalse($post->isLikedBy($user->id));

        $post->likes()->create(['user_id' => $user->id]);

        $this->assertTrue($post->isLikedBy($user->id));
        $this->assertEquals(1, $post->getLikeCount());
    }
}
```

#### Feature Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_post(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'content' => 'Test post content',
            'type' => 'text',
            'is_public' => true,
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'content' => 'Test post content',
                        'type' => 'text',
                        'is_public' => true,
                    ]
                ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Test post content',
        ]);
    }

    public function test_user_can_upload_image_with_post(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->postJson('/api/posts', [
            'content' => 'Post with image',
            'type' => 'image',
            'media' => [$file],
        ]);

        $response->assertStatus(201);
        
        $post = Post::latest()->first();
        $this->assertNotEmpty($post->media);
        
        Storage::disk('public')->assertExists('posts/' . basename($post->media[0]));
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson('/api/posts', [
            'content' => 'Test post',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_like_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'liked' => true,
                        'like_count' => 1,
                    ]
                ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }
}
```

#### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test class
php artisan test --filter PostTest

# Run with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

### Frontend Testing

#### Component Testing

```jsx
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import PostCard from '../components/posts/PostCard';
import { AuthProvider } from '../contexts/AuthContext';

// Test utilities
const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  return ({ children }) => (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AuthProvider>
          {children}
        </AuthProvider>
      </BrowserRouter>
    </QueryClientProvider>
  );
};

const mockPost = {
  id: 1,
  content: 'Test post content',
  type: 'text',
  media: [],
  created_at: '2024-01-01T00:00:00Z',
  like_count: 5,
  comment_count: 2,
  user: {
    id: 1,
    name: 'John Doe',
    profile: {
      username: 'johndoe',
      avatar_url: 'https://example.com/avatar.jpg',
    },
  },
};

describe('PostCard', () => {
  test('renders post content correctly', () => {
    render(
      <PostCard post={mockPost} onLike={() => {}} onComment={() => {}} />,
      { wrapper: createWrapper() }
    );

    expect(screen.getByText('Test post content')).toBeInTheDocument();
    expect(screen.getByText('John Doe')).toBeInTheDocument();
    expect(screen.getByText('@johndoe')).toBeInTheDocument();
  });

  test('calls onLike when like button is clicked', async () => {
    const mockOnLike = jest.fn();
    
    render(
      <PostCard post={mockPost} onLike={mockOnLike} onComment={() => {}} />,
      { wrapper: createWrapper() }
    );

    const likeButton = screen.getByLabelText('Like post');
    fireEvent.click(likeButton);

    await waitFor(() => {
      expect(mockOnLike).toHaveBeenCalledWith(mockPost.id);
    });
  });

  test('displays like and comment counts', () => {
    render(
      <PostCard post={mockPost} onLike={() => {}} onComment={() => {}} />,
      { wrapper: createWrapper() }
    );

    expect(screen.getByText('5')).toBeInTheDocument(); // like count
    expect(screen.getByText('2')).toBeInTheDocument(); // comment count
  });
});
```

#### Integration Testing

```jsx
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { rest } from 'msw';
import { setupServer } from 'msw/node';
import Dashboard from '../components/Dashboard';

// Mock server
const server = setupServer(
  rest.get('/api/posts', (req, res, ctx) => {
    return res(
      ctx.json({
        success: true,
        data: {
          data: [
            {
              id: 1,
              content: 'Test post',
              user: { id: 1, name: 'John Doe' },
            },
          ],
        },
      })
    );
  }),

  rest.post('/api/posts', (req, res, ctx) => {
    return res(
      ctx.json({
        success: true,
        data: {
          id: 2,
          content: 'New post',
          user: { id: 1, name: 'John Doe' },
        },
      })
    );
  })
);

beforeAll(() => server.listen());
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

describe('Dashboard Integration', () => {
  test('loads and displays posts', async () => {
    render(<Dashboard />, { wrapper: createWrapper() });

    await waitFor(() => {
      expect(screen.getByText('Test post')).toBeInTheDocument();
    });
  });

  test('creates new post', async () => {
    render(<Dashboard />, { wrapper: createWrapper() });

    // Open create post modal
    fireEvent.click(screen.getByText('Create Post'));

    // Fill form
    fireEvent.change(screen.getByPlaceholderText('What\'s on your mind?'), {
      target: { value: 'New post content' },
    });

    // Submit
    fireEvent.click(screen.getByText('Post'));

    await waitFor(() => {
      expect(screen.getByText('New post')).toBeInTheDocument();
    });
  });
});
```

#### Run Frontend Tests

```bash
cd social_react

# Run all tests
npm test

# Run tests in watch mode
npm test -- --watch

# Run tests with coverage
npm test -- --coverage

# Run specific test file
npm test PostCard.test.jsx
```

## Debugging

### Backend Debugging

#### Laravel Telescope

```bash
# Install Telescope (development only)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Add to `.env`:

```env
TELESCOPE_ENABLED=true
```

#### Debug Bar

```bash
# Install Debug Bar
composer require barryvdh/laravel-debugbar --dev
```

#### Logging

```php
// In your controllers or services
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => $user->id]);
Log::warning('Invalid login attempt', ['email' => $email]);
Log::error('API error', ['error' => $exception->getMessage()]);

// Custom log channels
Log::channel('custom')->info('Custom log message');
```

#### Artisan Commands for Debugging

```bash
# Check routes
php artisan route:list

# Check configuration
php artisan config:show

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Check queue jobs
php artisan queue:monitor

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Frontend Debugging

#### React Developer Tools

Install browser extensions:
- React Developer Tools
- Redux DevTools (if using Redux)

#### Console Debugging

```jsx
// Debug component props
const PostCard = ({ post, onLike }) => {
  console.log('PostCard props:', { post, onLike });
  
  useEffect(() => {
    console.log('PostCard mounted with post:', post.id);
    
    return () => {
      console.log('PostCard unmounted');
    };
  }, [post.id]);

  // Rest of component
};

// Debug API calls
const api = axios.create({
  baseURL: '/api',
});

api.interceptors.request.use((config) => {
  console.log('API Request:', config);
  return config;
});

api.interceptors.response.use(
  (response) => {
    console.log('API Response:', response);
    return response;
  },
  (error) => {
    console.error('API Error:', error);
    return Promise.reject(error);
  }
);
```

#### Performance Profiling

```jsx
import { Profiler } from 'react';

const onRenderCallback = (id, phase, actualDuration, baseDuration, startTime, commitTime) => {
  console.log('Profiler:', {
    id,
    phase,
    actualDuration,
    baseDuration,
    startTime,
    commitTime,
  });
};

function App() {
  return (
    <Profiler id="App" onRender={onRenderCallback}>
      {/* Your app components */}
    </Profiler>
  );
}
```

## Performance Guidelines

### Backend Performance

#### Database Optimization

```php
// Use eager loading to avoid N+1 queries
$posts = Post::with(['user.profile', 'likes', 'comments.user'])
               ->latest()
               ->paginate(10);

// Use select to limit fields
$users = User::select(['id', 'name', 'email'])
              ->where('active', true)
              ->get();

// Use indexes for frequent queries
Schema::table('posts', function (Blueprint $table) {
    $table->index(['user_id', 'created_at']);
    $table->index(['is_public', 'created_at']);
});

// Use database transactions for related operations
DB::transaction(function () {
    $post = Post::create($postData);
    $post->tags()->attach($tagIds);
    $user->increment('post_count');
});
```

#### Caching

```php
use Illuminate\Support\Facades\Cache;

// Cache expensive operations
$posts = Cache::remember('user.posts.' . $userId, 300, function () use ($userId) {
    return Post::where('user_id', $userId)
               ->with('user.profile')
               ->latest()
               ->get();
});

// Cache with tags for easier invalidation
Cache::tags(['posts', 'user:' . $userId])->put('user.posts.' . $userId, $posts, 300);

// Invalidate cache
Cache::tags(['posts'])->flush();
```

#### Queue Jobs

```php
// Use jobs for time-consuming tasks
class ProcessUploadedImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Post $post,
        private string $imagePath
    ) {}

    public function handle(): void
    {
        // Process image in background
        $resizedImage = ImageProcessor::resize($this->imagePath);
        $this->post->update(['processed_image' => $resizedImage]);
    }
}

// Dispatch job
ProcessUploadedImage::dispatch($post, $imagePath);
```

### Frontend Performance

#### Component Optimization

```jsx
import React, { memo, useMemo, useCallback } from 'react';

// Memoize expensive components
const PostCard = memo(({ post, onLike, onComment }) => {
  // Memoize expensive calculations
  const formattedDate = useMemo(() => {
    return formatDistanceToNow(new Date(post.created_at));
  }, [post.created_at]);

  // Memoize callbacks to prevent unnecessary re-renders
  const handleLike = useCallback(() => {
    onLike(post.id);
  }, [post.id, onLike]);

  return (
    <div className="post-card">
      {/* Component content */}
    </div>
  );
}, (prevProps, nextProps) => {
  // Custom comparison function
  return prevProps.post.id === nextProps.post.id &&
         prevProps.post.like_count === nextProps.post.like_count;
});

// Use React.lazy for code splitting
const Dashboard = lazy(() => import('./components/Dashboard'));
const Profile = lazy(() => import('./components/Profile'));

function App() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/profile" element={<Profile />} />
      </Routes>
    </Suspense>
  );
}
```

#### State Management Optimization

```jsx
// Use React Query for server state
const { data: posts, isLoading, error } = useQuery({
  queryKey: ['posts', page],
  queryFn: () => fetchPosts(page),
  staleTime: 5 * 60 * 1000, // 5 minutes
  cacheTime: 10 * 60 * 1000, // 10 minutes
});

// Optimize re-renders with proper dependencies
const PostList = ({ userId }) => {
  const { data: posts } = useQuery({
    queryKey: ['posts', userId],
    queryFn: () => fetchUserPosts(userId),
    enabled: !!userId, // Only run when userId exists
  });

  // Use useCallback for stable references
  const handlePostLike = useCallback((postId) => {
    likeMutation.mutate(postId);
  }, [likeMutation]);

  return (
    <div>
      {posts?.map(post => (
        <PostCard
          key={post.id}
          post={post}
          onLike={handlePostLike}
        />
      ))}
    </div>
  );
};
```

## Security Guidelines

### Backend Security

#### Input Validation

```php
// Use Form Requests for validation
class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Post::class);
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'type' => 'required|in:text,image,video,link',
            'media.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov|max:10240',
            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Post content is required.',
            'media.*.mimes' => 'Only image and video files are allowed.',
        ];
    }
}

// Use in controller
public function store(CreatePostRequest $request)
{
    // Request is already validated
    $validated = $request->validated();
    
    $post = Post::create([
        'user_id' => $request->user()->id,
        ...$validated,
    ]);

    return response()->json(['success' => true, 'data' => $post]);
}
```

#### Authorization

```php
// Use Policies for authorization
class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return $post->is_public || $user->id === $post->user_id;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}

// Use in controller
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);
    
    // Update logic
}
```

#### File Upload Security

```php
class FileUploadService
{
    public function uploadImage(UploadedFile $file, string $directory): string
    {
        // Validate file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new InvalidArgumentException('Invalid file type');
        }

        // Validate file size
        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
            throw new InvalidArgumentException('File too large');
        }

        // Generate secure filename
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs($directory, $filename, 'public');
        
        return $path;
    }
}
```

### Frontend Security

#### API Security

```jsx
// Secure API configuration
const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add CSRF token if available
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
  api.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// Secure token handling
const tokenManager = {
  get: () => localStorage.getItem('token'),
  set: (token) => {
    if (token) {
      localStorage.setItem('token', token);
    }
  },
  remove: () => {
    localStorage.removeItem('token');
  },
  isValid: (token) => {
    if (!token) return false;
    
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp > Date.now() / 1000;
    } catch {
      return false;
    }
  },
};
```

#### Input Sanitization

```jsx
import DOMPurify from 'dompurify';

// Sanitize HTML content
const SafeHTML = ({ content }) => {
  const sanitizedContent = DOMPurify.sanitize(content, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'a', 'br'],
    ALLOWED_ATTR: ['href'],
  });

  return (
    <div dangerouslySetInnerHTML={{ __html: sanitizedContent }} />
  );
};

// Validate and sanitize form inputs
const CreatePostForm = () => {
  const [content, setContent] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Client-side validation
    if (!content.trim()) {
      toast.error('Content is required');
      return;
    }

    if (content.length > 5000) {
      toast.error('Content too long');
      return;
    }

    // Sanitize content
    const sanitizedContent = content.trim();
    
    // Submit to API
    submitPost({ content: sanitizedContent });
  };

  return (
    <form onSubmit={handleSubmit}>
      <textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        maxLength={5000}
        placeholder="What's on your mind?"
      />
      <button type="submit">Post</button>
    </form>
  );
};
```

## Contributing

### Getting Started

1. **Fork the Repository**
   ```bash
   # Fork on GitHub, then clone your fork
   git clone https://github.com/your-username/amksocial.git
   cd amksocial
   
   # Add upstream remote
   git remote add upstream https://github.com/original-owner/amksocial.git
   ```

2. **Set Up Development Environment**
   Follow the [Development Environment Setup](#development-environment-setup) section.

3. **Create Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

### Making Changes

1. **Write Tests**
   - Add unit tests for new functions/methods
   - Add feature tests for new API endpoints
   - Add component tests for new React components

2. **Follow Coding Standards**
   - Use ESLint for JavaScript
   - Follow PSR-12 for PHP
   - Use meaningful variable and function names
   - Add appropriate comments and documentation

3. **Commit Changes**
   ```bash
   git add .
   git commit -m "feat(scope): description of changes"
   ```

4. **Keep Branch Updated**
   ```bash
   git fetch upstream
   git rebase upstream/develop
   ```

### Submitting Changes

1. **Push to Your Fork**
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create Pull Request**
   - Use the PR template
   - Include description of changes
   - Link to related issues
   - Add screenshots for UI changes

3. **Address Review Feedback**
   - Make requested changes
   - Push additional commits
   - Re-request review when ready

### Pull Request Template

```markdown
## Description
Brief description of what this PR does.

## Changes Made
- [ ] Added new feature X
- [ ] Fixed bug Y
- [ ] Updated documentation

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Manual testing completed

## Screenshots (if applicable)
Before/after screenshots for UI changes.

## Related Issues
Closes #123
References #456

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Tests pass locally
- [ ] Documentation updated
```

## Code Review Process

### Review Guidelines

#### For Reviewers

1. **Code Quality**
   - Check for adherence to coding standards
   - Look for potential bugs or edge cases
   - Verify error handling
   - Check for security vulnerabilities

2. **Performance**
   - Review database queries for efficiency
   - Check for unnecessary API calls
   - Look for memory leaks in React components
   - Verify proper caching implementation

3. **Testing**
   - Ensure adequate test coverage
   - Verify tests are meaningful and not just for coverage
   - Check that tests actually test the intended behavior

4. **Documentation**
   - Verify inline comments are helpful
   - Check that README and docs are updated
   - Ensure API documentation is current

#### For Authors

1. **Before Submitting**
   - Run all tests locally
   - Check code formatting
   - Review your own changes
   - Test edge cases manually

2. **Responding to Reviews**
   - Address all feedback promptly
   - Ask questions if feedback is unclear
   - Make requested changes or explain why not
   - Re-request review when ready

### Review Checklist

**Code Quality:**
- [ ] Code follows established patterns
- [ ] Error handling is appropriate
- [ ] No hardcoded values or magic numbers
- [ ] Proper logging is in place
- [ ] Security best practices followed

**Performance:**
- [ ] Database queries are optimized
- [ ] Proper caching is used
- [ ] No unnecessary API calls
- [ ] React components are optimized

**Testing:**
- [ ] Unit tests cover new functionality
- [ ] Integration tests verify workflow
- [ ] Edge cases are tested
- [ ] Tests are maintainable

**Documentation:**
- [ ] Code is self-documenting
- [ ] Complex logic is explained
- [ ] API documentation is updated
- [ ] README reflects changes

This development guide provides comprehensive coverage of the development workflow and standards for AMKSocial. Following these guidelines will help maintain code quality and ensure smooth collaboration among team members.
