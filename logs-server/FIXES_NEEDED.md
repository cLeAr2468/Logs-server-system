# Required Fixes for Email and Dashboard Issues

## Issue 1: Email Notifications Not Working ❌

### Problem
- Resend API key returns "API key is invalid" error
- OTP emails for password reset are not being sent
- Appointment status notifications (approved/rejected) are not being sent

### Root Cause
Railway blocks ALL SMTP connections (ports 25, 465, 587), so we switched to Resend HTTP API, but the API key is expired or invalid.

### Solution Steps

#### Step 1: Generate New Resend API Key
1. Go to https://resend.com/api-keys
2. Log in to your Resend account
3. Click "Create API Key"
4. Name it: `Railway Production - Logs System`
5. Set permissions: **Full Access** (or at minimum: "Sending access")
6. Copy the new API key (starts with `re_`)

#### Step 2: Update Railway Environment Variables
1. Open Railway dashboard: https://railway.app
2. Go to your `logs-server-system-production` project
3. Click on the backend service
4. Go to **Variables** tab
5. **Remove these OLD SMTP variables** (if they exist):
   ```
   MAIL_HOST
   MAIL_PORT
   MAIL_USERNAME
   MAIL_PASSWORD
   MAIL_ENCRYPTION
   ```

6. **Add/Update these Resend variables**:
   ```
   RESEND_API_KEY=re_YOUR_NEW_KEY_HERE
   MAIL_MAILER=resend
   MAIL_FROM_ADDRESS=onboarding@resend.dev
   MAIL_FROM_NAME=NWSSU Logs System
   ```

#### Step 3: Redeploy Backend
After updating variables, Railway will automatically redeploy. Wait for deployment to complete.

#### Step 4: Clear Config Cache
1. Open Railway console for your backend service
2. Run: `php artisan config:clear`
3. Run: `php artisan cache:clear`

#### Step 5: Test Email Sending
In Railway console, run:
```php
php artisan tinker

Mail::raw('Test email from Railway', function($message) {
    $message->to('reyesjerald638@gmail.com')
           ->subject('Test - Logs System');
});
```

If successful, you should see: `= Illuminate\Mail\SentMessage`

### Important Notes
- **Resend Free Tier**: Can only send to verified email addresses
- **Your verified email**: `reyesjerald638@gmail.com` ✅
- **Test domain**: `onboarding@resend.dev` (can only send to verified emails)
- To send to ANY email, you need to:
  - Add a custom domain in Resend, OR
  - Upgrade to a paid plan

---

## Issue 2: Dashboard Showing Cached Data ❌

### Problem
- Admin dashboard shows 4 transactions even after `migrate:fresh`
- Railway database is empty (confirmed)
- Frontend is displaying old/cached data

### Root Cause
Browser is caching API responses or React state is persisting in localStorage.

### Solution Steps

#### Step 1: Clear Browser Cache
1. Open your admin dashboard: https://your-admin-dashboard.pages.dev
2. Press **Ctrl + Shift + Delete** (Windows) or **Cmd + Shift + Delete** (Mac)
3. Select:
   - ✅ Cached images and files
   - ✅ Site data
   - ✅ Cookies
4. Time range: **All time**
5. Click **Clear data**

#### Step 2: Clear localStorage
1. Open browser DevTools (F12)
2. Go to **Console** tab
3. Run:
   ```javascript
   localStorage.clear();
   sessionStorage.clear();
   location.reload();
   ```

#### Step 3: Hard Refresh
1. Press **Ctrl + F5** (Windows) or **Cmd + Shift + R** (Mac)
2. This forces the browser to reload without cache

#### Step 4: Verify API Calls
1. Open DevTools (F12)
2. Go to **Network** tab
3. Filter by **Fetch/XHR**
4. Refresh the dashboard
5. Check if API calls are going to:
   - ✅ Correct: `https://logs-server-system-production.up.railway.app/api/...`
   - ❌ Wrong: `http://localhost:8000/api/...`

#### Step 5: Verify .env Files
**Admin Dashboard (.env)**
```env
VITE_API_URL=https://logs-server-system-production.up.railway.app/api
```

**Client Module (.env)**
```env
VITE_API_URL=https://logs-server-system-production.up.railway.app/api
```

#### Step 6: Rebuild and Redeploy Frontend
If cache persists, redeploy both frontends:

**For Admin (Transact-logs-system):**
```bash
cd c:\Users\User\Desktop\Transact-logs-system\logs-system
npm run build
npx wrangler pages deploy dist
```

**For Client (Client-Module):**
```bash
cd c:\Users\User\Desktop\Client-Module\logs-system
npm run build
npx wrangler pages deploy dist
```

---

## Testing Checklist

### Email Testing ✅
- [ ] Generate new Resend API key
- [ ] Update Railway variables
- [ ] Clear config cache in Railway
- [ ] Test OTP email (Forgot Password)
- [ ] Test appointment approval email
- [ ] Test appointment rejection email

### Dashboard Testing ✅
- [ ] Clear browser cache
- [ ] Clear localStorage/sessionStorage
- [ ] Hard refresh dashboard
- [ ] Verify Network tab shows Railway API calls
- [ ] Dashboard shows 0 transactions (since DB is empty)
- [ ] Create new test appointment
- [ ] Verify new appointment appears in dashboard

---

## Current Environment Variables (Railway)

```env
APP_URL=https://logs-server-system-production.up.railway.app
APP_NAME=NWSSU Logs System
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:exqSeY0dIozvbue8l+QhmUfnT6qlI/pMmxMuGq+DHho=

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# EMAIL - RESEND HTTP API (REQUIRED FOR RAILWAY)
RESEND_API_KEY=[NEEDS NEW KEY - CURRENT ONE IS INVALID]
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME=NWSSU Logs System
```

---

## Why Railway Blocks SMTP

Railway blocks SMTP ports (25, 465, 587) to prevent spam and abuse. This is standard practice for many cloud hosting providers. Solutions:

1. ✅ **Use HTTP-based email APIs** (Resend, SendGrid, Mailgun, etc.)
2. ❌ **SMTP will NOT work** on Railway free/hobby plans
3. ℹ️ Even with paid Railway plans, SMTP may still be blocked

**We chose Resend** because:
- Fast HTTP API (no SMTP)
- Laravel package available
- Free tier includes 100 emails/day
- Easy to set up
- Works perfectly on Railway

---

## Email Code Locations

### OTP Email (Forgot Password)
**Controller:** `app/Http/Controllers/AuthController.php`
- Line 388: `Mail::to($user->email)->send(new SendOtpMail($otp, $user->fname));`

**Mailable:** `app/Mail/SendOtpMail.php`
**View:** `resources/views/emails/otp.blade.php`

### Appointment Status Email (Approved/Rejected)
**Controller:** `app/Http/Controllers/TransactionController.php`
- Line 267-276: Email sending when status changes

**Mailable:** `app/Mail/TransactionStatusMail.php`
**View:** `resources/views/emails/transaction-status.blade.php`

---

## Next Steps

1. **URGENT**: Get new Resend API key from https://resend.com/api-keys
2. Update Railway variables with new key
3. Test email sending
4. Clear browser cache to fix dashboard
5. Test full workflow:
   - Register user
   - Forgot password → Receive OTP
   - Create appointment
   - Admin approves → User receives email
