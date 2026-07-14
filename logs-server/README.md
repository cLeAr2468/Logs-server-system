# 🚀 NWSSU Logs System - Laravel Backend

[![PHP 8.2](https://img.shields.io/badge/PHP-8.2-blue)](https://php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-red)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](https://mysql.com)
[![Railway Ready](https://img.shields.io/badge/Railway-Ready-success)](https://railway.app)

**Status:** ✅ Ready for Production Deployment

---

## 🎯 Quick Links

### 🚀 Ready to Deploy?
**👉 [START HERE - Deployment Guide](START_HERE.md)**

### 📚 Documentation
- **Quick Deploy:** [QUICK_DEPLOY.md](QUICK_DEPLOY.md) - 5 minutes
- **Detailed Guide:** [RAILWAY_DEPLOYMENT_STEPS.md](RAILWAY_DEPLOYMENT_STEPS.md) - 20 minutes
- **Full Index:** [README_DEPLOYMENT.md](README_DEPLOYMENT.md) - All guides

---

## 📦 What's This?

This is the **Laravel backend API** for the NWSSU Logs System - a comprehensive appointment and transaction logging system for Northwest Samar State University.

### Features:
- 🔐 Authentication (Admin, Staff, Client)
- 📅 Appointment Management
- 📊 Dashboard & Analytics
- 📢 Announcements System
- 📝 Transaction Logging
- 📧 Email Notifications
- 👥 Master List Management
- 📈 Reports & Statistics

---

## ⚡ Quick Start

### Local Development

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Configure database
# Edit .env with your database credentials

# 4. Run migrations
php artisan migrate

# 5. Start server
php artisan serve
```

**API will be at:** http://localhost:8000/api

---

## 🚀 Production Deployment

### ✅ Already Configured!

All deployment files are ready. Just follow the guides:

**👉 [START_HERE.md](START_HERE.md)** - Choose your deployment path

### What's Configured:
- ✅ Railway deployment (PHP 8.2)
- ✅ MySQL database support
- ✅ CORS for Vercel frontends
- ✅ Health monitoring endpoint
- ✅ Auto-migrations on deploy
- ✅ Email notifications
- ✅ Session & cache management

### Deployment Time:
**15-20 minutes** to production!

### Cost:
**$0/month** (Free tier: Railway + Vercel)

---

## 📁 Project Structure

```
logs-server/
├── app/                    # Application code
│   ├── Http/Controllers/   # API controllers
│   ├── Models/            # Eloquent models
│   └── Middleware/        # Custom middleware
├── config/                # Configuration
│   └── cors.php          # ✅ Updated for production
├── database/              # Migrations & seeders
├── routes/
│   └── api.php           # ✅ Health check added
├── storage/              # Logs & uploads
│
├── 🚀 Deployment Files
│   ├── nixpacks.toml     # Railway build config
│   ├── railway.json      # Deployment settings
│   ├── .env.production   # Production template
│   ├── Procfile          # Process file
│   └── post-deploy.sh    # Automation script
│
└── 📚 Documentation
    ├── START_HERE.md              # 📍 Start here!
    ├── QUICK_DEPLOY.md            # 5-min reference
    ├── RAILWAY_DEPLOYMENT_STEPS.md # Detailed guide
    └── ... (9 comprehensive guides)
```

---

## 🔑 Environment Variables

### Required:
```env
APP_KEY=                # Generate with: php artisan key:generate
APP_URL=                # Your domain
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### Email (Optional but recommended):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
```

**Full configuration:** See `.env.example` or `.env.production`

---

## 🧪 API Endpoints

### Health Check:
```
GET /api/health
```

### Authentication:
```
POST /api/login              # Client login
POST /api/register           # Client registration
POST /api/admin/login        # Admin/Staff login
POST /api/forgot-password    # Password reset
```

### Appointments:
```
GET    /api/appointments           # List all
POST   /api/appointments           # Create new
GET    /api/appointments/{id}      # Get one
PUT    /api/appointments/{id}      # Update
DELETE /api/appointments/{id}      # Delete
```

### Announcements:
```
GET  /api/public/announcements     # Public (no auth)
GET  /api/announcements            # All (requires auth)
POST /api/announcements            # Create (admin only)
```

**Full API:** See [routes/api.php](routes/api.php)

---

## 🧪 Testing

### Test Locally:
```bash
# Test health endpoint
curl http://localhost:8000/api/health

# Test login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### Test Production:
```bash
# Health check
curl https://your-app.up.railway.app/api/health

# Should return:
# {"status":"ok","service":"NWSSU Logs System API",...}
```

---

## 🔧 Development

### Install Dependencies:
```bash
composer install        # PHP dependencies
npm install            # Node dependencies
```

### Run Migrations:
```bash
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Fresh start
php artisan db:seed          # Seed data (if available)
```

### Cache Management:
```bash
php artisan config:cache     # Cache config
php artisan route:cache      # Cache routes
php artisan view:cache       # Cache views
php artisan optimize:clear   # Clear all caches
```

### Useful Commands:
```bash
php artisan about            # App info
php artisan db:show          # Database info
php artisan migrate:status   # Migration status
php artisan route:list       # List all routes
```

---

## 🐛 Troubleshooting

### Common Issues:

**Issue:** APP_KEY error
```bash
php artisan key:generate
```

**Issue:** Database connection failed
- Check `.env` database credentials
- Ensure MySQL is running
- Test connection: `php artisan db:show`

**Issue:** CORS errors
- Update `config/cors.php`
- Add frontend URL to allowed origins
- Clear cache: `php artisan config:cache`

**Issue:** Storage errors
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

**Full troubleshooting:** See deployment guides

---

## 📚 Documentation

### For Deployment:
| Guide | Purpose | Time |
|-------|---------|------|
| [START_HERE.md](START_HERE.md) | 📍 Navigation guide | - |
| [QUICK_DEPLOY.md](QUICK_DEPLOY.md) | ⚡ Quick reference | 5 min |
| [RAILWAY_DEPLOYMENT_STEPS.md](RAILWAY_DEPLOYMENT_STEPS.md) | 📝 Step-by-step | 20 min |
| [DEPLOY_README.md](DEPLOY_README.md) | 📖 Complete overview | 10 min |
| [README_DEPLOYMENT.md](README_DEPLOYMENT.md) | 📇 Full index | - |

### For Development:
- **API Routes:** [routes/api.php](routes/api.php)
- **Controllers:** [app/Http/Controllers/](app/Http/Controllers/)
- **Models:** [app/Models/](app/Models/)
- **Migrations:** [database/migrations/](database/migrations/)

---

## 🤝 Contributing

This is a student project for Northwest Samar State University.

---

## 📞 Support

### For Deployment:
- **Railway Docs:** https://docs.railway.app
- **Laravel Deployment:** https://laravel.com/docs/deployment

### For Development:
- **Laravel Docs:** https://laravel.com/docs
- **Laravel API:** https://laravel.com/api

---

## 📄 License

This project is for educational purposes.

---

## 🎯 Quick Actions

### 🚀 Deploy to Production
```bash
# 1. Push to GitHub
git add . && git commit -m "Deploy" && git push

# 2. Follow: START_HERE.md
# 3. Railway will auto-deploy!
```

### 🧪 Test Locally
```bash
php artisan serve
# Visit: http://localhost:8000/api/health
```

### 📦 Update Dependencies
```bash
composer update
npm update
```

---

## ✨ Features

- ✅ RESTful API
- ✅ Token-based authentication (Sanctum)
- ✅ Role-based access control
- ✅ Email notifications
- ✅ File uploads
- ✅ Database migrations
- ✅ API documentation
- ✅ Health monitoring
- ✅ CORS configured
- ✅ Production ready

---

## 🎉 Ready to Deploy!

Your backend is **100% configured** for production deployment.

**👉 [Click here to start: START_HERE.md](START_HERE.md)**

**Deployment time:** 15-20 minutes
**Cost:** FREE
**Difficulty:** Easy

---

*Made with ❤️ for Northwest Samar State University*
