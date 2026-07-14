# 🚀 Deployment Quick Checklist

## ⏱️ Total Time: ~30 Minutes

---

## ✅ BACKEND DEPLOYMENT (Railway)

### **Preparation** (5 min)
- [ ] Update `nixpacks.toml` to PHP 8.2 ✅ (Already done!)
- [ ] Remove `.env` from Git
- [ ] Push code to GitHub

### **Railway Setup** (10 min)
- [ ] Sign up at https://railway.app
- [ ] Create new project from GitHub
- [ ] Add MySQL database
- [ ] Set root directory to `logs-server`
- [ ] Add environment variables
- [ ] Generate APP_KEY
- [ ] Generate domain
- [ ] Run migrations

**Backend URL:** `https://______.up.railway.app`

---

## ✅ FRONTEND DEPLOYMENT (Vercel)

### **Admin Frontend** (5 min)
- [ ] Update `.env` with Railway URL
- [ ] Create `vercel.json`
- [ ] Deploy to Vercel
- [ ] Add `VITE_API_URL` variable

**Admin URL:** `https://______.vercel.app`

### **Client Frontend** (5 min)
- [ ] Update `.env` with Railway URL
- [ ] Create `vercel.json`
- [ ] Deploy to Vercel
- [ ] Add `VITE_API_URL` variable

**Client URL:** `https://______.vercel.app`

---

## ✅ CONNECTION (5 min)
- [ ] Update CORS in backend
- [ ] Add frontend URLs to Railway
- [ ] Test login from frontend
- [ ] Verify API calls work

---

## 🎯 ENVIRONMENT VARIABLES

### **Railway (Backend):**
```
APP_KEY=base64:_______________
APP_URL=https://______.up.railway.app
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}
```

### **Vercel (Both Frontends):**
```
VITE_API_URL=https://______.up.railway.app/api
```

---

## 🧪 TESTING

- [ ] Backend API responds: `curl https://______.up.railway.app/api/health`
- [ ] Admin login works
- [ ] Client registration works
- [ ] Data displays correctly
- [ ] Email notifications send

---

## 🎉 DONE!

**Backend:** https://______.up.railway.app
**Admin:** https://______.vercel.app
**Client:** https://______.vercel.app

**Total Cost:** $0/month ✅
