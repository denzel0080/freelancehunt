# Testing Project (Clean Php+Vue.js+TypeScript)

## 🚀 Project Overview
[A modern PHP-based for tracking and filtering freelance projects.](https://github.com/freelancehunt/code-test)

## ✨ Features
- Comprehensive project filtering
- Pagination support
- Caching mechanism
- RESTful API design
- Skill and category-based project search
- DI Container
- PHPUnit Tests

## 🛠️ Technology Stack
- PHP 8.2+
- MySQL
- Redis
- GuzzleHttp
- PHPUnit
- Composer
- Docker
- php-di
- and others

## 📦 Installation

### Method 2: Docker Installation

#### Prerequisites
- Docker
- Docker Compose

#### Setup Steps
```bash
# Clone the repository
git clone https://github.com/denzel0080/freelancehunt

# Navigate to project directory
cd freelancehunt

# Copy environment configuration
cp .env.example .env

# Build and start containers
docker-compose up -d --build

# Install composer dependencies inside the container
docker-compose exec php composer install

# Run database structure creating
docker-compose exec php bash - enter to Bash inside container (/var/www/html#)
after that -> 1) php bin/setup-db.php 2) php bin/import.php

# Access the application
- API will be available at http://localhost:8080
- phpMyAdmin (database management) at http://localhost:8081

# Frontend side install
npm install

# Frontend side run local env
npm run dev
```
### Env settings
Add your values:
- DATABASE_URL=
- FREELANCEHUNT_API_KEY=

#### Docker Compose Management
```bash
# Stop containers
docker-compose down

# Rebuild containers
docker-compose up -d --build

# Access application container
docker-compose exec php bash
```

## 🔧 Configuration
Key configuration files:
- `.env`: Environment variables
- `src/config/Database.php`: Database settings
- `src/config/Container.php`: DI Container settings
- `docker-compose.yml`: Docker configuration

## 📊 API Endpoints

### Get Projects
`GET /api/projects`

#### Query Parameters
- `category`: Filter by project category
- `currency`: Filter by budget currency
- `page`: Pagination page number
- `perPage`: Number of items per page
- `sortBy`: Sort field (published_at, budget_amount)
- `sortOrder`: Sort direction (asc/desc)

## 🧪 Running Tests


### Docker Tests
```bash
docker-compose exec php ./vendor/bin/phpunit
```

## 📝 Database Schema
- `projects`: Core project information
- `employers`: Project employer details
- `skills`: Available project skills
- `project_skills`: Project-skill relationships

## 🔒 Security
- API uses bearer token authentication
- Implements input validation
- CORS protection

## 📈 Performance
- Redis caching layer
- Optimized database queries
- Pagination to limit response size

## 📜 License
MIT License

## 👥 Authors
- Denys Liubynovskyi <denys.liubynovskyi@gmail.com>

## 🐛 Issues
Report issues on GitHub Issues page