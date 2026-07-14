# 🚀 Backend Deployment - Ready to Deploy!

## ✅ What's Been Set Up

Your Laravel backend is now configured and ready for Railway deployment with the following files:

### 📁 Deployment Configuration Files

| File | Purpose |
|------|---------|
| `nixpacks.toml` | Railway build configuration (PHP 8.2, npm, composer) |
| `railway.json` | Railway deployment settings and start command |
| `.env.production` | Production environment template |
| `Procfile` | Heroku-compatible process file (backup) |
| `post-deploy.sh` | Post-deployment setup script |
| `.dockerignore` | Files to exclude from Docker builds |

### 🔧 Updated Files

| File | Changes |
|------|---------|
| `config/cors.php` | Added Vercel domain support with wildcards |
| `routes/api.php` | Added `/api/health` endpoint for monitoring |

### 📊 API Endpoints

**Health Check:**
```
GET /api/health
Response: {"status":"ok","service":"NWSSU Logs System API","timestamp":"...","database":"connected"}
```

---

## 🚀 Quick Start: Deploy to Railway

### Option 1: Automatic Deployment (Recommended)

1. **Push to GitHub:**
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
git add .
git commit -m "Configure for Railway deployment"
git push origin main
```

2. **Deploy on Railway:**
   - Visit: https://railway.app
   - Click "New Project" → "Deploy from GitHub repo"
   - Select your repository
   - Railway will auto-detect Laravel and build

3. **Add MySQL Database:**
   - In project dashboard, click "+ New"
   - Select "Database" → "MySQL"
   - Railway will auto-link environment variables

4. **Set Environment Variables:**
   - Go to your Laravel service → "Variables"
   - Click "RAW Editor" and paste:

```env
APP_NAME=NWSSU Logs System
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_PRIVATE_URL_HOST}}
DB_PORT=${{MySQL.MYSQL_PRIVATE_URL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
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
LOG_LEVEL=error
BCRYPT_ROUNDS=12
```

5. **Generate APP_KEY:**
   - Open Railway console/shell
   - Run: `php artisan key:generate --show`
   - Copy output and update `APP_KEY` variable

6. **Generate Domain:**
   - Settings → Networking → "Generate Domain"
   - Copy URL and update `APP_URL` variable

7. **Run Post-Deployment:**
   - In Railway console: `bash post-deploy.sh`
   - Or manually:
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

8. **Test:**
   - Visit: `https://your-app.up.railway.app/api/health`
   - Should return: `{"status":"ok",...}`

---

## 📋 Detailed Guides

| Guide | Purpose |
|-------|---------|
| `RAILWAY_DEPLOYMENT_STEPS.md` | Step-by-step Railway deployment |
| `COMPLETE_DEPLOYMENT_GUIDE.md` | Backend + Frontend deployment |
| `DEPLOYMENT_CHECKLIST.md` | Quick checklist |

---

## 🔑 Critical Environment Variables

### Must Configure:

1. **APP_KEY** - Generate with: `php artisan key:generate --show`
2. **APP_URL** - Your Railway domain
3. **DB_*** - Auto-filled by Railway MySQL service
4. **MAIL_*** - Already configured with Gmail

### Configure After Frontend Deployment:

5. **FRONTEND_URL** - Admin frontend URL (Vercel)
6. **CLIENT_URL** - Client frontend URL (Vercel)

---

## 🧪 Testing Checklist

After deployment, verify:

- [ ] Health endpoint works: `/api/health`
- [ ] Database connected (health check shows "connected")
- [ ] Login endpoint works: `POST /api/admin/login`
- [ ] CORS allows your frontend domains
- [ ] Emails can be sent (test forgot password)
- [ ] File uploads work
- [ ] Migrations completed successfully

---

## 🔧 Railway Configuration

### Build Phase (nixpacks.toml):
1. Install PHP 8.2 with extensions
2. Run `composer install --no-dev`
3. Run `npm install && npm run build`
4. Cache config, routes, and views

### Start Command:
```bash
php artisan migrate --force && 
php artisan storage:link && 
php artisan serve --host=0.0.0.0 --port=$PORT
```

---

## 🌐 CORS Configuration

CORS is already configured to accept:

- ✅ `http://localhost:5173` (local admin)
- ✅ `http://localhost:5174` (local client)
- ✅ All `*.vercel.app` domains (production)
- ✅ Specific URLs from `FRONTEND_URL` and `CLIENT_URL`

**Credentials:** Enabled (`supports_credentials: true`)

---

## 📦 What's Included

### Dependencies (composer.json):
- Laravel 12.x
- Laravel Sanctum (API authentication)
- Laravel Tinker

### Build Tools (package.json):
- Vite
- Tailwind CSS
- Axios

### Deployment Ready:
- ✅ PHP 8.2 configured
- ✅ MySQL ready
- ✅ CORS configured
- ✅ Health checks
- ✅ Migration ready
- ✅ Storage links
- ✅ Email configured

---

## 🐛 Troubleshooting

### Build Fails

**Issue:** PHP version error
- ✅ Fixed: `nixpacks.toml` specifies PHP 8.2

**Issue:** Composer dependencies fail
```bash
# In Railway console:
composer install --no-dev --no-interaction --optimize-autoloader
```

### Database Issues

**Issue:** Connection refused
- Verify MySQL service is running
- Check environment variables use `${{MySQL.*}}`

**Issue:** Migration fails
```bash
# In Railway console:
php artisan migrate:fresh --force
```

### APP_KEY Missing

**Issue:** "No application encryption key"
```bash
# Generate key:
php artisan key:generate --show
# Add to Railway variables
```

### CORS Errors

**Issue:** Frontend blocked by CORS
- Update `FRONTEND_URL` and `CLIENT_URL` in Railway variables
- Restart service
- Clear cache: `php artisan config:cache`

---

## 💡 Pro Tips

1. **Use Railway Console:**
   - Access via service → Settings → Connect
   - Run artisan commands directly
   - View real-time logs

2. **Monitor Logs:**
   - View logs in Railway dashboard
   - Check for errors during deployment
   - Use `/api/health` for uptime monitoring

3. **Database Backups:**
   - Railway handles automatic backups
   - Export data periodically for safety

4. **Environment Management:**
   - Never commit `.env` (already in .gitignore)
   - Keep `.env.production` as template only
   - Update variables in Railway dashboard

5. **Cost Optimization:**
   - Free tier: $5 credit/month
   - Monitor usage in Railway dashboard
   - Upgrade only when needed

---

## 📞 Support Resources

- **Railway Docs:** https://docs.railway.app
- **Laravel Deployment:** https://laravel.com/docs/deployment
- **Nixpacks:** https://nixpacks.com/docs
- **Railway Discord:** https://discord.gg/railway

---

## 🎯 Next Steps

1. ✅ Backend configured
2. ⬜ Deploy to Railway
3. ⬜ Deploy frontends to Vercel
4. ⬜ Update frontend URLs in Railway
5. ⬜ Test end-to-end
6. ⬜ Create admin accounts
7. ⬜ Import master list

---

## 🎉 Success Criteria

Your deployment is successful when:

✅ Railway build completes without errors
✅ Service shows "Active" status
✅ `/api/health` returns `{"status":"ok"}`
✅ Database connection works
✅ API endpoints respond correctly
✅ CORS allows frontend requests
✅ Emails send successfully
✅ File uploads work

---

## 📝 Quick Commands

```bash
# Test locally before deploying
php artisan serve
php artisan config:cache
php artisan route:cache

# Railway console commands
php artisan about
php artisan migrate:status
php artisan db:show
php artisan optimize:clear
php artisan storage:link

# Check logs
tail -f storage/logs/laravel.log
```

---

**🚀 You're ready to deploy!**

Follow `RAILWAY_DEPLOYMENT_STEPS.md` for detailed instructions.

**Estimated deployment time:** 15-20 minutes

**Cost:** FREE (Railway $5/month credit)

Good luck! 🎉
