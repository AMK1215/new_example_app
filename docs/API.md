# API Documentation

This document provides comprehensive information about the AMKSocial API endpoints, request/response formats, and usage examples.

## Base URL

```
Production: https://luckymillion.online/api
Development: http://localhost:8000/api
```

## Authentication

The API uses Laravel Sanctum for authentication with Bearer tokens.

### Headers Required
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Token Management
- Tokens are returned on successful login/registration
- Tokens should be stored securely (localStorage/sessionStorage)
- Include token in Authorization header for protected routes

## Response Format

All API responses follow this standard format:

```json
{
  "success": true|false,
  "message": "Response message",
  "data": {
    // Response data
  },
  "errors": {
    // Validation errors (if any)
  }
}
```

## Authentication Endpoints

### Register User
**POST** `/register`

Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "username": "johndoe" // optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "profile": {
        "id": 1,
        "username": "johndoe",
        "bio": null,
        "avatar": null,
        "cover_photo": null
      }
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### Login User
**POST** `/login`

Authenticate user and return access token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** Same as registration response

### Logout User
**POST** `/logout`

Revoke the current access token.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### Get Current User
**GET** `/me`

Get current authenticated user's information.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "profile": {
        "id": 1,
        "username": "johndoe",
        "bio": "Software Developer",
        "avatar_url": "https://example.com/storage/avatars/1.jpg",
        "cover_photo_url": "https://example.com/storage/covers/1.jpg"
      }
    }
  }
}
```

## Posts Endpoints

### Get Posts Feed
**GET** `/posts`

Get paginated list of public posts.

**Query Parameters:**
- `page` (optional): Page number for pagination

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "content": "This is my first post!",
        "type": "text",
        "media": [],
        "media_urls": [],
        "is_public": true,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "like_count": 5,
        "comment_count": 2,
        "user": {
          "id": 1,
          "name": "John Doe",
          "profile": {
            "username": "johndoe",
            "avatar_url": "https://example.com/storage/avatars/1.jpg"
          }
        },
        "likes": [],
        "comments": []
      }
    ],
    "last_page": 10,
    "per_page": 10,
    "total": 95
  }
}
```

### Create Post
**POST** `/posts`

Create a new post with optional media attachments.

**Headers:** 
- Authorization required
- Content-Type: multipart/form-data (when uploading files)

**Request Body (multipart/form-data):**
```
content: "Check out this amazing photo!"
type: "image" // text, image, video, link
is_public: true
media[0]: [File] // Image/video file
media[1]: [File] // Additional files
```

**Response:**
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "id": 2,
    "content": "Check out this amazing photo!",
    "type": "image",
    "media": ["posts/abc123.jpg"],
    "media_urls": ["https://example.com/storage/posts/abc123.jpg"],
    "is_public": true,
    "created_at": "2024-01-15T11:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe"
    }
  }
}
```

### Get Specific Post
**GET** `/posts/{id}`

Get details of a specific post.

**Headers:** Authorization required

**Response:** Same format as individual post in feed

### Update Post
**PUT** `/posts/{id}`

Update an existing post (only by the post owner).

**Headers:** Authorization required

**Request Body:**
```json
{
  "content": "Updated post content",
  "is_public": false
}
```

### Delete Post
**DELETE** `/posts/{id}`

Delete a post (only by the post owner).

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

### Like/Unlike Post
**POST** `/posts/{id}/like`

Toggle like status on a post.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "message": "Post liked" | "Post unliked",
  "data": {
    "liked": true|false,
    "like_count": 6
  }
}
```

### Get User Posts
**GET** `/users/{userId}/posts`

Get posts by a specific user.

**Headers:** Authorization required

**Response:** Same format as posts feed

## Comments Endpoints

### Get Post Comments
**GET** `/posts/{postId}/comments`

Get comments for a specific post.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "content": "Great post!",
      "created_at": "2024-01-15T11:30:00.000000Z",
      "like_count": 2,
      "user": {
        "id": 2,
        "name": "Jane Smith",
        "profile": {
          "avatar_url": "https://example.com/storage/avatars/2.jpg"
        }
      },
      "replies": [
        {
          "id": 2,
          "content": "Thanks!",
          "parent_id": 1,
          "created_at": "2024-01-15T11:35:00.000000Z",
          "user": {
            "id": 1,
            "name": "John Doe"
          }
        }
      ]
    }
  ]
}
```

### Create Comment
**POST** `/posts/{postId}/comments`

Add a comment to a post.

**Headers:** Authorization required

**Request Body:**
```json
{
  "content": "This is a great post!",
  "parent_id": null // optional, for replies
}
```

### Update Comment
**PUT** `/comments/{commentId}`

Update a comment (only by the comment author).

**Headers:** Authorization required

**Request Body:**
```json
{
  "content": "Updated comment content"
}
```

### Delete Comment
**DELETE** `/comments/{commentId}`

Delete a comment (only by the comment author).

**Headers:** Authorization required

### Like Comment
**POST** `/comments/{commentId}/like`

Toggle like status on a comment.

**Headers:** Authorization required

## Profile Endpoints

### Get Current User Profile
**GET** `/profiles`

Get the current user's profile information.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "username": "johndoe",
    "bio": "Software Developer passionate about technology",
    "location": "San Francisco, CA",
    "website": "https://johndoe.dev",
    "avatar_url": "https://example.com/storage/avatars/1.jpg",
    "cover_photo_url": "https://example.com/storage/covers/1.jpg",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

### Get All Users
**GET** `/profiles/users`

Get list of all other users (excluding current user).

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Jane Smith",
      "profile": {
        "username": "janesmith",
        "bio": "Designer and photographer",
        "avatar_url": "https://example.com/storage/avatars/2.jpg"
      }
    }
  ]
}
```

### Get Specific User Profile
**GET** `/profiles/{userId}`

Get profile of a specific user.

**Headers:** Authorization required

**Response:** Same format as current user profile

### Update Profile
**PUT** `/profiles`

Update the current user's profile.

**Headers:** 
- Authorization required
- Content-Type: multipart/form-data (when uploading files)

**Request Body (multipart/form-data):**
```
username: "newerusername"
bio: "Updated bio information"
location: "New York, NY"
website: "https://newwebsite.com"
avatar: [File] // Image file for avatar
cover_photo: [File] // Image file for cover
```

## Friendship Endpoints

### Get Friends List
**GET** `/friends`

Get list of current user's friends.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "friend_id": 2,
      "status": "accepted",
      "accepted_at": "2024-01-10T15:30:00.000000Z",
      "friend": {
        "id": 2,
        "name": "Jane Smith",
        "profile": {
          "username": "janesmith",
          "avatar_url": "https://example.com/storage/avatars/2.jpg"
        }
      }
    }
  ]
}
```

### Send Friend Request
**POST** `/friends/{userId}`

Send a friend request to another user.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "message": "Friend request sent successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "friend_id": 3,
    "status": "pending",
    "created_at": "2024-01-15T12:00:00.000000Z"
  }
}
```

### Respond to Friend Request
**PUT** `/friendships/{friendshipId}`

Accept or reject a friend request.

**Headers:** Authorization required

**Request Body:**
```json
{
  "action": "accept" // or "reject"
}
```

### Remove Friendship
**DELETE** `/friendships/{friendshipId}`

Remove an existing friendship.

**Headers:** Authorization required

### Get Pending Requests
**GET** `/friendships/pending`

Get pending friend requests (received by current user).

**Headers:** Authorization required

### Get Friendship Status
**GET** `/friendships/status/{userId}`

Get friendship status with a specific user.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "accepted" | "pending" | "none",
    "friendship_id": 1, // if exists
    "can_send_request": true|false
  }
}
```

### Get Suggested Friends
**GET** `/friends/suggested`

Get friend suggestions based on mutual connections.

**Headers:** Authorization required

## Message Endpoints

### Get Conversations
**GET** `/conversations`

Get list of user's conversations.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "private",
      "name": null,
      "avatar": null,
      "created_at": "2024-01-10T10:00:00.000000Z",
      "users": [
        {
          "id": 1,
          "name": "John Doe",
          "profile": {
            "avatar_url": "https://example.com/storage/avatars/1.jpg"
          }
        },
        {
          "id": 2,
          "name": "Jane Smith",
          "profile": {
            "avatar_url": "https://example.com/storage/avatars/2.jpg"
          }
        }
      ],
      "latest_message": {
        "id": 5,
        "content": "Hey, how are you?",
        "created_at": "2024-01-15T14:30:00.000000Z",
        "user": {
          "id": 2,
          "name": "Jane Smith"
        }
      }
    }
  ]
}
```

### Get Conversation Messages
**GET** `/conversations/{conversationId}/messages`

Get messages from a specific conversation.

**Query Parameters:**
- `page` (optional): Page number for pagination

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "content": "Hello!",
        "type": "text",
        "media": null,
        "is_edited": false,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "user": {
          "id": 1,
          "name": "John Doe",
          "profile": {
            "avatar_url": "https://example.com/storage/avatars/1.jpg"
          }
        }
      }
    ]
  }
}
```

### Send Message
**POST** `/conversations/{conversationId}/messages`

Send a message in a conversation.

**Headers:** Authorization required

**Request Body:**
```json
{
  "content": "Hello, how are you doing?",
  "type": "text"
}
```

### Start Conversation
**POST** `/conversations/start/{userId}`

Start a new conversation with a user.

**Headers:** Authorization required

**Response:**
```json
{
  "success": true,
  "message": "Conversation created successfully",
  "data": {
    "id": 2,
    "type": "private",
    "created_at": "2024-01-15T15:00:00.000000Z",
    "users": [
      // User objects
    ]
  }
}
```

### Create Group Conversation
**POST** `/conversations`

Create a new group conversation.

**Headers:** Authorization required

**Request Body:**
```json
{
  "name": "Study Group",
  "user_ids": [2, 3, 4]
}
```

### Edit Message
**PUT** `/messages/{messageId}`

Edit a message (only by the message sender).

**Headers:** Authorization required

**Request Body:**
```json
{
  "content": "Updated message content"
}
```

### Delete Message
**DELETE** `/messages/{messageId}`

Delete a message (only by the message sender).

**Headers:** Authorization required

### Mark Conversation as Read
**POST** `/conversations/{conversationId}/read`

Mark all messages in a conversation as read.

**Headers:** Authorization required

### Typing Indicators
**POST** `/conversations/{conversationId}/typing`

Indicate that user is typing.

**Headers:** Authorization required

**POST** `/conversations/{conversationId}/stop-typing`

Stop typing indicator.

**Headers:** Authorization required

## Error Responses

### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Authentication Errors (401)
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### Authorization Errors (403)
```json
{
  "success": false,
  "message": "This action is unauthorized"
}
```

### Not Found Errors (404)
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### Server Errors (500)
```json
{
  "success": false,
  "message": "Internal server error"
}
```

## Rate Limiting

API endpoints are rate limited to prevent abuse:
- Authentication endpoints: 5 requests per minute
- General API endpoints: 60 requests per minute
- File upload endpoints: 30 requests per minute

## File Upload Specifications

### Supported File Types
**Images:**
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

**Videos:**
- MP4 (.mp4)
- AVI (.avi)
- MOV (.mov)
- WebM (.webm)

### File Size Limits
- Maximum file size: 10MB per file
- Maximum files per request: 5 files
- Total upload size limit: 50MB per request

### Upload Response Format
```json
{
  "success": true,
  "data": {
    "media": ["posts/abc123.jpg", "posts/def456.mp4"],
    "media_urls": [
      "https://example.com/storage/posts/abc123.jpg",
      "https://example.com/storage/posts/def456.mp4"
    ]
  }
}
```

## WebSocket Events

### Real-time Events
The API broadcasts the following events via WebSocket:

**Message Events:**
- `message.new` - New message in conversation
- `message.edited` - Message was edited
- `message.deleted` - Message was deleted

**Post Events:**
- `post.created` - New post published
- `post.liked` - Post was liked
- `comment.created` - New comment added

**Friendship Events:**
- `friendship.request_received` - New friend request
- `friendship.status_changed` - Friendship status updated

**User Events:**
- `user.typing` - User is typing in conversation
- `user.online` - User online status changed

### Channel Subscriptions
- `posts` - Public channel for post updates
- `user.{userId}` - Private channel for user notifications
- `conversation.{conversationId}` - Private channel for chat messages

## Example Usage

### JavaScript/Axios Example
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://luckymillion.online/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Login
const login = async (email, password) => {
  try {
    const response = await api.post('/login', { email, password });
    localStorage.setItem('token', response.data.data.token);
    return response.data;
  } catch (error) {
    console.error('Login failed:', error.response.data);
  }
};

// Create post with image
const createPost = async (content, imageFile) => {
  const formData = new FormData();
  formData.append('content', content);
  formData.append('type', 'image');
  formData.append('media[0]', imageFile);
  
  try {
    const response = await api.post('/posts', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data;
  } catch (error) {
    console.error('Post creation failed:', error.response.data);
  }
};
```

This API documentation provides comprehensive coverage of all available endpoints. For additional help or clarification, please refer to the main README or create an issue in the repository.
