# Admin Table Setup Instructions

## Overview
The system has been updated to use a database table for admin authentication instead of hardcoded credentials.

## Changes Made

### 1. Database Changes
- Created `admins` table with the following structure:
  - `id` (primary key)
  - `fname` (first name)
  - `mname` (middle name, nullable)
  - `lname` (last name)
  - `email` (unique)
  - `password` (hashed)
  - `status` (default: 'Active')
  - `timestamps`

### 2. Model Created
- `App\Models\Admin` - Eloquent model for admin authentication

### 3. Controller Updated
- `AdminController.php` now authenticates against the `admins` table
- Removed hardcoded default admin credentials (`admin@nwssu.edu.ph` / `admin`)
- Login now checks:
  1. Admins table first
  2. Staff table second
  3. Returns proper token and user data

## Setup Instructions

### Step 1: Start XAMPP
Make sure your MySQL database server is running through XAMPP Control Panel.

### Step 2: Run Migration
Open a terminal and run:
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan migrate
```

This will create the `admins` table in your database.

### Step 3: Seed Admin Account
Run the seeder to create the default admin account:
```bash
php artisan db:seed --class=AdminSeeder
```

## Default Admin Credentials

**Email:** `admin@nwssu.edu.ph`  
**Password:** `admin123`

⚠️ **Important:** Change this password after first login!

## Testing

1. Start your Laravel server
2. Try logging in to the Transact-logs-system with:
   - Email: `admin@nwssu.edu.ph`
   - Password: `admin123`
3. The system should authenticate successfully and redirect to the dashboard

## Adding More Admins

You can add more admin accounts directly to the database:

```sql
INSERT INTO admins (fname, mname, lname, email, password, status, created_at, updated_at)
VALUES ('John', 'M', 'Doe', 'john.doe@nwssu.edu.ph', '$2y$10$...', 'Active', NOW(), NOW());
```

Or you can create a command/endpoint to register new admins through the application.

## Security Notes

1. Passwords are hashed using Laravel's bcrypt hashing
2. Authentication uses Laravel Sanctum tokens
3. Never store plain text passwords
4. Change the default password immediately after first login
5. Consider adding email verification for new admin accounts

## Troubleshooting

### Database Connection Error
If you get a connection error when running migrations:
1. Make sure XAMPP MySQL is running
2. Check your `.env` file database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=logs-server
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### Admin Already Exists
If the seeder says "Admin account already exists", you can reset it:
```sql
DELETE FROM admins WHERE email = 'admin@nwssu.edu.ph';
```
Then run the seeder again.

## Files Modified/Created

### Created:
- `database/migrations/2026_08_30_004143_create_admins_table.php`
- `app/Models/Admin.php`
- `database/seeders/AdminSeeder.php`

### Modified:
- `app/Http/Controllers/AdminController.php` - Updated login logic

### Frontend:
- Login form in Transact-logs-system now authenticates against the database
- No hardcoded credentials remain in the system
