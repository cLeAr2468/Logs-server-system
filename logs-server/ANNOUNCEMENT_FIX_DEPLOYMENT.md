# Announcement Feature Fix - Deployment Guide

## Problem Fixed
The announcement creation was failing with a "Server Error" because:
- The `announcements` table referenced the `users` table via `user_id`
- But announcements should be created by staff members, not regular users
- Both admin and staff accounts should be able to create announcements

## Changes Made

### 1. Database Migration
**File:** `database/migrations/2026_07_19_051344_change_announcements_user_id_to_staff_id.php`

This migration:
- Renames `user_id` column to `staff_id`
- Changes the foreign key from `users` table to `staff` table
- Maintains backward compatibility with rollback capability

### 2. Announcement Model
**File:** `app/Models/Announcement.php`

Changes:
- Updated `fillable` array: `user_id` → `staff_id`
- Changed relationship from `user()` to `staff()`
- Added `user()` alias for backward compatibility

### 3. Announcement Controller
**File:** `app/Http/Controllers/AnnouncementController.php`

Changes:
- Updated all methods to use `staff_id` instead of `user_id`
- Modified `store()` method to handle both default admin and staff login
- When default admin creates announcement, it creates/gets a system staff account
- Added proper error handling and logging
- Updated eager loading: `with('user')` → `with('staff')`

### 4. Staff Model
**File:** `app/Models/Staff.php`

Added:
- `announcements()` relationship method

## Deployment Steps

### On Railway (Production)

1. **Push the code to your repository:**
   ```bash
   git add .
   git commit -m "Fix: Change announcements to use staff_id instead of user_id"
   git push origin main
   ```

2. **SSH into Railway container:**
   ```bash
   railway shell
   ```

3. **Run the migration:**
   ```bash
   php artisan migrate
   ```

   This will:
   - Rename the `user_id` column to `staff_id`
   - Update the foreign key to reference the `staff` table

4. **Verify the migration:**
   ```bash
   php artisan migrate:status
   ```

### On Local (Development)

1. **Make sure MySQL is running** (via XAMPP)

2. **Run the migration:**
   ```bash
   cd c:\xampp\htdocs\Logs-server-system\logs-server
   php artisan migrate
   ```

## How It Works Now

### For Default Admin (`admin@nwssu.edu.ph`)
When the default admin creates an announcement:
1. System checks if a staff record with email `admin@nwssu.edu.ph` exists
2. If not, it creates one with:
   - `staff_id`: ADMIN-000
   - `fname`: System
   - `lname`: Administrator
   - `email`: admin@nwssu.edu.ph
3. Uses that staff record's ID for the announcement

### For Staff Accounts
When a staff member creates an announcement:
1. System uses the authenticated staff member's ID directly
2. Announcement is linked to that staff member

## Testing

After deployment, test by:

1. **Login as admin** (`admin@nwssu.edu.ph` / `admin`)
2. **Go to Announcements page**
3. **Click "Add Announcement"**
4. **Fill in the form:**
   - Title: Test Announcement
   - Content: This is a test
   - Status: Published or Draft
   - (Optional) Upload an image
5. **Click "Save Announcement"**
6. **Should see success message** ✅

7. **Repeat test with staff account** to verify staff can also create announcements

## Rollback (If Needed)

If something goes wrong, you can rollback:

```bash
php artisan migrate:rollback --step=1
```

This will:
- Rename `staff_id` back to `user_id`
- Change foreign key back to reference `users` table

## Database Schema

### Before:
```sql
announcements
├── id
├── user_id (foreign key → users.id)
├── title
├── content
├── cover_image
├── status
├── published_at
├── created_at
└── updated_at
```

### After:
```sql
announcements
├── id
├── staff_id (foreign key → staff.id)
├── title
├── content
├── cover_image
├── status
├── published_at
├── created_at
└── updated_at
```

## Notes

- The migration is safe to run even if there's existing data
- The column rename preserves all existing data
- Foreign key constraints ensure data integrity
- Both admin and staff can create announcements seamlessly
- Error handling provides meaningful messages for debugging

## Support

If you encounter any issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check Railway logs in the dashboard
3. Verify the migration ran successfully with `php artisan migrate:status`
