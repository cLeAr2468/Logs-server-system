# Quick Start: Deploy to Railway in 10 Minutes

## Step-by-Step Guide

### 1️⃣ Prepare Your Code (2 minutes)

```bash
# Navigate to your Laravel project
cd c:\xampp\htdocs\Logs-server-system\logs-server

# Initialize Git (if not already done)
git init
git add .
git commit -m "Ready for deployment"
```

### 2️⃣ Push to GitHub (3 minutes)

1. Create a new repository on GitHub: https://github.com/new
2. Name it: `logs-server` or similar
3. Push your code:

```bash
git remote add origin https://github.com/YOUR_USERNAME/logs-server.git
git branch -M main
git push -u origin main
```

### 3️⃣ Deploy on Railway (5 minutes)

**A. Create Project:**
1. Go to: https://railway.app
2. Sign in with GitHub
3. Click **"Start a New Project"**
4. Select **"Deploy from GitHub repo"**
5. Choose your `logs-server` repository
6. Click **"Deploy Now"**

**B. Add Database:**
1. In your project, click **"+ New"**
2. Select **"Database"**
3. Choose **"Add MySQL"**
4. Wait for provisioning

**C. Add Environment Variables:**
1. Click on your **Laravel service** (not MySQL)
2. Go to **"Variables"** tab
3. Add these variables:

```env
APP_NAME=NWSSU Logs System
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_THIS_BELOW

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

SESSION_DRIVER=file
CACHE_DRIVER=file
```

**D. Generate APP_KEY:**
1. In your Laravel service, click **"Deployments"**
2. Click latest deployment
3. Click **"View Logs"**
4. Once deployed, open console (Settings → Deploy → Open Shell)
5. Run: `php artisan key:generate --show`
6. Copy the output
7. Go back to Variables
8. Set `APP_KEY` to the copied value

**E. Get Your URL:**
1. Go to **"Settings"** → **"Networking"**
2. Click **"Generate Domain"**
3. Copy your URL: `https://your-app.up.railway.app`
4. Update `APP_URL` variable with this URL

### 4️⃣ Update Frontend (1 minute)

Update both frontend `.env` files:

**Transact-logs-system:**
```env
VITE_API_URL=https://your-app.up.railway.app/api
```

**Client-Module:**
```env
VITE_API_URL=https://your-app.up.railway.app/api
```

---

## ✅ Verification

Test your API:
```
https://your-app.up.railway.app/api/health
```

If you see an error, check the logs in Railway dashboard.

---

## 🎉 Done!

Your backend is now live at: `https://your-app.up.railway.app`

### Next Steps:
1. Test all API endpoints
2. Configure email settings (for notifications)
3. Deploy your frontend (Vercel, Netlify, etc.)
4. Update CORS settings with frontend URL

---

## 🆘 Quick Fixes

**Problem: 500 Error**
```bash
# In Railway console:
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
```

**Problem: Database connection error**
- Check that MySQL service is running
- Verify database variables are set correctly

**Problem: Storage not working**
```bash
# In Railway console:
php artisan storage:link
```

---

## 📧 Email Setup (Optional)

If you want email notifications to work:

1. Use Gmail App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Generate password
   
2. Add to Railway variables:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=NWSSU Logs System
```

---

## 💡 Tips

- Railway gives you $5 free credit/month
- Monitor usage in dashboard
- Set up GitHub auto-deploy (already done!)
- Use Railway console for debugging

---

**Need detailed help? Check `RAILWAY_DEPLOYMENT_GUIDE.md`**
