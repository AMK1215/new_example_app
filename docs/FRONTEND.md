# Frontend Documentation

This document covers the React frontend architecture, components, hooks, and development patterns used in AMKSocial.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Project Structure](#project-structure)
- [Core Components](#core-components)
- [Hooks and Context](#hooks-and-context)
- [State Management](#state-management)
- [Routing](#routing)
- [Styling](#styling)
- [Real-time Features](#real-time-features)
- [Development Guidelines](#development-guidelines)

## Architecture Overview

The frontend is built with React 19 and follows modern React patterns:

- **Component-based architecture** with functional components
- **Hooks** for state management and side effects
- **Context API** for global state (authentication)
- **React Query** for server state management
- **React Router** for client-side routing
- **Tailwind CSS** for styling
- **Laravel Echo** for real-time WebSocket connections

```
┌─────────────────┐
│     App.jsx     │
├─────────────────┤
│  Auth Context   │
│  Query Client   │
│  Router Setup   │
└─────────────────┘
         │
┌─────────────────┐
│  Layout.jsx     │
├─────────────────┤
│  Navigation     │
│  Sidebar        │
│  Modal Context  │
└─────────────────┘
         │
┌─────────────────┐
│  Page Components│
├─────────────────┤
│  Dashboard      │
│  Profile        │
│  Chat           │
│  Friends        │
└─────────────────┘
```

## Project Structure

```
social_react/src/
├── components/           # React components
│   ├── auth/            # Authentication components
│   ├── chat/            # Chat system components
│   ├── comments/        # Comment system components
│   ├── friendship/      # Friend management components
│   ├── layout/          # Layout and navigation
│   ├── posts/           # Post-related components
│   └── profile/         # Profile components
├── contexts/            # React contexts
│   └── AuthContext.jsx  # Authentication context
├── hooks/               # Custom React hooks
│   ├── useAuth.js       # Authentication hook
│   ├── usePosts.js      # Posts management hook
│   └── useWebSocket.js  # WebSocket connection hook
├── services/            # API and external services
│   ├── api.js           # Axios configuration
│   └── broadcasting.js  # WebSocket service
├── config/              # Configuration files
├── assets/              # Static assets
├── App.jsx              # Main application component
├── main.jsx             # Application entry point
└── index.css            # Global styles
```

## Core Components

### Layout Components

#### Layout.jsx
Main layout wrapper with navigation, sidebar, and modal management.

**Props:**
- `children` - Page content to render

**Features:**
- Responsive navigation bar
- Mobile-friendly hamburger menu
- User profile dropdown
- Real-time notification indicators
- Modal context provider

```jsx
import Layout from './components/layout/Layout';

function App() {
  return (
    <Layout>
      <Dashboard />
    </Layout>
  );
}
```

### Authentication Components

#### Login.jsx
User login form with validation and error handling.

**Features:**
- Email/password validation
- Loading states
- Error display
- Redirect after login

#### Register.jsx
User registration form with comprehensive validation.

**Features:**
- Name, email, password, username fields
- Password confirmation
- Real-time validation
- Auto-login after registration

### Dashboard Components

#### Dashboard.jsx
Main feed with infinite scroll and post creation.

**Features:**
- Infinite scroll pagination
- Real-time post updates
- Post creation modal
- Friend suggestions sidebar

**Key Hooks:**
```jsx
const {
  data,
  isLoading,
  fetchNextPage,
  hasNextPage,
  isFetchingNextPage
} = useInfiniteQuery({
  queryKey: ['posts'],
  queryFn: fetchPosts,
  getNextPageParam: (lastPage) => lastPage.nextCursor
});
```

#### CreatePost.jsx
Modal component for creating new posts with media upload.

**Props:**
- `onClose` - Function to close modal
- `onPostCreated` - Callback after successful post creation

**Features:**
- Rich text editor
- Media file upload (images/videos)
- Post type detection
- Privacy settings
- Real-time preview

### Post Components

#### PostCard.jsx
Individual post display with interactions.

**Props:**
- `post` - Post object
- `onLike` - Like/unlike callback
- `onComment` - Comment callback
- `onShare` - Share callback

**Features:**
- Media display (images/videos)
- Like/comment counts
- User information
- Interaction buttons
- Time formatting

```jsx
<PostCard
  post={post}
  onLike={handleLike}
  onComment={handleComment}
  onShare={handleShare}
/>
```

#### PostDetail.jsx
Full post view with comments section.

**Features:**
- Complete post display
- Nested comments
- Comment creation
- Comment replies
- Like/unlike functionality

### Chat Components

#### Chat.jsx
Main chat interface with conversation list and chat window.

**Features:**
- Conversation list sidebar
- Active conversation display
- Mobile-responsive layout
- Real-time message updates

#### ChatList.jsx
List of user conversations with search functionality.

**Props:**
- `onSelectConversation` - Callback when conversation is selected
- `selectedConversationId` - Currently active conversation ID

**Features:**
- Conversation search
- Unread message indicators
- Last message preview
- User online status

#### ChatWindow.jsx
Active conversation interface with message history.

**Props:**
- `conversation` - Conversation object
- `onBack` - Callback for mobile back navigation

**Features:**
- Message history with pagination
- Real-time message updates
- Message composition
- Typing indicators
- File attachments

#### FloatingChatBox.jsx
Minimizable chat widget for quick conversations.

**Features:**
- Draggable positioning
- Minimize/maximize
- Multiple conversation tabs
- Notification badges

### Profile Components

#### Profile.jsx
User profile page with posts, friends, and settings.

**Features:**
- Profile information display
- Avatar and cover photo upload
- User posts timeline
- Friend list management
- Profile editing modal

**Key Features:**
```jsx
// Profile tabs
const tabs = ['posts', 'friends', 'photos', 'about'];

// Profile editing
const [isEditing, setIsEditing] = useState(false);
const [profileData, setProfileData] = useState(null);

// File upload for avatar/cover
const handleAvatarUpload = (file) => {
  // Upload logic
};
```

### Friendship Components

#### FriendsPage.jsx
Complete friend management interface.

**Features:**
- Friend list display
- Pending requests
- Friend suggestions
- Search functionality

#### FriendshipButton.jsx
Dynamic button for friend actions based on relationship status.

**Props:**
- `userId` - Target user ID
- `initialStatus` - Current friendship status

**States:**
- Not friends: "Add Friend"
- Pending: "Request Sent"
- Friends: "Remove Friend"
- Received request: "Accept/Decline"

#### FriendsList.jsx
Display list of friends with messaging options.

**Props:**
- `friends` - Array of friend objects
- `onStartChat` - Callback to start conversation

### Comment Components

#### CommentsSection.jsx
Complete comment system for posts.

**Props:**
- `postId` - Post ID for comments
- `initialComments` - Pre-loaded comments

**Features:**
- Nested comment threads
- Comment creation/editing
- Like/unlike comments
- Comment deletion
- Real-time updates

#### CommentItem.jsx
Individual comment display with reply functionality.

**Props:**
- `comment` - Comment object
- `level` - Nesting level for styling
- `onReply` - Reply callback

#### CommentForm.jsx
Form for creating new comments or replies.

**Props:**
- `postId` - Target post ID
- `parentId` - Parent comment for replies
- `onSubmit` - Submit callback

## Hooks and Context

### AuthContext
Provides authentication state and methods throughout the app.

```jsx
const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};

// Usage
const { user, login, logout, loading } = useAuth();
```

**Methods:**
- `login(email, password)` - Authenticate user
- `register(userData)` - Register new user
- `logout()` - Sign out user
- `updateUser(userData)` - Update user information

### Custom Hooks

#### useWebSocket
Manages WebSocket connections and real-time updates.

```jsx
const useWebSocket = () => {
  const [isConnected, setIsConnected] = useState(false);
  const [echo, setEcho] = useState(null);

  useEffect(() => {
    // Initialize Laravel Echo
    const echoInstance = new Echo({
      broadcaster: 'reverb',
      // configuration
    });
    
    setEcho(echoInstance);
    setIsConnected(true);

    return () => {
      echoInstance.disconnect();
      setIsConnected(false);
    };
  }, []);

  return { echo, isConnected };
};
```

#### usePosts
Manages post-related operations and state.

```jsx
const usePosts = () => {
  const queryClient = useQueryClient();

  const createPost = useMutation({
    mutationFn: (postData) => api.post('/posts', postData),
    onSuccess: () => {
      queryClient.invalidateQueries(['posts']);
    }
  });

  const likePost = useMutation({
    mutationFn: (postId) => api.post(`/posts/${postId}/like`),
    onSuccess: () => {
      queryClient.invalidateQueries(['posts']);
    }
  });

  return { createPost, likePost };
};
```

## State Management

### React Query
Used for server state management with automatic caching and synchronization.

**Configuration:**
```jsx
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
      staleTime: 5 * 60 * 1000, // 5 minutes
      gcTime: 10 * 60 * 1000, // 10 minutes
    }
  }
});
```

**Common Queries:**
```jsx
// Posts feed
const { data: posts, isLoading } = useQuery({
  queryKey: ['posts'],
  queryFn: () => api.get('/posts')
});

// User profile
const { data: profile } = useQuery({
  queryKey: ['profile', userId],
  queryFn: () => api.get(`/profiles/${userId}`)
});

// Conversations
const { data: conversations } = useQuery({
  queryKey: ['conversations'],
  queryFn: () => api.get('/conversations')
});
```

### Local State
Component-level state using `useState` and `useReducer`.

**Common Patterns:**
```jsx
// Modal state
const [isModalOpen, setIsModalOpen] = useState(false);

// Form state
const [formData, setFormData] = useState({
  name: '',
  email: '',
  bio: ''
});

// Loading states
const [isLoading, setIsLoading] = useState(false);

// Error handling
const [error, setError] = useState(null);
```

## Routing

### Route Configuration
```jsx
<Routes>
  {/* Public Routes */}
  <Route path="/login" element={
    <PublicRoute>
      <Login />
    </PublicRoute>
  } />
  
  {/* Protected Routes */}
  <Route path="/" element={
    <ProtectedRoute>
      <Layout>
        <Dashboard />
      </Layout>
    </ProtectedRoute>
  } />
  
  <Route path="/profile/:userId?" element={
    <ProtectedRoute>
      <Layout>
        <Profile />
      </Layout>
    </ProtectedRoute>
  } />
  
  <Route path="/chat" element={
    <ProtectedRoute>
      <Layout>
        <Chat />
      </Layout>
    </ProtectedRoute>
  } />
</Routes>
```

### Route Guards
```jsx
const ProtectedRoute = ({ children }) => {
  const { user, loading } = useAuth();
  
  if (loading) {
    return <LoadingSpinner />;
  }
  
  return user ? children : <Navigate to="/login" replace />;
};

const PublicRoute = ({ children }) => {
  const { user, loading } = useAuth();
  
  if (loading) {
    return <LoadingSpinner />;
  }
  
  return user ? <Navigate to="/" replace /> : children;
};
```

### Navigation
```jsx
import { useNavigate, Link } from 'react-router-dom';

// Programmatic navigation
const navigate = useNavigate();
navigate('/profile/123');

// Declarative navigation
<Link to="/chat" className="nav-link">
  Messages
</Link>
```

## Styling

### Tailwind CSS
The app uses Tailwind CSS v4 for styling with a mobile-first approach.

**Common Patterns:**
```jsx
// Responsive design
<div className="w-full md:w-1/2 lg:w-1/3">

// Dark mode support
<div className="bg-white dark:bg-gray-800">

// Hover effects
<button className="bg-blue-500 hover:bg-blue-600 transition-colors">

// Component variants
<div className={`
  ${variant === 'primary' ? 'bg-blue-500' : 'bg-gray-500'}
  ${size === 'large' ? 'px-6 py-3' : 'px-4 py-2'}
`}>
```

### Custom CSS
Global styles in `index.css` for animations and custom utilities:

```css
/* Loading animations */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Custom scrollbars */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}
```

## Real-time Features

### WebSocket Integration
```jsx
import broadcastingService from '../services/broadcasting';

// Initialize connection
useEffect(() => {
  broadcastingService.initialize();
  
  return () => {
    broadcastingService.disconnect();
  };
}, []);

// Subscribe to channels
useEffect(() => {
  if (user) {
    // User notifications
    broadcastingService.subscribeToUserNotifications(
      user.id,
      handleNotification
    );
    
    // Posts feed
    broadcastingService.subscribeToPostsFeed(handleNewPost);
    
    // Chat messages
    if (conversationId) {
      broadcastingService.subscribeToConversation(
        conversationId,
        handleNewMessage
      );
    }
  }
}, [user, conversationId]);
```

### Real-time Updates
```jsx
// Handle new messages
const handleNewMessage = (event) => {
  const { message } = event;
  
  // Update React Query cache
  queryClient.setQueryData(['messages', conversationId], (old) => {
    return {
      ...old,
      pages: old.pages.map((page, index) => {
        if (index === 0) {
          return {
            ...page,
            data: [message, ...page.data]
          };
        }
        return page;
      })
    };
  });
  
  // Show notification if not in active conversation
  if (!isActiveConversation) {
    toast.info(`New message from ${message.user.name}`);
  }
};

// Handle new posts
const handleNewPost = (event) => {
  const { post } = event;
  
  queryClient.setQueryData(['posts'], (old) => {
    return {
      ...old,
      pages: old.pages.map((page, index) => {
        if (index === 0) {
          return {
            ...page,
            data: [post, ...page.data]
          };
        }
        return page;
      })
    };
  });
};
```

## Development Guidelines

### Component Structure
```jsx
import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { api } from '../services/api';
import toast from 'react-hot-toast';

const ComponentName = ({ prop1, prop2, onCallback }) => {
  // 1. Hooks (state, effects, queries)
  const [localState, setLocalState] = useState(null);
  
  const { data, isLoading, error } = useQuery({
    queryKey: ['key'],
    queryFn: fetchData
  });
  
  // 2. Event handlers
  const handleClick = () => {
    // Handle click
  };
  
  // 3. Effects
  useEffect(() => {
    // Side effects
  }, []);
  
  // 4. Early returns
  if (isLoading) return <LoadingSpinner />;
  if (error) return <ErrorMessage error={error} />;
  
  // 5. Render
  return (
    <div className="component-wrapper">
      {/* Component content */}
    </div>
  );
};

export default ComponentName;
```

### Error Handling
```jsx
// Error boundaries for components
const ErrorFallback = ({ error, resetErrorBoundary }) => {
  return (
    <div className="error-container">
      <h2>Something went wrong</h2>
      <pre>{error.message}</pre>
      <button onClick={resetErrorBoundary}>Try again</button>
    </div>
  );
};

// API error handling
const handleApiError = (error) => {
  const message = error.response?.data?.message || 'An error occurred';
  toast.error(message);
  console.error('API Error:', error);
};
```

### Performance Optimization
```jsx
// Memoization for expensive calculations
const expensiveValue = useMemo(() => {
  return computeExpensiveValue(data);
}, [data]);

// Callback memoization
const handleCallback = useCallback((id) => {
  onAction(id);
}, [onAction]);

// Component memoization
const MemoizedComponent = React.memo(Component);

// Virtual scrolling for large lists
import { FixedSizeList as List } from 'react-window';

const VirtualList = ({ items }) => (
  <List
    height={600}
    itemCount={items.length}
    itemSize={80}
    itemData={items}
  >
    {Row}
  </List>
);
```

### Testing Patterns
```jsx
// Component testing with React Testing Library
import { render, screen, fireEvent } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const renderWithProviders = (component) => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false }
    }
  });
  
  return render(
    <QueryClientProvider client={queryClient}>
      {component}
    </QueryClientProvider>
  );
};

test('renders component correctly', () => {
  renderWithProviders(<Component />);
  expect(screen.getByText('Expected Text')).toBeInTheDocument();
});
```

### Accessibility
```jsx
// Semantic HTML
<nav role="navigation" aria-label="Main navigation">
  <ul>
    <li>
      <Link to="/" aria-current={location.pathname === '/' ? 'page' : undefined}>
        Home
      </Link>
    </li>
  </ul>
</nav>

// ARIA labels and descriptions
<button
  aria-label="Like post"
  aria-pressed={isLiked}
  onClick={handleLike}
>
  <HeartIcon />
</button>

// Focus management
const focusElement = useRef();

useEffect(() => {
  if (isModalOpen) {
    focusElement.current?.focus();
  }
}, [isModalOpen]);
```

This documentation provides comprehensive coverage of the frontend architecture and development patterns. For additional details on specific components or features, refer to the inline code comments or create an issue in the repository.
