# URsekai - Browser-Based Gaming Platform

![URsekai Logo](https://placeholder.com/wp-content/uploads/2018/10/placeholder.com-logo1.png)

## Overview

URsekai is a comprehensive browser-based gaming platform that bridges the gap between game developers and players. The name combines "UR" (your) and "SEKAI" (Japanese for "world"), representing our commitment to creating a space where developers can share their creative worlds and players can explore them without any downloads required.

## Project Structure

The project is divided into several key components:

### Backend (Laravel)
- Located in `/ursekai/laravel-app/`
- RESTful API for game management, user authentication, and data analytics
- Laravel 10.x with modern PHP architecture
- JWT-based authentication system
- MySQL database integration

### Frontend
- Located in `/frontend-v1/`
- HTML/CSS/JavaScript based interface
- Responsive design for all device types
- Express.js server for serving static content

### Developer Portal
- Located in `/frontend-v1/dev-portal/`
- Dashboard for developers to manage their games
- Analytics for tracking player engagement
- API documentation and testing tools

### Database
- SQL schema in `/database/ursekai_db.sql`
- Comprehensive data structure for users, games, achievements, and analytics

## Key Features

### For Players
- **In-browser Gaming**: Play WebGL games directly in your browser without downloads
- **Achievement System**: Earn achievements and track progress across games
- **Social Features**: Friends system, community forums, and real-time chat
- **Leaderboards**: Compete with others on global and game-specific leaderboards

### For Developers
- **Game Hosting**: Upload and manage WebGL game files seamlessly
- **Analytics Dashboard**: Track player engagement, retention, and monetization
- **API Integration**: Connect games with URsekai's backend for player data, achievements, etc.
- **Monetization Options**: Various revenue models including in-app purchases and ads

## Technology Stack

### Backend
- Laravel Framework
- PHP 8.x
- MySQL Database
- JWT Authentication
- Queue System for background tasks

### Frontend
- HTML/CSS/JavaScript
- Express.js
- Chart.js for analytics visualization
- Font Awesome icons
- Responsive design with custom CSS

## Installation and Setup

### Prerequisites
- PHP 8.0 or higher
- Composer
- Node.js and npm
- MySQL

### Backend Setup
1. Navigate to the laravel app directory:
```bash
cd ursekai/laravel-app
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create a copy of the environment file:
```bash
cp .env.example .env
```

4. Generate an application key:
```bash
php artisan key:generate
```

5. Configure your database in the `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ursekai_db
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations and seed the database:
```bash
php artisan migrate
php artisan db:seed
```

7. Start the Laravel development server:
```bash
php artisan serve
```

### Frontend Setup
1. Navigate to the frontend directory:
```bash
cd ../frontend-v1
```

2. Install Node.js dependencies:
```bash
npm install
```

3. Start the Express server:
```bash
node server.js
```

4. Access the application at `http://localhost:3000`

### Developer Portal Setup
1. Navigate to the developer portal directory:
```bash
cd dev-portal
```

2. Install dependencies:
```bash
npm install
```

3. Start the developer portal server:
```bash
node server.js
```

4. Access the developer portal at `http://localhost:3001`

## API Documentation

URsekai provides comprehensive API endpoints for game developers to integrate their games with the platform. The full API documentation is available in the Developer Portal or at `/pages/api-docs.html`.

Key endpoints include:

- **Player Stats**: `/api/player/{id}/stats`
- **Achievements**: `/api/player/{id}/achievements`
- **Game Integration**: `/api/games/{id}`
- **Leaderboards**: `/api/leaderboard/{game_id}`

## Database Schema

The database is structured to support all platform features including:

- User accounts and profiles
- Game storage and metadata
- Achievement systems
- Leaderboards
- Social interactions (friends, forums, chat)
- Analytics and tracking
- Monetization features

## Contributing

We welcome contributions to the URsekai project. Please follow our contribution guidelines in [CONTRIBUTING.md](CONTRIBUTING.md).

## License

URsekai is licensed under the [MIT License](LICENSE).

## Contact

For questions, feedback, or support, please contact us at:
- Email: support@ursekai.com
- Discord: [Join our server](https://discord.gg/ursekai)
- GitHub: [Report issues here](https://github.com/ursekai/issues)

---

© 2025 URsekai. All rights reserved.
