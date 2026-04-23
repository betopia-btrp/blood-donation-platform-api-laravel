# 🩸 Blood Donation Platform — REST API

A backend REST API for a community-driven blood donation platform. Built with Laravel, PostgreSQL 17, and JWT authentication with fully containerized with Docker.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [API Documentation](#api-documentation)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Authentication](#authentication)
- [Role System](#role-system)
- [Trust Score System](#trust-score-system)
- [API Endpoints](#api-endpoints)
- [Running Tests](#running-tests)
- [Team Collaboration](#team-collaboration)
- [Daily Workflow](#daily-workflow)

---

## Overview

This platform connects blood donors with recipients and NGOs. Users can search for donors by blood group and location, and register for blood collection events organized by verified NGOs.

Key things this API handles:

- Role-based access for donors, recipients, and organizations
- Donation request lifecycle with donor selection and contact reveal
- NGO event management with admin approval flow
- A trust score system that rewards reliability and penalizes no-shows
- Dummy payment records for audit purposes (payment gateway not integrated)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel |
| Language | PHP |
| Database | PostgreSQL 17 |
| Auth | JWT (php-open-source-saver/jwt-auth) |
| Web Server | Nginx (Alpine) |
| Runtime | PHP-FPM |
| Containerization | Docker + Docker Compose |
| API Docs | Scribe |
| Testing | PHPUnit (Laravel Feature Tests) |

---

## Getting Started

You need Docker and Docker Compose installed. Nothing else.

```bash
# Clone the repository
git clone https://github.com/your-org/blood-donation-api.git
cd blood-donation-api

# Copy environment file
cp src/.env.example src/.env

# Start all containers
docker compose up -d --build

# Enter the app container
docker exec -it blood_app bash

# Install dependencies
composer install

# Generate app key and JWT secret
php artisan key:generate
php artisan jwt:secret

# Run migrations and seed default data
php artisan migrate --seed

exit
```

Visit `http://localhost:8000/api/health` - if you get a success response, everything is running.

---

## Environment Variables

Copy `src/.env.example` to `src/.env` and fill in the values. The DB credentials below match the Docker Compose defaults and work out of the box locally.

```env
APP_NAME=BloodDonationAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=blood_donation
DB_USERNAME=blood_user
DB_PASSWORD=blood_pass

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

JWT_SECRET=        # auto-filled by: php artisan jwt:secret
JWT_ALGO=HS256
```

## API Documentation

Scribe generates interactive docs from the codebase automatically.

```bash
docker exec -it blood_app bash
php artisan scribe:generate
exit
```

| Resource | URL |
|---|---|
| API Docs | http://localhost:8000/docs |
| Postman Collection | http://localhost:8000/docs/collection.json |
| OpenAPI Spec | http://localhost:8000/docs/openapi.yaml |
| pgAdmin | http://localhost:5050 |

**pgAdmin credentials:**
```
Email:    admin@admin.com
Password: admin
```

**pgAdmin DB connection:**
```
Host:     postgres
Port:     5432
Database: blood_donation
Username: blood_user
Password: blood_pass
```

---

## Project Structure

```
src/
├── app/
│   ├── Http/
│   │   ├── Controllers/API/
│   │   │   ├── Auth/           → register, login, logout, me, change-password
│   │   │   ├── Admin/          → user management, events, requests, payments, reports
│   │   │   ├── Dashboard/      → user dashboard, org dashboard
│   │   │   ├── Donor/          → donor search, donor actions (accept/reject/confirm)
│   │   │   ├── DonationRequest/→ request lifecycle
│   │   │   ├── Event/          → public event discovery
│   │   │   ├── Organization/   → org profile, event management
│   │   │   └── User/           → user profile
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/           → form validation classes
│   ├── Models/                 → Eloquent models with relationships
│   └── Traits/
│       └── ApiResponse.php     → standard JSON response format
├── database/
│   ├── migrations/             → all table definitions in order
│   └── seeders/                → default roles and admin account
├── routes/
│   └── api.php                 → all API routes
└── tests/
    └── Feature/                → endpoint tests for every section
```

---

## Database Schema

```
users                       → core account (role: admin / user / organization)
user_profiles               → donor profile (blood group, location, availability)
organizations               → NGO profile (verification status, contact)
organization_documents      → uploaded docs for org verification
donation_requests           → blood requests posted by users
donation_request_recipients → donors selected per request + their response
events                      → blood collection events by orgs
event_registrations         → donor registrations per event
payments                    → payment records (dummy, for audit)
reports                     → fraud reports on users, requests, events
```

---

## Authentication

All protected routes require a JWT token in the `Authorization` header:

```
Authorization: Bearer <token>
```

Tokens are issued on registration and login. On logout, the token is blacklisted and cannot be reused.

**Register:**
```json
POST /api/auth/register
{
  "name": "Jhon Doe",
  "email": "jhon@example.com",
  "password": "password123",
  "role": "user"
}
```

**Login:**
```json
POST /api/auth/login
{
  "email": "jhon@example.com",
  "password": "password123"
}
```

The response includes a `token` field. Pass this token with every subsequent request.

---

## Role System

| Role | Description |
|---|---|
| `user` | Can donate blood, post requests, register for events |
| `organization` | NGO that creates blood collection events |
| `admin` | Full platform control |

Roles are assigned at registration. Admin accounts cannot be created via the API — seed one manually.

Organization accounts start as `pending` and must be approved by an admin before they can log in.

---

## Trust Score System

Every user profile has a `trust_score` between `0.00` and `1.00`. The system updates it automatically based on behavior — it cannot be edited manually.

| Action | Score Change |
|---|---|
| Donor confirms donation completed | +0.05 |
| Donor accepted request but never confirmed | -0.10 |
| Donor registered for event but marked absent | -0.10 |

The score is capped at `1.00` and floored at `0.00`. Donors are sorted by trust score in search results — highest trust appears first.

---

## API Endpoints

### Auth
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/change-password
```

### Profile
```
GET    /api/user/profile
PUT    /api/user/profile
GET    /api/organization/profile
PUT    /api/organization/profile
```

### Donor Discovery (public)
```
GET    /api/donors
GET    /api/donors/{id}
```

### Donation Requests
```
POST   /api/donation-requests
GET    /api/donation-requests/{id}
DELETE /api/donation-requests/{id}
GET    /api/donation-requests/{id}/acceptances
POST   /api/donation-requests/{id}/confirm-payment
GET    /api/donation-requests/{id}/donors-revealed
POST   /api/donation-requests/{id}/complete
POST   /api/donation-requests/{id}/report
```

### Donor Actions
```
GET    /api/my/incoming-requests
GET    /api/my/incoming-requests/{id}
POST   /api/my/incoming-requests/{id}/accept
POST   /api/my/incoming-requests/{id}/reject
POST   /api/my/incoming-requests/{id}/confirm-donated
```

### Events (public)
```
GET    /api/events
GET    /api/events/{id}
POST   /api/events/{id}/register
DELETE /api/events/{id}/register
POST   /api/events/{id}/report
```

### Personal Dashboard
```
GET    /api/dashboard/my-requests
GET    /api/dashboard/my-requests/{id}
GET    /api/dashboard/incoming-requests
GET    /api/dashboard/my-events
GET    /api/dashboard/my-donations
GET    /api/dashboard/stats
```

### Organization Dashboard
```
GET    /api/dashboard/org/events
GET    /api/dashboard/org/events/{id}
GET    /api/dashboard/org/stats
POST   /api/dashboard/org/events
PUT    /api/dashboard/org/events/{id}
DELETE /api/dashboard/org/events/{id}
GET    /api/dashboard/org/events/{id}/registrations
PUT    /api/dashboard/org/events/{id}/attendance
```

### Admin
```
GET    /api/admin/stats
GET    /api/admin/users
GET    /api/admin/users/{id}
PUT    /api/admin/users/{id}/activate
PUT    /api/admin/users/{id}/deactivate
DELETE /api/admin/users/{id}
PUT    /api/admin/users/{id}/approve-org
PUT    /api/admin/users/{id}/reject-org
GET    /api/admin/events
GET    /api/admin/events/{id}
PUT    /api/admin/events/{id}/approve
PUT    /api/admin/events/{id}/cancel
GET    /api/admin/donation-requests
GET    /api/admin/donation-requests/{id}
GET    /api/admin/payments
GET    /api/admin/payments/{id}
GET    /api/admin/reports
GET    /api/admin/reports/{id}
PUT    /api/admin/reports/{id}/review
PUT    /api/admin/reports/{id}/resolve
```

---

## Running Tests

A separate test database is used so tests never touch  development data.

**First time setup:**
```bash
# Create test database
docker exec -it blood_postgres psql -U blood_user -d blood_donation -c "CREATE DATABASE blood_donation_test;"
```

**Run all tests:**
```bash
docker exec -it blood_app bash
php artisan test
exit
```

**Run a specific test file:**
```bash
php artisan test --filter AuthTest
php artisan test --filter DonationRequestTest
```

**Run with full output:**
```bash
php artisan test --verbose
```

Current test coverage: 67 tests, 137 assertions across 9 test files covering every section of the API.

---

## Team Collaboration

When a teammate clones the repo and wants to get the project running:

```bash
git clone https://github.com/your-org/blood-donation-api.git
cd blood-donation-api

cp src/.env.example src/.env
# Fill in JWT_SECRET or run jwt:secret after setup

docker compose up -d --build

docker exec -it blood_app bash
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
exit
```

That's it. No local PHP or PostgreSQL installation needed.

When someone adds a new package, everyone else syncs with:

```bash
docker exec -it blood_app bash
composer install
exit
```

---

## Daily Workflow

```bash
# Start (morning)
docker compose up -d

# Enter container for artisan commands
docker exec -it blood_app bash

# Common artisan commands
php artisan migrate                 # run new migrations
php artisan migrate:fresh --seed    # wipe and rebuild (dev only)
php artisan route:list              # see all routes
php artisan config:clear            # clear config cache
php artisan scribe:generate         # rebuild API docs
php artisan test                    # run test suite

# Stop (end of day)
docker compose down
```

---

## Services

| Service | URL | Purpose |
|---|---|---|
| API | http://localhost:8000 | Main application |
| Docs | http://localhost:8000/docs | API documentation |
| pgAdmin | http://localhost:5050 | Database GUI |
| PostgreSQL | localhost:5432 | Direct DB access |

---

## Default Admin Account

Seeded automatically when you run `php artisan db:seed`.

```
Email:    admin@blood.com
Password: password123
```

---

## License

MIT
