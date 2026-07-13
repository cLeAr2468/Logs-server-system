# Railway Deployment Guide - Laravel Backend

## Overview
Complete guide to deploy your Laravel backend (Logs-server-system) to Railway.app.

**Railway Features:**
- ✅ Free tier available ($5 free credit/month)
- ✅ Automatic HTTPS
- ✅ MySQL database included
- ✅ Easy environment variables
- ✅ Automatic deployments from Git

---

## 📋 PREREQUISITES

### 1. **Railway Account**
- Sign up at: https://railway.app
- Login with GitHub (recommended)

### 2. **Git Repository**
Your Laravel project must be in a Git repository:
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
git init
git add .
git commit -m "Initial commit"
```

### 3. **GitHub Repository** (Recommended)
- Create a new repository on GitHub
- Push your code:
```bash
git remote add origin https://github.com/YOUR_USERNAME/logs-server.git
git branch -M main
git push -u origin main
```

---

## 🚀 DEPLOYMENT STEPS

### Step 1: Create Railway Project

1. Go to https://railway.app
2. Click **"Start a New Project"**
3. Select **"Deploy from GitHub repo"**
4. Authorize Railway to access your GitHub
5. Select your `logs-server` repository
6. Click **"Deploy Now"**

### Step 2: Add MySQL Database

1. In your Railway project dashboard
2. Click **"+ New"**
3. Select **"Database"**
4. Choose **"Add MySQL"**
5. Railway will automatically provision a MySQL database

### Step 3: Configure Environment Variables

1. Click on your Laravel service (not database)
2. Go to **"Variables"** tab
3. Click **"+ New Variable"**
4. Add each variable below:

```env
# App Configuration
APP_NAME="NWSSU Logs System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app

# Generate this key (see below)
APP_KEY=base64:YOUR_GENERATED_KEY

# Database (from Railway MySQL service)
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

# Mail Configuration (use your email provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Frontend URL (update with your frontend deployment)
FRONTEND_URL=http://localhost:5173
```

**Note:** Railway automatically references the MySQL service variables using `${{MySQL.VARIABLE_NAME}}`

---

## 📝 REQUIRED FILES

### 1. **Create Procfile**

Create a file named `Procfile` in your project root:

```bash
# File: c:\xampp\htdocs\Logs-server-system\logs-server\Procfile
```

```
web: php artisan config:cache && php artisan route:cache && php artisan migrate --force && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=$PORT
```

### 2. **Create nixpacks.toml**

Create a file named `nixpacks.toml` in your project root:

```bash
# File: c:\xampp\htdocs\Logs-server-system\logs-server\nixpacks.toml
```

```toml
[phases.setup]
nixPkgs = ["php81", "php81Packages.composer"]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader"]

[phases.build]
cmds = [
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
]

[start]
cmd = "php artisan serve --host=0.0.0.0 --port=$PORT"
```

### 3. **Update .gitignore**

Make sure these are in your `.gitignore`:

```
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
```

### 4. **Create railway.json** (Optional)

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "numReplicas": 1,
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

---

## 🔑 GENERATE APP_KEY

You need to generate a Laravel application key:

**Option 1: Locally**
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan key:generate --show
```

Copy the output (e.g., `base64:abc123...`) and add it to Railway variables.

**Option 2: In Railway Console**
1. Go to your Laravel service
2. Click **"Settings"** → **"Environment"**
3. Open console
4. Run: `php artisan key:generate`

---

## 🗄️ DATABASE MIGRATION

After deployment, run migrations:

**Option 1: Automatic (in Procfile)**
Already included in Procfile: `php artisan migrate --force`

**Option 2: Manual (via Railway Console)**
1. Go to your Laravel service
2. Click **"Settings"** → **"Environment"**
3. Open console
4. Run:
```bash
php artisan migrate --force
php artisan db:seed  # if you have seeders
```

---

## 📦 STORAGE LINK

Create storage link for file uploads:

**Already included in Procfile:**
```bash
php artisan storage:link
```

Or manually in Railway console:
```bash
php artisan storage:link
```

---

## 🌐 DOMAIN & URL

### Get Your Railway URL:
1. Go to your Laravel service
2. Go to **"Settings"** → **"Networking"**
3. Click **"Generate Domain"**
4. You'll get: `https://your-app.up.railway.app`

### Update Environment Variables:
```env
APP_URL=https://your-app.up.railway.app
```

### Update Frontend API URL:
Update your frontend `.env`:
```env
# Transact-logs-system
VITE_API_URL=https://your-app.up.railway.app/api

# Client-Module
VITE_API_URL=https://your-app.up.railway.app/api
```

---

## 🔧 CORS CONFIGURATION

Update `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'https://your-frontend.vercel.app',  // Add your frontend URL
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

Or use wildcard (less secure):
```php
'allowed_origins' => ['*'],
```

---

## 📧 EMAIL CONFIGURATION

### Using Gmail:

1. **Enable 2-Factor Authentication** in your Google account
2. **Generate App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other"
   - Copy the generated password

3. **Add to Railway Variables:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # 16-character app password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="NWSSU Logs System"
```

### Using Other Providers:

**Mailtrap (Testing):**
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

**SendGrid:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
```

---

## 🚨 TROUBLESHOOTING

### Issue 1: "No application encryption key"
**Solution:**
```bash
php artisan key:generate
```
Then update APP_KEY in Railway variables.

### Issue 2: Database connection failed
**Solution:**
- Check database variables: `${{MySQL.MYSQL_HOST}}`, etc.
- Make sure MySQL service is running
- Check database credentials

### Issue 3: Storage link not working
**Solution:**
Run in Railway console:
```bash
php artisan storage:link
```

### Issue 4: 500 Internal Server Error
**Solution:**
Check logs in Railway:
1. Go to your service
2. Click **"Deployments"**
3. Click on latest deployment
4. View logs

Enable debug mode temporarily:
```env
APP_DEBUG=true
```

### Issue 5: CORS errors
**Solution:**
Update `config/cors.php`:
```php
'allowed_origins' => ['*'],
'supports_credentials' => true,
```

### Issue 6: File upload not working
**Solution:**
1. Ensure storage is linked: `php artisan storage:link`
2. Check folder permissions
3. Use Railway's persistent storage if needed

---

## 📊 MONITORING & LOGS

### View Logs:
1. Go to your service in Railway
2. Click **"Deployments"**
3. Select deployment
4. View real-time logs

### Common Log Commands:
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check app status
php artisan about
```

---

## 💰 PRICING

### Railway Free Tier:
- $5 free credit per month
- ~500 hours of runtime
- 1GB RAM
- 1GB storage

### Paid Plans:
- $5/month minimum
- Pay for what you use
- More resources available

### Cost Optimization:
- Use caching effectively
- Optimize database queries
- Use CDN for static files
- Monitor usage in Railway dashboard

---

## 🔒 SECURITY CHECKLIST

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database password is secure
- [ ] CORS properly configured
- [ ] `.env` file not in Git
- [ ] File upload validation
- [ ] Rate limiting enabled
- [ ] HTTPS enforced
- [ ] SQL injection prevention (use Eloquent)

---

## 🎯 DEPLOYMENT CHECKLIST

### Pre-Deployment:
- [ ] Code pushed to GitHub
- [ ] `.env.example` updated
- [ ] `Procfile` created
- [ ] `nixpacks.toml` created
- [ ] Database migrations tested
- [ ] File uploads work locally

### During Deployment:
- [ ] Railway project created
- [ ] MySQL database added
- [ ] Environment variables set
- [ ] APP_KEY generated
- [ ] Domain generated

### Post-Deployment:
- [ ] Migrations run successfully
- [ ] Storage link created
- [ ] Test API endpoints
- [ ] Test authentication
- [ ] Test file uploads
- [ ] Test email sending
- [ ] Update frontend API URL

---

## 📝 QUICK DEPLOYMENT COMMANDS

```bash
# 1. Initialize Git (if not done)
cd c:\xampp\htdocs\Logs-server-system\logs-server
git init
git add .
git commit -m "Initial commit"

# 2. Push to GitHub
git remote add origin https://github.com/YOUR_USERNAME/logs-server.git
git push -u origin main

# 3. In Railway Console (after deployment)
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

# 4. Test the deployment
curl https://your-app.up.railway.app/api/health
```

---

## 🌟 ALTERNATIVE: Manual Railway Setup

If you don't want to use GitHub:

1. Install Railway CLI:
```bash
npm i -g @railway/cli
```

2. Login:
```bash
railway login
```

3. Initialize:
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
railway init
```

4. Add MySQL:
```bash
railway add
```
Select MySQL.

5. Deploy:
```bash
railway up
```

---

## 🔗 USEFUL LINKS

- Railway Dashboard: https://railway.app/dashboard
- Railway Docs: https://docs.railway.app
- Laravel Deployment Docs: https://laravel.com/docs/deployment
- Railway Discord: https://discord.gg/railway

---

## 📞 SUPPORT

If you encounter issues:
1. Check Railway logs
2. Check Laravel logs: `storage/logs/laravel.log`
3. Railway Discord community
4. Laravel Discord community

---

*Follow this guide step-by-step for successful deployment!*
