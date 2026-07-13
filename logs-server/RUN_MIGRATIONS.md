# Database Migration Instructions

## Problem
The `announcements` table is missing or doesn't have the correct columns.

Error: `Column 'status' at row 1`

## Solution

### Option 1: Fresh Migration (Recommended if you don't have important data)
This will drop all tables and recreate them:

```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan migrate:fresh
```

### Option 2: Run Migrations (If table doesn't exist yet)
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan migrate
```

### Option 3: If table exists but is missing columns
If the announcements table already exists but is missing the status column, you need to:

1. Drop the table manually in phpMyAdmin or MySQL:
```sql
DROP TABLE IF EXISTS announcements;
```

2. Then run migrations:
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan migrate
```

## What Changed

The `announcements` table now has:
- `status` column: enum('draft', 'published', 'archive') with default 'draft'
- All other required columns (id, user_id, title, content, cover_image, published_at, timestamps)

## After Running Migration

Try creating an announcement again. It should work without the database error.

## Verification

Check if the table was created correctly:
```bash
php artisan tinker
```

Then in tinker:
```php
\Illuminate\Support\Facades\Schema::hasTable('announcements');
// Should return: true

\Illuminate\Support\Facades\Schema::hasColumn('announcements', 'status');
// Should return: true
```

Or check in phpMyAdmin:
- Open http://localhost/phpmyadmin
- Select your database (logs-server)
- Look for `announcements` table
- Verify it has all columns including `status`
