# Railway Deployment Fix - Root Directory Issue

## 🚨 Problem

Your deployment is failing because Railway is looking for your Laravel app in the wrong directory.

**Your Repository Structure:**
```
Logs-server-system/          ← Railway is looking here (wrong!)
└── logs-server/              ← Your Laravel app is actually here
    ├── app/
    ├── composer.json
    ├── Procfile
    ├── nixpacks.toml
    └── ...
```

**Error:** Railway can't find `composer.json`, `Procfile`, etc., because they're in the `logs-server` subdirectory.

---

## ✅ Solution: Set Root Directory

You need to tell Railway to use `logs-server` as the root directory.

### **Method 1: Railway Dashboard (RECOMMENDED)**

**Step-by-step:**

1. Go to your Railway dashboard: https://railway.app/dashboard
2. Open your project (Logs-server-system)
3. Click on your **Laravel service** (the one that's failing)
4. Click on the **"Settings"** tab
5. Scroll down to find **"Root Directory"** or **"Service Settings"**
6. In the **"Root Directory"** field, enter: `logs-server`
7. Click the **checkmark** or **"Update"** button to save
8. Railway will automatically trigger a new deployment

**Screenshot Guide:**
```
Settings Tab
├── General
├── Domains
├── Service Settings         ← Look here
│   └── Root Directory       ← Enter: logs-server
└── ...
```

---

### **Method 2: Add Railway Service Variable**

If you don't see "Root Directory" in Settings:

1. Go to your Laravel service
2. Click **"Variables"** tab
3. Click **"+ New Variable"**
4. Add:
   - **Name:** `RAILWAY_ROOT_DIRECTORY`
   - **Value:** `logs-server`
5. Save
6. Redeploy

---

### **Method 3: Move Files to Root (NOT RECOMMENDED)**

If the above doesn't work, you can move your Laravel app to the root:

```bash
# In your local repository
cd c:\xampp\htdocs\Logs-server-system

# Move everything from logs-server to root
move logs-server\* .
rmdir logs-server

# Commit and push
git add .
git commit -m "Move Laravel app to root"
git push origin main
```

**Note:** This will change your local folder structure.

---

## 🔍 Verify the Fix

After setting the root directory:

1. **Check Deployment Logs:**
   - Go to your service → **"Deployments"** tab
   - Click on the latest deployment
   - Look for these success indicators:
     ```
     ✓ Found composer.json
     ✓ Installing dependencies...
     ✓ Running php artisan config:cache
     ✓ Deployment successful
     ```

2. **Check Build Output:**
   Look for:
   ```
   Building from directory: /app/logs-server
   Found composer.json
   Installing PHP dependencies...
   ```

3. **Test Your API:**
   ```bash
   curl https://your-app.up.railway.app/api/health
   ```

---

## 🐛 Still Failing? Check These

### 1. **Verify Repository Structure**

On GitHub, check your repository:
- Go to: `https://github.com/YOUR_USERNAME/YOUR_REPO`
- You should see: `logs-server/` folder
- Inside `logs-server/` you should see:
  - `composer.json`
  - `artisan`
  - `app/`
  - `Procfile`
  - `nixpacks.toml`

### 2. **Check Deployment Logs**

Common errors and fixes:

**Error: "No buildpack detected"**
- **Fix:** Make sure `nixpacks.toml` is in `logs-server/`

**Error: "composer.json not found"**
- **Fix:** Set root directory to `logs-server`

**Error: "Class not found" or "namespace errors"**
- **Fix:** Run `composer install` in Railway console:
  ```bash
  composer install --optimize-autoloader
  php artisan config:clear
  ```

**Error: "No application encryption key"**
- **Fix:** Generate APP_KEY:
  ```bash
  php artisan key:generate --show
  ```
  Add to Railway variables.

### 3. **Environment Variables**

Make sure these are set:

```env
# Required
APP_KEY=base64:...
APP_ENV=production
APP_DEBUG=false

# Database (Railway MySQL)
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}
```

---

## 📝 Alternative: Monorepo Configuration

If you have multiple services in one repo, use Railway's monorepo feature:

**railway.toml** (in repository root):
```toml
[build]
builder = "NIXPACKS"
watchPatterns = ["logs-server/**"]

[deploy]
startCommand = "cd logs-server && php artisan serve --host=0.0.0.0 --port=$PORT"
```

---

## 🎯 Quick Checklist

After setting root directory:

- [ ] Root directory set to `logs-server`
- [ ] New deployment triggered
- [ ] Build logs show "Found composer.json"
- [ ] No "buildpack not detected" errors
- [ ] Deployment status: Success ✅
- [ ] API endpoint responds
- [ ] Database connected

---

## 💡 Pro Tips

### 1. **Use Railway CLI for Easier Setup**

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link to your project
railway link

# Set root directory
railway variables set RAILWAY_ROOT_DIRECTORY=logs-server

# Deploy
railway up
```

### 2. **Check Service Settings**

Always verify in Railway:
- **Settings** → **Service** → **Root Directory** = `logs-server`

### 3. **Monitor Deployments**

Watch the build logs in real-time:
- Go to **Deployments** tab
- Click latest deployment
- Watch logs as they stream

---

## 🆘 Common Questions

**Q: Why is my folder structure different?**
A: When you pushed to GitHub, you might have pushed the parent folder instead of just the Laravel app.

**Q: Should I restructure my repository?**
A: No need! Just set the root directory in Railway. That's the easiest fix.

**Q: Will this affect my local development?**
A: No! Your local files stay the same. This only affects Railway's build process.

**Q: Can I have multiple services from one repo?**
A: Yes! Railway supports monorepos. Just create multiple services and set different root directories for each.

---

## ✅ Success Indicators

You'll know it's working when you see:

**In Build Logs:**
```
==> Building logs-server
==> Detected PHP application
==> Found composer.json
==> Installing dependencies with composer
==> Running: composer install --no-dev --optimize-autoloader
==> Build successful!
```

**In Deploy Logs:**
```
==> Deploying...
==> Running: php artisan migrate --force
Migration table created successfully.
==> Server running on 0.0.0.0:$PORT
==> Deployment successful!
```

**In Browser:**
```
https://your-app.up.railway.app
Status: 200 OK
```

---

## 🎉 Next Steps After Fix

Once deployment succeeds:

1. **Test API Endpoints:**
   ```bash
   curl https://your-app.up.railway.app/api/health
   curl https://your-app.up.railway.app/api/public/announcements
   ```

2. **Run Migrations:**
   ```bash
   # In Railway console
   php artisan migrate --force
   ```

3. **Create Storage Link:**
   ```bash
   php artisan storage:link
   ```

4. **Update Frontend:**
   ```env
   VITE_API_URL=https://your-app.up.railway.app/api
   ```

5. **Test Full Flow:**
   - Login
   - Create announcement
   - Upload image
   - Send email notification

---

## 📞 Still Need Help?

If you're still stuck:

1. **Share Error Logs:**
   - Copy full build logs from Railway
   - Share in Railway Discord or here

2. **Verify Setup:**
   - Confirm repository structure on GitHub
   - Confirm root directory setting in Railway
   - Confirm environment variables are set

3. **Try Clean Deploy:**
   ```bash
   # In Railway console
   composer clear-cache
   php artisan cache:clear
   php artisan config:clear
   
   # Then redeploy
   ```

---

*Follow this guide to fix the root directory issue and successfully deploy your Laravel backend!*
