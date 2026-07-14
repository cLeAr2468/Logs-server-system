# 🚀 Railway Deployment Steps

## ✅ Prerequisites Completed

The following files have been created and configured for deployment:

1. ✅ `nixpacks.toml` - Railway build configuration (PHP 8.2)
2. ✅ `railway.json` - Railway deployment settings
3. ✅ `.env.production` - Production environment template
4. ✅ `Procfile` - Process configuration (Heroku compatible)
5. ✅ `config/cors.php` - CORS updated with Vercel support

---

## 📋 Step-by-Step Deployment

### **STEP 1: Prepare Git Repository** (5 minutes)

```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server

# Check git status
git status

# Ensure .env is ignored (should already be in .gitignore)
# Add all deployment files
git add nixpacks.toml railway.json .env.production Procfile config/cors.php
git commit -m "Add Railway deployment configuration"

# If not already pushed to GitHub:
# git remote add origin https://github.com/YOUR_USERNAME/logs-server.git
# git branch -M main
git push origin main
```

---

### **STEP 2: Deploy to Railway** (10 minutes)

#### A. Create Railway Project

1. Visit: https://railway.app
2. Click **"Login"** → Sign in with GitHub
3. Click **"+ New Project"**
4. Select **"Deploy from GitHub repo"**
5. Choose your `logs-server` repository
6. Railway will start building automatically

#### B. Add MySQL Database

1. In your Railway project dashboard
2. Click **"+ New"** button
3. Select **"Database"**
4. Choose **"Add MySQL"**
5. Wait for database to provision (~30 seconds)

#### C. Configure Environment Variables

1. Click on your **Laravel service** (not the database)
2. Go to **"Variables"** tab
3. Click **"RAW Editor"** button
4. Paste the following (Railway will auto-link database):

```env
APP_NAME=NWSSU Logs System
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://your-app-name.up.railway.app

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_PRIVATE_URL_HOST}}
DB_PORT=${{MySQL.MYSQL_PRIVATE_URL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=reyesjerald638@gmail.com
MAIL_PASSWORD=ajltlgteiravwtkr
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@logssystem.com
MAIL_FROM_NAME=NWSSU Logs System

FRONTEND_URL=
CLIENT_URL=

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error
BCRYPT_ROUNDS=12
```

4. Click **"Save"**

#### D. Generate APP_KEY

1. Wait for deployment to complete (check "Deployments" tab)
2. Once running, go to your service → **"Settings"** tab
3. Scroll to **"Service"** section
4. Click **"Connect"** or find deployment logs
5. Look for **"View Logs"** or **"Open Shell"**
6. In the Railway shell/console, run:

```bash
php artisan key:generate --show
```

7. Copy the output (example: `base64:abc123xyz...`)
8. Go back to **"Variables"** tab
9. Find `APP_KEY` and paste the generated key
10. Click **"Save"**

#### E. Generate Public Domain

1. Go to **"Settings"** tab
2. Find **"Networking"** section
3. Click **"Generate Domain"**
4. Copy your new URL (e.g., `https://logs-server-production.up.railway.app`)
5. Go back to **"Variables"** tab
6. Update `APP_URL` with your Railway domain
7. Click **"Save"**

#### F. Run Database Migrations

After deployment completes:

1. Open Railway console/shell again
2. Run these commands:

```bash
# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Check health
php artisan about
```

---

### **STEP 3: Update Frontend URLs** (After frontend deployment)

Once you deploy your frontends to Vercel:

1. Go to Railway → Your service → **"Variables"**
2. Update these variables:

```env
FRONTEND_URL=https://your-admin-frontend.vercel.app
CLIENT_URL=https://your-client-frontend.vercel.app
```

3. Add these to the allowed origins list (already configured in cors.php)

---

### **STEP 4: Test Backend** (2 minutes)

#### Test API Endpoints:

```bash
# Health check (create this endpoint if needed)
curl https://your-railway-url.up.railway.app/api/health

# Test login endpoint
curl -X POST https://your-railway-url.up.railway.app/api/admin/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@nwssu.edu.ph\",\"password\":\"admin123\"}"
```

Or visit in browser:
- `https://your-railway-url.up.railway.app/` (should show Laravel welcome or API message)

---

## 🔧 Environment Variables Reference

### Required Variables:

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Laravel encryption key | `base64:abc123...` |
| `APP_URL` | Your Railway domain | `https://your-app.up.railway.app` |
| `DB_HOST` | Auto-filled by Railway | `${{MySQL.MYSQL_PRIVATE_URL_HOST}}` |
| `DB_PORT` | Auto-filled by Railway | `${{MySQL.MYSQL_PRIVATE_URL_PORT}}` |
| `DB_DATABASE` | Auto-filled by Railway | `${{MySQL.MYSQL_DATABASE}}` |
| `DB_USERNAME` | Auto-filled by Railway | `${{MySQL.MYSQL_USER}}` |
| `DB_PASSWORD` | Auto-filled by Railway | `${{MySQL.MYSQL_PASSWORD}}` |
| `MAIL_USERNAME` | Gmail address | `your-email@gmail.com` |
| `MAIL_PASSWORD` | Gmail app password | `16-char app password` |

### Optional Variables (update after frontend deployment):

| Variable | Description | Example |
|----------|-------------|---------|
| `FRONTEND_URL` | Admin frontend URL | `https://admin.vercel.app` |
| `CLIENT_URL` | Client frontend URL | `https://client.vercel.app` |

---

## 🐛 Troubleshooting

### Build Fails

**Error: "PHP version not found"**
- ✅ Fixed: `nixpacks.toml` specifies PHP 8.2

**Error: "Composer dependencies failed"**
```bash
# In Railway console:
composer install --no-dev --optimize-autoloader --no-interaction
```

### Database Connection Issues

**Error: "SQLSTATE[HY000] [2002] Connection refused"**
- Check MySQL service is running in Railway
- Verify environment variables use `${{MySQL.*}}` references
- Ensure both services are in the same project

**Fix:**
1. Railway → Database service → Copy connection variables
2. Railway → Laravel service → Variables → Update DB_* values

### APP_KEY Issues

**Error: "No application encryption key has been specified"**
```bash
# In Railway console:
php artisan key:generate
# OR
php artisan key:generate --show
# Then update APP_KEY variable manually
```

### Migration Errors

**Error: "Migration table not found"**
```bash
# In Railway console:
php artisan migrate:fresh --force
php artisan db:seed --force  # if you have seeders
```

### Storage/Permission Issues

**Error: "The stream or file could not be opened"**
```bash
# In Railway console:
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

### CORS Errors (from frontend)

**Error: "blocked by CORS policy"**

1. Check `config/cors.php` has correct origins
2. Update Railway variables:
```env
FRONTEND_URL=https://your-frontend.vercel.app
CLIENT_URL=https://your-client.vercel.app
```
3. Redeploy or run:
```bash
php artisan config:cache
```

---

## 📊 Deployment Checklist

Use this checklist to track your progress:

### Pre-Deployment:
- [x] Deployment files created (nixpacks.toml, railway.json, etc.)
- [ ] Code committed to Git
- [ ] Code pushed to GitHub
- [ ] .env file NOT in repository

### Railway Setup:
- [ ] Railway account created
- [ ] GitHub connected to Railway
- [ ] New project created
- [ ] Repository connected
- [ ] MySQL database added
- [ ] Environment variables configured
- [ ] APP_KEY generated
- [ ] Public domain generated
- [ ] APP_URL updated with domain

### Database:
- [ ] Migrations run successfully
- [ ] Storage linked
- [ ] Seeder run (if applicable)
- [ ] Test data created (if needed)

### Testing:
- [ ] Backend URL accessible
- [ ] API endpoints responding
- [ ] Database queries working
- [ ] Email sending tested
- [ ] File uploads working

### Post-Deployment:
- [ ] Frontend URLs added to variables
- [ ] CORS configured for frontends
- [ ] SSL certificate active (automatic)
- [ ] Custom domain configured (optional)

---

## 💰 Railway Pricing

**Free Tier:**
- $5 in usage credits per month
- ~500 execution hours
- Perfect for development/student projects
- No credit card required to start

**Upgrade Options:**
- Starter: $5/month + usage
- Team: $20/month + usage
- Only upgrade when needed!

---

## 🎯 Quick Commands Reference

```bash
# Check application status
php artisan about

# Clear all caches
php artisan optimize:clear

# Cache everything
php artisan optimize

# View logs
tail -f storage/logs/laravel.log

# Check database connection
php artisan db:show

# Run migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback --force

# Fresh migration (WARNING: drops all tables)
php artisan migrate:fresh --force

# Generate new APP_KEY
php artisan key:generate --show
```

---

## 🔗 Important Links

- **Railway Dashboard:** https://railway.app/dashboard
- **Railway Docs:** https://docs.railway.app
- **Laravel Deployment:** https://laravel.com/docs/deployment
- **Nixpacks Docs:** https://nixpacks.com/docs

---

## 📞 Next Steps

After successful backend deployment:

1. **Deploy Frontend (Admin):**
   - Follow `Transact-logs-system` Vercel deployment
   - Update `.env` with Railway API URL
   
2. **Deploy Frontend (Client):**
   - Follow `Client-Module` Vercel deployment  
   - Update `.env` with Railway API URL

3. **Connect Everything:**
   - Update `FRONTEND_URL` and `CLIENT_URL` in Railway
   - Test end-to-end functionality
   - Create admin accounts
   - Import master list

---

## ✅ Success Indicators

You'll know deployment succeeded when:

✅ Railway build completes without errors
✅ Service is "Active" in Railway dashboard
✅ Generated domain is accessible
✅ API endpoints return responses (not 404)
✅ Database queries execute successfully
✅ Logs show no critical errors
✅ `php artisan about` shows correct environment

---

*Your backend is now ready for production! 🎉*

**Your Railway URL:** `https://[your-service-name].up.railway.app`

**Save this URL** - you'll need it for frontend deployment!
