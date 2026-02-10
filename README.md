# Assessment MC — Laravel API

A Laravel 12 REST API with user authentication (Laravel Sanctum) and CRUD for **Users** and **Posts**. All responses use a consistent JSON shape with `success`, `message`, `data`, and `errors`.

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL/PostgreSQL via `.env`

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

For SQLite (default in `.env.example`):

```bash
touch database/database.sqlite
php artisan migrate
```

For MySQL/PostgreSQL, set `DB_*` in `.env` and run:

```bash
php artisan migrate
```

Optional: seed posts (requires users to exist):

```bash
php artisan db:seed
```

## Running the app

```bash
php artisan serve
```

API base URL: **http://localhost:8000/api** (or your `APP_URL` + `/api`).

## Authentication

The API uses **Laravel Sanctum** with Bearer tokens.

1. **Register** — `POST /api/register`
   Body: `name`, `email`, `password`, `password_confirmation`
   Returns user (no token).

2. **Login** — `POST /api/login`
   Body: `email`, `password`
   Returns `token`, `token_type` (`Bearer`), and `user`. Use the token in subsequent requests.

3. **Protected routes** — Send header:
   `Authorization: Bearer <token>`

4. **Logout** — `POST /api/logout` (authenticated)
   Revokes the current token.

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | No | Register a new user |
| POST | `/api/login` | No | Login; returns Bearer token |
| POST | `/api/logout` | Yes | Revoke current token |
| GET | `/api/users` | Yes | List users (paginated, `?per_page=15`) |
| POST | `/api/users` | Yes | Create user (admin-style) |
| GET | `/api/users/{id}` | Yes | Get user (own profile only) |
| PUT/PATCH | `/api/users/{id}` | Yes | Update user (own profile only) |
| DELETE | `/api/users/{id}` | Yes | Delete user (own account only) |
| GET | `/api/posts` | Yes | List posts (paginated, `?per_page=15`) |
| POST | `/api/posts` | Yes | Create post (author = current user) |
| GET | `/api/posts/{id}` | Yes | Get single post |
| PUT/PATCH | `/api/posts/{id}` | Yes | Update post (author only) |
| DELETE | `/api/posts/{id}` | Yes | Delete post (author only) |

## Request / Response format

**Success response:**

```json
{
  "success": true,
  "message": "Optional message",
  "data": { ... },
  "errors": null
}
```

**Error response:**

```json
{
  "success": false,
  "message": "Error message",
  "data": null,
  "errors": { ... }
}
```

Validation errors appear in `errors` (e.g. Laravel validation structure). Unauthorized/forbidden use HTTP 401 or 403 with the same shape.

## Validation (summary)

- **Register:** `name` (required), `email` (required, unique), `password` (required, min 8, confirmed).
- **Login:** `email` (required), `password` (required).
- **User (store/update):** `name`, `email` (unique where applicable), `password` (min 8, confirmed; optional on update).
- **Post (store):** `title` (required), `slug` (required, unique), `content` (required). `author_id` is set from the authenticated user.
- **Post (update):** `title`, `slug`, `content` — all optional; slug must stay unique excluding current post.

## Tech stack

- Laravel 12
- Laravel Sanctum (API tokens)
- SQLite by default (configurable via `.env`)
- Form Requests for validation
- API Resources for User and Post responses (UserResource, PostResource)
