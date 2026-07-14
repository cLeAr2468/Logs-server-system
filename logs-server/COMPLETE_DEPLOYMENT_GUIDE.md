# Complete Deployment Guide - Backend + Frontend Connection

## 🎯 OVERVIEW

This guide will help you:
1. ✅ Deploy Laravel Backend to Railway (FREE)
2. ✅ Deploy Frontend to Vercel (FREE)
3. ✅ Connect Frontend to Backend
4. ✅ Test everything works

**Total Time:** ~30 minutes

---

## 📋 PART 1: DEPLOY BACKEND (Railway)

### **Step 1: Prepare Your Backend** (5 minutes)

#### A. Update nixpacks.toml (PHP 8.2)
✅ Already done!

#### B. Remove .env from Git
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server

# Check if .env is in .gitignore
type .gitignore | findstr .env

# If not there, add it
echo .env >> .gitignore

# Remove .env from git tracking
git rm --cached .env
git commit -m "Remove .env from repository"
```

#### C. Push to GitHub
```bash
# If not initialized
git init
git add .
git commit -m "Ready for deployment"

# Create GitHub repo and push
git remote add origin https://github.com/YOUR_USERNAME/logs-server.git
git branch -M main
git push -u origin main
```

---

### **Step 2: Deploy to Railway** (10 minutes)

#### A. Sign Up & Create Project
1. Go to: https://railway.app
2. Click **"Start a New Project"**
3. Sign in with GitHub
4. Click **"Deploy from GitHub repo"**
5. Select your `logs-server` repository
6. Click **"Deploy Now"**

#### B. Add MySQL Database
1. In your project, click **"+ New"**
2. Select **"Database"**
3. Choose **"Add MySQL"**
4. Wait for provisioning (~1 minute)

#### C. Set Root Directory
1. Click on your **Laravel service** (Logs-server-system)
2. Go to **"Settings"** tab
3. Find **"Root Directory"** or **"Service Settings"**
4. Enter: `logs-server`
5. Save

#### D. Add Environment Variables
1. Click **"Variables"** tab
2. Add these variables:

```env
APP_NAME=NWSSU Logs System
APP_ENV=production
APP_DEBUG=false
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=reyesjerald638@gmail.com
MAIL_PASSWORD=ajltlgteiravwtkr
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@logssystem.com
MAIL_FROM_NAME=NWSSU Logs System
```

#### E. Generate APP_KEY
1. Wait for deployment to finish
2. Click **"Deployments"** tab
3. Click on latest deployment
4. Once running, go to **"Settings"** → **"Deploy"**
5. Open **Console/Shell**
6. Run:
```bash
php artisan key:generate --show
```
7. Copy the output (e.g., `base64:abc123...`)
8. Go to **"Variables"** tab
9. Update `APP_KEY` with the copied value

#### F. Get Your Backend URL
1. Go to **"Settings"** → **"Networking"**
2. Click **"Generate Domain"**
3. Copy your URL (e.g., `https://logs-server-production.up.railway.app`)
4. Update `APP_URL` in variables with this URL

#### G. Run Migrations
1. Open console again
2. Run:
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
```

---

## 📋 PART 2: DEPLOY FRONTEND (Vercel)

### **Option A: Transact-logs-system (Admin/Staff)**

#### Step 1: Update API URL
```bash
cd c:\Users\User\Desktop\Transact-logs-system\logs-system
```

Update `.env` file:
```env
VITE_API_URL=https://your-railway-url.up.railway.app/api
```

Example:
```env
VITE_API_URL=https://logs-server-production.up.railway.app/api
```

#### Step 2: Test Locally
```bash
npm run dev
```
- Try logging in with admin account
- Check if API calls work

#### Step 3: Build & Deploy
```bash
# Build for production
npm run build

# Create vercel.json
```

Create `vercel.json`:
```json
{
  "rewrites": [{ "source": "/(.*)", "destination": "/index.html" }],
  "buildCommand": "npm run build",
  "outputDirectory": "dist"
}
```

#### Step 4: Deploy to Vercel
```bash
# Install Vercel CLI
npm install -g vercel

# Login
vercel login

# Deploy
vercel --prod
```

Or use Vercel Dashboard:
1. Go to: https://vercel.com
2. Click **"Add New Project"**
3. Import from Git
4. Select your repository
5. Set **Framework Preset:** Vite
6. Set **Root Directory:** `logs-system`
7. Add environment variable:
   - Name: `VITE_API_URL`
   - Value: `https://your-railway-url.up.railway.app/api`
8. Click **"Deploy"**

---

### **Option B: Client-Module**

Same steps as above, but for Client-Module:

```bash
cd c:\Users\User\Desktop\Client-Module\logs-system
```

Update `.env`:
```env
VITE_API_URL=https://your-railway-url.up.railway.app/api
```

Deploy to Vercel with root directory: `logs-system`

---

## 📋 PART 3: CONNECT BACKEND TO FRONTEND

### **Step 1: Update CORS in Backend**

Update `config/cors.php` on Railway:

1. Open Railway console
2. Edit `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'https://your-admin-frontend.vercel.app',
        'https://your-client-frontend.vercel.app',
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

Or use wildcard (less secure but easier):
```php
'allowed_origins' => ['*'],
```

### **Step 2: Add Frontend URLs to Railway Variables**

```env
FRONTEND_URL=https://your-admin-frontend.vercel.app
CLIENT_URL=https://your-client-frontend.vercel.app
```

---

## 📋 PART 4: TESTING

### **Test Backend:**
```bash
# Health check
curl https://your-railway-url.up.railway.app/api/health

# Test login endpoint
curl -X POST https://your-railway-url.up.railway.app/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@nwssu.edu.ph","password":"admin"}'
```

### **Test Frontend:**
1. Visit your Vercel URL
2. Try logging in
3. Check browser console for errors
4. Verify API calls are going to Railway URL

---

## 📊 ARCHITECTURE DIAGRAM

```
┌─────────────────────────────────────────────┐
│                                             │
│  CLIENT BROWSERS                            │
│                                             │
└──────┬────────────────────┬─────────────────┘
       │                    │
       │                    │
┌──────▼─────────────┐  ┌──▼──────────────────┐
│                    │  │                     │
│  ADMIN FRONTEND    │  │  CLIENT FRONTEND    │
│  (Vercel)          │  │  (Vercel)           │
│                    │  │                     │
│  transact-logs     │  │  client-module      │
│  -system           │  │                     │
│                    │  │                     │
└──────┬─────────────┘  └──┬──────────────────┘
       │                   │
       │                   │
       │  API Calls        │  API Calls
       │  HTTPS            │  HTTPS
       │                   │
       └───────┬───────────┘
               │
               │
        ┌──────▼──────────────────┐
        │                         │
        │  LARAVEL BACKEND        │
        │  (Railway)              │
        │                         │
        │  /api/admin/*           │
        │  /api/users/*           │
        │  /api/public/*          │
        │                         │
        └──────┬──────────────────┘
               │
               │
        ┌──────▼──────────────────┐
        │                         │
        │  MYSQL DATABASE         │
        │  (Railway)              │
        │                         │
        └─────────────────────────┘
```

---

## 🔗 URLS SUMMARY

After deployment, you'll have:

```
BACKEND (Railway):
https://logs-server-production.up.railway.app

ADMIN FRONTEND (Vercel):
https://transact-logs-system.vercel.app

CLIENT FRONTEND (Vercel):
https://client-module.vercel.app

DATABASE (Railway):
Automatically connected via environment variables
```

---

## 🔧 ENVIRONMENT VARIABLES SUMMARY

### **Backend (Railway):**
```env
APP_NAME=NWSSU Logs System
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://your-railway-url.up.railway.app

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls

FRONTEND_URL=https://your-admin-frontend.vercel.app
CLIENT_URL=https://your-client-frontend.vercel.app
```

### **Frontend (Vercel):**
```env
# Both frontends
VITE_API_URL=https://your-railway-url.up.railway.app/api
```

---

## 🐛 TROUBLESHOOTING

### **Backend Issues:**

**1. Build Failed - PHP Version**
- ✅ Fixed: `nixpacks.toml` uses PHP 8.2

**2. Database Connection Failed**
- Check MySQL service is running
- Verify `${{MySQL.MYSQL_HOST}}` variables

**3. 500 Internal Server Error**
- Check Railway logs
- Set `APP_DEBUG=true` temporarily
- Run `php artisan config:clear`

### **Frontend Issues:**

**1. CORS Error**
- Update `config/cors.php`
- Add frontend URL to `allowed_origins`

**2. API Calls Failing**
- Check `VITE_API_URL` is correct
- Must end with `/api`
- Must use HTTPS

**3. 404 on Refresh**
- Add `vercel.json` with rewrites

---

## ✅ DEPLOYMENT CHECKLIST

### **Backend (Railway):**
- [ ] Code pushed to GitHub
- [ ] Railway project created
- [ ] MySQL database added
- [ ] Root directory set to `logs-server`
- [ ] Environment variables added
- [ ] APP_KEY generated
- [ ] Domain generated
- [ ] Migrations run
- [ ] Storage link created
- [ ] Test API endpoints work

### **Frontend (Vercel):**
- [ ] `.env` updated with Railway URL
- [ ] `vercel.json` created
- [ ] Tested locally
- [ ] Deployed to Vercel
- [ ] Environment variable added
- [ ] Test login works
- [ ] Test API calls work

### **Connection:**
- [ ] CORS configured
- [ ] Frontend URLs added to backend
- [ ] End-to-end testing complete

---

## 💰 COST BREAKDOWN

### **Railway (Backend):**
- FREE: $5 credit/month
- Enough for: ~500 hours
- Perfect for student projects ✅

### **Vercel (Frontend):**
- FREE: Unlimited for personal projects
- 100GB bandwidth/month
- Perfect for student projects ✅

### **Total Cost:**
**$0/month** for both! 🎉

---

## 🎉 SUCCESS INDICATORS

You'll know everything works when:

✅ Railway backend URL responds
✅ API endpoints return data
✅ Vercel frontend loads
✅ Login works from frontend
✅ Data displays correctly
✅ Email notifications send
✅ File uploads work

---

## 📞 SUPPORT LINKS

- Railway Docs: https://docs.railway.app
- Vercel Docs: https://vercel.com/docs
- Laravel Deployment: https://laravel.com/docs/deployment
- Railway Discord: https://discord.gg/railway

---

## 🚀 QUICK START COMMANDS

```bash
# Backend
cd c:\xampp\htdocs\Logs-server-system\logs-server
git add .
git commit -m "Deploy to Railway"
git push origin main
# Then deploy via Railway dashboard

# Frontend (Admin)
cd c:\Users\User\Desktop\Transact-logs-system\logs-system
vercel --prod

# Frontend (Client)
cd c:\Users\User\Desktop\Client-Module\logs-system
vercel --prod
```

---

*Follow this guide step-by-step for successful deployment!*
