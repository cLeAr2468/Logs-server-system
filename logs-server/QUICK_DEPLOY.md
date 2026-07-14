# ⚡ Quick Deploy Reference

## 30-Second Checklist

- [x] Files configured
- [ ] Code on GitHub
- [ ] Railway project created
- [ ] MySQL added
- [ ] Variables set
- [ ] APP_KEY generated
- [ ] Domain generated
- [ ] Migrations run

---

## 5-Minute Deploy

### 1. Push Code
```bash
git add . && git commit -m "Deploy" && git push
```

### 2. Railway Setup
1. railway.app → New Project → GitHub repo
2. Add MySQL database
3. Copy variables from `.env.production`
4. Generate APP_KEY: `php artisan key:generate --show`
5. Generate domain → Update APP_URL

### 3. Post-Deploy
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
```

### 4. Test
Visit: `https://your-app.up.railway.app/api/health`

---

## Essential Variables

```env
APP_KEY=                    # Generate this!
APP_URL=                    # Your Railway domain
DB_HOST=${{MySQL.MYSQL_PRIVATE_URL_HOST}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}
```

---

## Commands

```bash
# Generate key
php artisan key:generate --show

# Migrate
php artisan migrate --force

# Link storage
php artisan storage:link

# Cache
php artisan config:cache

# Check status
php artisan about
```

---

## Files Created

✅ `nixpacks.toml` - Build config
✅ `railway.json` - Deploy config
✅ `.env.production` - Env template
✅ `post-deploy.sh` - Setup script
✅ Health endpoint at `/api/health`
✅ CORS configured for Vercel

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Build fails | Check `nixpacks.toml` exists |
| No APP_KEY | Run `key:generate --show` |
| DB error | Verify MySQL service running |
| CORS error | Update FRONTEND_URL variable |
| 404 errors | Run `route:cache` |

---

## After Frontend Deploy

Update Railway variables:
```env
FRONTEND_URL=https://admin.vercel.app
CLIENT_URL=https://client.vercel.app
```

---

## Docs

📖 `DEPLOY_README.md` - Overview
📋 `RAILWAY_DEPLOYMENT_STEPS.md` - Detailed steps
✅ `DEPLOYMENT_CHECKLIST.md` - Full checklist

---

**Ready in 15 minutes!** 🚀
