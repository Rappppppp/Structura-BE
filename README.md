# Structura Backend API

Enterprise-grade project management backend API built with Laravel 12 and PHP 8.4. Manages clients, projects, invoices, tasks, team members, and project timelines with role-based access control and comprehensive REST endpoints.

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Build Status](https://img.shields.io/badge/Status-Active-brightgreen?style=for-the-badge)

</div>

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Environment Setup](#environment-setup)
- [Database](#database)
- [Authentication](#authentication)
- [API Documentation](#api-documentation)
  - [Auth Endpoints](#auth-endpoints)
  - [Public Endpoints](#public-endpoints)
  - [Admin Endpoints](#admin-endpoints)
- [Request Examples](#request-examples)
- [Response Format](#response-format)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### Core Functionality
- **User Management** — role-based users (admin, project_manager, architect, engineer)
- **Client Management** — client profiles, contact info, project tracking, financial metrics
- **Project Management** — project creation, status tracking, progress calculation, budget management
- **Invoice Management** — billing, payment tracking, overdue detection, late fee calculation
- **Task Management** — task assignment, priority/status workflow, time tracking
- **Team Members** — team profiles, role assignments, project participation
- **Project Roles** — user assignment to projects with role-based responsibilities
- **Communications** — chat rooms and messaging for project teams
- **Timeline Events** — project milestones and important dates

### Authentication & Security
- **Sanctum Token-Based Auth** — secure API token authentication
- **Rate Limiting** — 5 login attempts per 15 minutes per IP
- **Strong Password Validation** — requires uppercase, lowercase, numbers, symbols
- **Email Verification** — optional email verification on registration
- **Soft Deletes** — safe record archiving
- **Role-Based Access Control** — admin-only endpoints with Gate authorization

### Data Integrity
- **Database Transactions** — atomic operations for multi-step creates
- **Foreign Key Cascades** — proper cleanup of related records
- **UUID Primary Keys** — globally unique identifiers
- **Timestamps** — automatic created_at/updated_at tracking
- **Input Validation** — enterprise-grade form requests with custom messages

---

## Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 12 |
| Language | PHP | 8.4.1 |
| Database | MySQL/PostgreSQL | Latest stable |
| Auth | Laravel Sanctum | 4 |
| Testing | PHPUnit | 11 |
| Code Style | Laravel Pint | 1 |
| ORM | Eloquent | Built-in |

---

## Installation

### Prerequisites
- PHP 8.4 or higher
- Composer
- MySQL 8+ or PostgreSQL 12+
- Git

### Clone Repository
```bash
git clone https://github.com/yourusername/structura-backend.git
cd structura-backend
```

### Install Dependencies
```bash
composer install
```

### Create Environment File
```bash
cp .env.example .env
```

### Generate Application Key
```bash
php artisan key:generate
```

---

## Environment Setup

### Database Configuration
Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=structura
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Sanctum Configuration
```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,yourdomain.com
SESSION_DOMAIN=localhost
```

### Run Migrations
```bash
php artisan migrate
```

### Seed Sample Data
```bash
php artisan db:seed
```

This creates:
- **16 users** — 1 admin, 3 project managers, 2 architects, 5 engineers, 5 regular users
- **29 clients** — mix of statuses, industries, and value levels
- Default admin: `admin@structura.local` / `password`

---

## Database

### Schema Overview

**Users Table**
- UUID primary key
- Email (unique)
- Password (hashed)
- Roles: user, admin, project_manager, architect, engineer
- Phone, company, email verification

**Clients Table**
- UUID primary key
- Industry classification
- Contact person, email, phone, location
- Active projects count, total value
- Status: active, review, completed, on-hold
- Account owner (FK → users)
- Soft deletes

**Projects Table**
- UUID primary key
- Name, description, status
- Client relationship (FK)
- Progress (0-100), budget decimal
- Deadline timestamp
- Soft deletes

**Invoices Table**
- UUID primary key
- Invoice ID (human-friendly number)
- Amount, contract value (decimal)
- Status: pending, paid, overdue
- Due date, paid timestamp
- Project & client relationships
- Soft deletes

**Tasks Table**
- UUID primary key
- Title, description
- Status: todo, in-progress, done
- Priority: low, medium, high
- Assigned user
- Project relationship
- Estimated & spent hours (decimal)
- Due timestamp
- Soft deletes

**Additional Tables**
- `team_members` — user team info and roles
- `project_user_roles` — user assignments to projects
- `chat_rooms` & `chat_messages` — team communications
- `timeline_events` — project milestones and dates

### Run Migrations
```bash
php artisan migrate
php artisan migrate:rollback  # Undo last batch
php artisan migrate:reset     # Rollback all
```

---

## Authentication

### Token-Based (Sanctum)

All requests to protected endpoints must include the Authorization header:

```
Authorization: Bearer {token}
```

### Register
**POST** `/api/auth/register`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "project_manager",
  "company": "Acme Corp",
  "phone_number": "+1-555-123-4567"
}
```

### Login
**POST** `/api/auth/login`

```json
{
  "email": "john@example.com",
  "password": "SecurePass123!",
  "remember_me": true
}
```

### Logout
**POST** `/api/auth/logout` *(requires token)*

### Get Current User
**GET** `/api/auth/me` *(requires token)*

### Refresh Token
**POST** `/api/auth/refresh` *(requires token)*

---

## API Documentation

### Response Format

All responses follow a consistent JSON structure:

#### Success Response (200, 201)
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "Example",
    "created_at": "2026-03-04T10:30:00Z"
  },
  "message": "Resource retrieved successfully"
}
```

#### Error Response (4xx, 5xx)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["Email must be unique"],
    "password": ["Password must contain symbols"]
  }
}
```

---

### Auth Endpoints

#### Register
**POST** `/api/auth/register`

| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| name | string | Yes | 2-255 chars, letters/spaces/hyphens only |
| email | string | Yes | Valid RFC email, must be unique |
| password | string | Yes | 8+ chars, mixed case, numbers, symbols |
| password_confirmation | string | Yes | Must match password |
| role | string | No | one of: user, admin, project_manager, architect, engineer |
| company | string | No | 2-255 chars |
| phone_number | string | No | 10-20 digits, +1 format supported |

**Response:** 201 Created
```json
{
  "success": true,
  "data": {
    "user": { "id": "uuid", "name": "John", "email": "john@example.com", "role": "user", ... },
    "token": "api_token_here"
  },
  "message": "Account registered successfully"
}
```

#### Login
**POST** `/api/auth/login`

| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| email | string | Yes | Valid email |
| password | string | Yes | Minimum 8 chars |
| remember_me | boolean | No | Optional flag |

**Response:** 200 OK
```json
{
  "success": true,
  "data": {
    "user": { "id": "uuid", "name": "John", ... },
    "token": "api_token_here",
    "remember_me": false
  },
  "message": "Login successful"
}
```

**Rate Limiting:** 5 attempts per 15 minutes per IP address

#### Logout
**POST** `/api/auth/logout`

**Headers:** `Authorization: Bearer {token}`

**Response:** 200 OK

---

### Public Endpoints

#### List Clients
**GET** `/api/clients`

**Response:** 200 OK
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Acme Corp",
      "industry": "Technology",
      "email": "contact@acme.com",
      "phone": "+1-555-000-1234",
      "status": "active",
      "active_projects": 3,
      "total_value": 250000,
      "created_at": "2026-03-01T10:00:00Z"
    }
  ],
  "message": "Retrieved successfully"
}
```

#### Get Client Details
**GET** `/api/clients/:id`

**Response:** 200 OK — same structure as single client above

#### List Projects
**GET** `/api/projects`

**Response:** 200 OK
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "E-Commerce Platform",
      "description": "Build modern SPA",
      "client_id": "uuid",
      "status": "in-progress",
      "progress": 65,
      "budget": 100000,
      "deadline_at": "2026-06-30T23:59:59Z",
      "created_at": "2026-02-01T10:00:00Z"
    }
  ]
}
```

#### Get Project Details
**GET** `/api/projects/:id`

#### List Invoices
**GET** `/api/invoices`

#### Get Invoice Details
**GET** `/api/invoices/:id`

#### List Tasks
**GET** `/api/tasks`

#### Get Task Details
**GET** `/api/tasks/:id`

---

### Admin Endpoints

All admin endpoints require:
- **Header:** `Authorization: Bearer {token}`
- **Gate:** User must have `role === 'admin'`

#### User Management
**GET** `/api/admin/users` — List all users

**POST** `/api/admin/users` — Create user
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "architect"
}
```

**PUT/PATCH** `/api/admin/users/:id` — Update user

**DELETE** `/api/admin/users/:id` — Delete user

#### Client Management
**GET** `/api/admin/clients` — List all clients

**POST** `/api/admin/clients` — Create client
```json
{
  "name": "TechStart Inc",
  "industry": "Technology",
  "contact_person": "Jane Doe",
  "email": "jane@techstart.com",
  "phone": "+1-555-987-6543",
  "location": "San Francisco, CA",
  "account_owner_id": "user_uuid"
}
```

**PUT/PATCH** `/api/admin/clients/:id` — Update client

**DELETE** `/api/admin/clients/:id` — Soft delete client

#### Project Management
**POST** `/api/admin/projects` — Create project
```json
{
  "name": "Mobile App Redesign",
  "description": "Redesign mobile experience",
  "client_id": "client_uuid",
  "budget": 50000,
  "deadline_at": "2026-05-30"
}
```

**PUT/PATCH** `/api/admin/projects/:id` — Update project

**DELETE** `/api/admin/projects/:id` — Soft delete project

#### Invoice Management
**POST** `/api/admin/invoices` — Create invoice
```json
{
  "invoice_id": "INV-001",
  "project_id": "project_uuid",
  "client_id": "client_uuid",
  "amount": 25000,
  "contract_value": 50000,
  "status": "pending",
  "due_date": "2026-04-04"
}
```

#### Task Management
**POST** `/api/admin/tasks` — Create task
```json
{
  "title": "Design database schema",
  "description": "Plan and design database",
  "project_id": "project_uuid",
  "assigned_to": "user_uuid",
  "status": "todo",
  "priority": "high",
  "due_at": "2026-03-15"
}
```

---

## Request Examples

### Using cURL

#### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "role": "user"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123!"
  }'
```

#### Get Current User
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### List Clients
```bash
curl -X GET http://localhost:8000/api/clients
```

#### Create Client (Admin)
```bash
curl -X POST http://localhost:8000/api/admin/clients \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Acme Corp",
    "industry": "Technology",
    "contact_person": "John Smith",
    "email": "john@acme.com",
    "phone": "+1-555-123-4567",
    "location": "New York, NY",
    "account_owner_id": "user_uuid"
  }'
```

### Using JavaScript/Fetch

```javascript
// Register
const response = await fetch('http://localhost:8000/api/auth/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com',
    password: 'SecurePass123!',
    password_confirmation: 'SecurePass123!',
    role: 'user'
  })
});

const { data } = await response.json();
const token = data.token;

// Get current user with token
const userResponse = await fetch('http://localhost:8000/api/auth/me', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const userData = await userResponse.json();
console.log(userData.data.user);
```

---

## Error Handling

### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["This email address is already registered."],
    "password": ["Password must contain at least one special character (!@#$%^&*)."]
  }
}
```

### Authentication Errors (401)
```json
{
  "success": false,
  "message": "The provided credentials are incorrect.",
  "errors": { "email": ["The provided credentials are incorrect."] }
}
```

### Authorization Errors (403)
```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": {}
}
```

### Rate Limiting (429)
```json
{
  "success": false,
  "message": "Too many login attempts. Please try again later.",
  "errors": { "email": ["Too many login attempts..."] }
}
```

### Server Errors (500)
```json
{
  "success": false,
  "message": "Server error occurred",
  "errors": {}
}
```

---

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test
```bash
php artisan test tests/Feature/AuthControllerTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Database Seeding for Tests
```bash
php artisan migrate:fresh --seed
```

---

## Code Formatting

### Run Pint (Schema Formatter)
```bash
vendor/bin/pint
```

### Check Code Style
```bash
vendor/bin/pint --test
```

---

## Project Structure

```
structura-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ClientController.php
│   │   │   │   ├── ProjectController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── TaskController.php
│   │   │   │   └── Api/Admin/*.php
│   │   ├── Requests/
│   │   │   ├── RegisterRequest.php
│   │   │   ├── LoginRequest.php
│   │   │   └── *Request.php
│   │   └── Resources/
│   │       └── *Resource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Client.php
│   │   ├── Project.php
│   │   ├── Invoice.php
│   │   ├── Task.php
│   │   └── *.php
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── routes/
│   ├── api.php
│   └── web.php
└── tests/
    ├── Feature/
    └── Unit/
```

---

## Troubleshooting

### CORS Issues
Ensure `SANCTUM_STATEFUL_DOMAINS` in `.env` includes your frontend domain.

### Token Not Working
- Verify token is included in `Authorization: Bearer {token}` header
- Check token hasn't expired (Sanctum tokens don't expire by default)
- Ensure user still exists and isn't soft-deleted

### Database Connection Errors
```bash
php artisan config:cache
php artisan cache:clear
php artisan migrate --force
```

### Seeder Issues
```bash
php artisan migrate:fresh --seed
```

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes and commit: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a pull request

Please ensure all tests pass:
```bash
php artisan test
vendor/bin/pint
```

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## Support

For issues, questions, or feature requests, please open an issue on GitHub.

Need help? Check the [Laravel Documentation](https://laravel.com/docs) or reach out to the team.

---

<div align="center">

**Built with ❤️ using Laravel 12**

</div>

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
