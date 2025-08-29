# AMKSocial - Social Media Platform

A modern, full-featured social media application built with Laravel 12 and React 19, featuring real-time chat, posts, friendships, and media sharing.

## 🌟 Features

### Core Social Features
- **User Authentication** - Secure registration and login with JWT tokens
- **User Profiles** - Customizable profiles with avatars and cover photos
- **Posts & Media** - Create text, image, and video posts with rich media support
- **Like & Comment System** - Interactive engagement with nested comments
- **Friend System** - Send/accept friend requests with smart suggestions
- **Real-time Chat** - Private messaging and group conversations
- **Live Notifications** - Instant updates for all social interactions

### Technical Features
- **Real-time Updates** - WebSocket integration with Laravel Reverb
- **Responsive Design** - Mobile-first design with Tailwind CSS
- **File Upload** - Secure media handling with Laravel Storage
- **API-First** - RESTful API with comprehensive endpoints
- **Modern Stack** - Latest Laravel 12 and React 19

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React App     │    │  Laravel API    │    │   Database      │
│                 │    │                 │    │                 │
│ • Components    │◄──►│ • Controllers   │◄──►│ • Users         │
│ • Hooks         │    │ • Models        │    │ • Posts         │
│ • Context       │    │ • Events        │    │ • Messages      │
│ • Services      │    │ • Broadcasting  │    │ • Friendships   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         ▲                       ▲
         │                       │
         └───────────────────────┘
              WebSocket (Reverb)
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm/yarn
- Database (MySQL/PostgreSQL/SQLite)

### Backend Setup (Laravel)

1. **Clone and Install Dependencies**
   ```bash
   git clone <repository-url>
   cd new_example_app
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure Database**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=amksocial
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Configure Broadcasting (Reverb)**
   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=your_app_id
   REVERB_APP_KEY=your_app_key
   REVERB_APP_SECRET=your_app_secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```

5. **Run Migrations and Seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start Laravel Services**
   ```bash
   # Terminal 1: Web server
   php artisan serve
   
   # Terminal 2: Queue worker
   php artisan queue:work
   
   # Terminal 3: WebSocket server
   php artisan reverb:start
   ```

### Frontend Setup (React)

1. **Navigate to React Directory**
   ```bash
   cd social_react
   ```

2. **Install Dependencies**
   ```bash
   npm install
   ```

3. **Configure API Endpoint**
   ```javascript
   // src/services/api.js
   export const api = axios.create({
     baseURL: 'http://localhost:8000/api', // Update for your setup
   });
   ```

4. **Start Development Server**
   ```bash
   npm run dev
   ```

### Access the Application
- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000
- **WebSocket**: ws://localhost:8080

## 📖 Documentation

- [API Documentation](docs/API.md) - Complete API reference
- [Frontend Guide](docs/FRONTEND.md) - React components and hooks
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment
- [Development Guide](docs/DEVELOPMENT.md) - Contributing and development workflow

## 🛠️ Tech Stack

**Backend**
- Laravel 12 (PHP 8.2+)
- Laravel Sanctum (Authentication)
- Laravel Reverb (WebSockets)
- Eloquent ORM
- Laravel Storage (File handling)

**Frontend**
- React 19
- Vite (Build tool)
- React Router v6 (Routing)
- TanStack Query (State management)
- Tailwind CSS v4 (Styling)
- Laravel Echo (WebSocket client)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

---

Made with ❤️ by the AMKSocial Team