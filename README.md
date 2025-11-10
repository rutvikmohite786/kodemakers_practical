<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Blog Management API (Laravel + Sanctum)

### Requirements
- PHP 8.2+
- Composer
- MySQL

### Setup
1. Copy `.env.example` to `.env` and set your MySQL credentials:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=...`
   - `DB_PORT=3306`
   - `DB_DATABASE=...`
   - `DB_USERNAME=...`
   - `DB_PASSWORD=...`
2. Install dependencies:
   - `composer install`
3. Generate app key:
   - `php artisan key:generate`
4. Run migrations and seeders:
   - `php artisan migrate --seed`
5. Link storage for images:
   - `php artisan storage:link`

Seed creates:
- Admin: `admin@example.com` / `password`
- Demo: `demo@example.com` / `password`
- Sample blogs with likes and images in `storage/app/public/blogs`

### Authentication (Sanctum)
- Login to obtain token:
  - `POST /api/auth/login`
  - Body: `{ "email": "admin@example.com", "password": "password" }`
  - Response: `{ token, token_type: "Bearer", user }`
- Logout:
  - `POST /api/auth/logout` (Authorization header required)

Use header on protected routes:
`Authorization: Bearer <token>`

### Blog APIs
- List blogs (pagination, search, sorting):
  - `GET /api/blogs`
  - Query:
    - `page` (default 1)
    - `per_page` (default 10, max 100)
    - `search` (searches `title` and `description`)
    - `sort=latest|most_liked` (default `latest`)
  - Each item includes: `likes_count`, `is_liked` for the logged-in user, and `author`.
- Create blog:
  - `POST /api/blogs`
  - Multipart form-data:
    - `title` (string, required, max 255)
    - `description` (string, required)
    - `image` (file image: jpg/jpeg/png/gif/webp, max 5MB)
- Edit blog:
  - `PUT /api/blogs/{blog}` or `PATCH /api/blogs/{blog}`
  - Multipart form-data (any of):
    - `title` (string, max 255)
    - `description` (string)
    - `image` (file image: jpg/jpeg/png/gif/webp, max 5MB)
  - Note: only the author can update/delete their blog.
- Delete blog:
  - `DELETE /api/blogs/{blog}`
  - Note: only the author can delete their blog.
- Like toggle:
  - `POST /api/blogs/{blog}/like-toggle`
  - Toggles like by current user; response includes `is_liked` and `likes_count`.

### Tech Notes
- Auth: Laravel Sanctum personal access tokens
- Models:
  - `Blog` has `user`, `likes` (polymorphic), soft deletes, image path
  - `Like` is polymorphic (`likeable`) and references `user`
  - `User` uses `HasApiTokens` and relations for blogs and likes
- Migrations:
  - `blogs` table with fulltext on `title, description`
  - `likes` table with unique `(user_id, likeable_id, likeable_type)`
- Filesystem:
  - Public disk used; URLs via `Storage::disk('public')->url(...)`

### Quick Route Reference
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/blogs`
- `POST /api/blogs`
- `PUT|PATCH /api/blogs/{blog}`
- `DELETE /api/blogs/{blog}`
- `POST /api/blogs/{blog}/like-toggle`
