# Dashboard Troubleshooting Guide

## 🔴 Error: "Failed to load dashboard data"

Based on your screenshot, here's how to debug and fix the dashboard loading issue.

---

## 🔍 Step 1: Check Browser Console

### **Open DevTools:**
1. Press `F12` or Right-click → Inspect
2. Go to **Console** tab
3. Look for errors

### **Common Console Errors:**

#### **A. CORS Error:**
```
Access to fetch at 'https://...' has been blocked by CORS policy
```
**Solution:** Backend needs to allow your frontend origin in CORS settings.

#### **B. 404 Not Found:**
```
GET https://.../admin/dashboard/statistics 404 (Not Found)
```
**Solution:** Endpoint doesn't exist or route not registered.

#### **C. 401 Unauthorized:**
```
GET https://.../admin/dashboard/statistics 401 (Unauthorized)
```
**Solution:** Token is missing, invalid, or expired.

#### **D. Network Error:**
```
Failed to fetch
TypeError: NetworkError when attempting to fetch resource
```
**Solution:** Backend is down or URL is wrong.

---

## 🔍 Step 2: Check Network Tab

### **Open DevTools:**
1. Go to **Network** tab
2. Refresh the dashboard page
3. Look for API calls

### **Expected Requests:**
You should see 3 requests:
```
1. GET /api/admin/dashboard/statistics?month=7&year=2026
2. GET /api/admin/dashboard/recent-transactions?limit=10&month=7&year=2026
3. GET /api/admin/dashboard/performance?month=7&year=2026
```

### **Click on Each Request:**

#### **Check Request Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```
**If Authorization header is missing:**
- Token not stored in localStorage
- Need to log in again

#### **Check Response:**

**✅ Success (200 OK):**
```json
{
  "statistics": {
    "total_transactions": 245,
    ...
  }
}
```

**❌ Error (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Token expired, need to log in again.

**❌ Error (404 Not Found):**
```json
{
  "message": "Route not found"
}
```
**Solution:** Backend endpoint doesn't exist.

**❌ Error (500 Internal Server Error):**
```json
{
  "message": "Server Error",
  "error": "..."
}
```
**Solution:** Backend error, check Railway logs.

---

## 🔧 Common Fixes

### **Fix 1: Token Issues**

**Check if token exists:**
1. Open DevTools → Console
2. Type: `localStorage.getItem('admin_token')`
3. Press Enter

**If returns `null`:**
```javascript
// Token doesn't exist - need to log in
```
**Solution:** Log out and log in again.

**If returns token:**
```javascript
// "eyJ0eXAiOiJKV1QiLCJhbGc..."
```
**Check if it's valid:**
- Try logging in again to get fresh token
- Token might be expired

---

### **Fix 2: Wrong API URL**

**Check .env file:**
```env
# Should be:
VITE_API_URL=https://logs-server-system-production.up.railway.app/api

# NOT:
VITE_API_URL=https://logs-server-system-production.up.railway.app
# (missing /api at the end)
```

**Verify in browser:**
1. Open DevTools → Console
2. Type: `import.meta.env.VITE_API_URL`
3. Check the value

---

### **Fix 3: Backend Not Deployed**

**Check if Railway backend is running:**
1. Go to https://logs-server-system-production.up.railway.app
2. Should see Laravel welcome page

**If not loading:**
- Backend deployment failed
- Check Railway dashboard
- Check Railway logs: `railway logs`

---

### **Fix 4: Route Not Found**

**Verify backend routes exist:**

Check `routes/api.php` has:
```php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/dashboard/statistics', [DashboardController::class, 'getAdminStatistics']);
    Route::get('/admin/dashboard/recent-transactions', [DashboardController::class, 'getRecentTransactions']);
    Route::get('/admin/dashboard/performance', [DashboardController::class, 'getPerformanceSummary']);
});
```

**If routes are missing:**
- Add them to `routes/api.php`
- Redeploy backend

---

## 🧪 Manual Testing

### **Test API Endpoints Directly:**

**1. Test Statistics Endpoint:**
```bash
# Replace YOUR_TOKEN with your actual token
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://logs-server-system-production.up.railway.app/api/admin/dashboard/statistics?month=7&year=2026"
```

**Expected Response:**
```json
{
  "statistics": {
    "total_transactions": 0,
    "target_percentage": 0,
    ...
  }
}
```

**2. Test in Browser:**
1. Open: https://logs-server-system-production.up.railway.app/api/admin/dashboard/statistics?month=7&year=2026
2. You'll get 401 (expected without token)
3. If you get 404, route doesn't exist

---

## 🔍 Debug Mode

### **Added Enhanced Logging:**

I've updated the dashboard to log more details. After refresh, check console for:

**Success:**
```
🔍 Fetching dashboard data... { month: 7, year: 2026, ... }
📊 API Responses: { statistics: 200, transactions: 200, performance: 200 }
✅ Statistics data: { statistics: {...} }
✅ Transactions data: { transactions: [...] }
✅ Performance data: { performance: [...] }
```

**Failure:**
```
🔍 Fetching dashboard data... { month: 7, year: 2026, ... }
📊 API Responses: { statistics: 401, transactions: 401, performance: 401 }
❌ Statistics failed: 401 { message: "Unauthenticated." }
```

---

## 📋 Quick Checklist

```
□ Backend is running on Railway
  - Test: Visit https://logs-server-system-production.up.railway.app
  
□ .env file has correct VITE_API_URL
  - Check: c:\Users\User\Desktop\Transact-logs-system\logs-system\.env
  
□ Admin token exists
  - Check console: localStorage.getItem('admin_token')
  
□ Token is valid (not expired)
  - Try logging in again
  
□ Backend routes exist
  - Check: routes/api.php has dashboard routes
  
□ DashboardController exists
  - Check: app/Http/Controllers/DashboardController.php
  
□ No CORS errors in console
  - Check browser console
  
□ API calls return 200 OK
  - Check Network tab
```

---

## 🚀 Quick Fixes

### **Fix 1: Re-login**
```
1. Log out from dashboard
2. Go to login page
3. Log in again with admin credentials
4. Go back to dashboard
5. Should load now
```

### **Fix 2: Clear Cache**
```
1. Open DevTools
2. Right-click refresh button
3. Select "Empty Cache and Hard Reload"
4. Or: Ctrl + Shift + Delete → Clear cache
```

### **Fix 3: Check Environment**
```bash
# In Transact-logs-system folder
cd c:\Users\User\Desktop\Transact-logs-system\logs-system

# Verify .env
cat .env

# Should show:
# VITE_API_URL=https://logs-server-system-production.up.railway.app/api

# If wrong, fix it and rebuild
npm run build
```

---

## 📱 Test with Different Month

Try selecting **current month** (January 2025):
- You selected July 2026 (future month)
- There might be no data for that period
- Select current month to see if data appears

---

## 🔧 Still Not Working?

### **Provide These Details:**

**1. Browser Console Output:**
- Copy all red errors
- Copy all console.log messages

**2. Network Tab Details:**
- Status codes of 3 API calls
- Response body of failed requests

**3. LocalStorage Check:**
```javascript
// Run in console:
console.log('Token:', localStorage.getItem('admin_token'));
console.log('API URL:', import.meta.env.VITE_API_URL);
```

**4. Backend Logs:**
```bash
# If you have Railway CLI:
railway logs

# Look for:
# - API request logs
# - Error messages
# - 401/500 errors
```

---

## 💡 Most Likely Issue

Based on your screenshot showing **July 2026** selected:

**You're viewing a FUTURE month with no data!**

### **Try This:**
1. Select current month (January 2025)
2. Refresh dashboard
3. Data should appear if there are any transactions

If still showing error after selecting current month:
- **Token expired** - Log in again
- **Backend not deployed** - Check Railway
- **CORS issue** - Check browser console

---

## ✅ Working Dashboard Should Show:

1. **4 Statistics Cards** at top
2. **Recent Transactions Table** with data (or "No transactions")
3. **Performance Summary** on right (or "No performance data")
4. **No red error message**

If you see statistics but "No recent transactions":
- That's OKAY if no transactions exist for selected month
- Try selecting a different month with data

---

## 🎯 Next Steps

1. **Open browser console** (F12)
2. **Look for error messages**
3. **Check Network tab** for API responses
4. **Share console output** if still stuck

Would you like me to help you debug specific errors from the console?
