# 📧 Email Configuration - Local vs Production

## 🎯 Strategy

You have **two environments**, each needs different email configuration:

1. **Local Development (your computer)** → Use Mailtrap (testing)
2. **Production (Railway)** → Use Resend API (real emails)

---

## 🖥️ LOCAL DEVELOPMENT - Use Mailtrap

### Your `.env` file (local computer)

I've already configured this for you! Just add your Mailtrap SMTP credentials:

**File:** `c:\xampp\htdocs\Logs-server-system\logs-server\.env`

```env
# Mailtrap Configuration for Local Testing
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=YOUR_MAILTRAP_USERNAME  ← Get from Mailtrap
MAIL_PASSWORD=YOUR_MAILTRAP_PASSWORD  ← Get from Mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@transact-logs.com
MAIL_FROM_NAME="NWSSU Logs System"
```

### How to get Mailtrap SMTP credentials:

1. Go to: https://mailtrap.io/inboxes
2. Click on **"My Inbox"** (or create new inbox)
3. Go to **"SMTP Settings"** tab
4. Select **"Laravel 9+"** from dropdown
5. Copy `MAIL_USERNAME` and `MAIL_PASSWORD`
6. Paste into your `.env` file

### Test locally:
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan config:clear
php test-email.php test@example.com
```

Check Mailtrap inbox - email should appear there!

---

## ☁️ PRODUCTION (Railway) - Use Resend API

### Configuration Options:

You have **2 options** for production:

---

### **OPTION 1: Keep Using Resend Sandbox (Quick but Limited)** ⚠️

**Current setup - will have the SAME PROBLEM as now:**
- ✅ Works for whitelisted emails only
- ❌ Won't work for all user emails
- ⚠️ Sandbox limitations remain

**Railway Environment Variables:**
```env
RESEND_API_KEY=re_C7z6ku5u_8tXu2xeZweVtdEzvFvdNNfAf
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME=NWSSU Logs System
```

**This is what you have now, but it won't fix the email problem!**

---

### **OPTION 2: Verify Domain with Resend (RECOMMENDED)** ✅

This will fix the email problem in production!

#### Step 1: Add Domain to Resend

1. Go to: https://resend.com/domains
2. Click **"Add Domain"**
3. Enter: `transact-logs.com`
4. Click **"Add Domain"**

#### Step 2: Add DNS Records

Resend will give you DNS records to add. You'll need to add these to your domain's DNS settings:

**TXT Record:**
```
Type: TXT
Name: _resend
Value: [Resend will provide this]
TTL: 3600
```

**CNAME Records (for DKIM):**
```
Type: CNAME
Name: resend._domainkey
Value: [Resend will provide this]
TTL: 3600

Type: CNAME
Name: resend2._domainkey  
Value: [Resend will provide this]
TTL: 3600
```

#### Step 3: Wait for Verification

- DNS propagation takes 15-30 minutes
- Go back to Resend dashboard
- Click "Verify" button
- Status should change to "Verified" ✅

#### Step 4: Update Railway Environment Variables

Once verified, set these in Railway:

```env
RESEND_API_KEY=re_C7z6ku5u_8tXu2xeZweVtdEzvFvdNNfAf
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@transact-logs.com
MAIL_FROM_NAME=NWSSU Logs System
```

**Important:** Change `MAIL_FROM_ADDRESS` from `onboarding@resend.dev` to use your verified domain!

---

### **OPTION 3: Use Mailtrap Sending (Alternative)** 🚀

Mailtrap also has a production email sending service!

#### Step 1: Verify Domain in Mailtrap

I can see you have `transact-logs.com` in Mailtrap but it's **Unverified**.

1. Click on **"transact-logs.com"** in your Mailtrap dashboard
2. Click **"Add DNS records"** button
3. Add the DNS records Mailtrap provides to your domain
4. Wait 15-30 minutes for verification
5. Click **"Verify"** button

#### Step 2: Get Mailtrap API Token

1. In Mailtrap, go to **"API Tokens"**
2. Create new token for **"Email Sending"**
3. Copy the token

#### Step 3: Update Railway Environment Variables

```env
MAIL_MAILER=smtp
MAIL_HOST=live.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=api
MAIL_PASSWORD=your_mailtrap_api_token_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@transact-logs.com
MAIL_FROM_NAME=NWSSU Logs System
```

---

## 🎯 RECOMMENDED SETUP

### Local Development:
```env
# .env (local)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@transact-logs.com
MAIL_FROM_NAME="NWSSU Logs System"
```

### Production (Railway):
```env
# Railway Environment Variables
RESEND_API_KEY=re_C7z6ku5u_8tXu2xeZweVtdEzvFvdNNfAf
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@transact-logs.com  ← After domain verification!
MAIL_FROM_NAME=NWSSU Logs System
```

---

## 📋 Step-by-Step Action Plan

### For Local Development (Do This Now):

1. ✅ Get Mailtrap SMTP credentials
2. ✅ Update `.env` file with credentials
3. ✅ Run: `php artisan config:clear`
4. ✅ Test: `php test-email.php test@example.com`
5. ✅ Verify email appears in Mailtrap inbox

### For Production (Do Before Deploying):

**Choose ONE option:**

**Option A: Verify with Resend (Recommended)**
1. Go to Resend → Add domain `transact-logs.com`
2. Add DNS records to your domain
3. Wait for verification (15-30 min)
4. Update Railway env: `MAIL_FROM_ADDRESS=noreply@transact-logs.com`
5. Redeploy to Railway

**Option B: Verify with Mailtrap**
1. In Mailtrap → Verify `transact-logs.com` domain
2. Add DNS records
3. Get API token
4. Update Railway env variables with Mailtrap settings
5. Redeploy to Railway

---

## 🔧 How to Update Railway Environment Variables

1. Go to: https://railway.app
2. Select your project: **logs-server-system-production**
3. Click on your service
4. Go to **"Variables"** tab
5. Edit the email-related variables:
   - `MAIL_MAILER`
   - `MAIL_FROM_ADDRESS`
   - `MAIL_HOST` (if using Mailtrap)
   - `MAIL_PORT` (if using Mailtrap)
   - etc.
6. Click **"Deploy"** to restart with new config

---

## 🧪 Testing

### Test Local (Mailtrap):
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan config:clear
php test-email.php test@example.com
```
Check: Mailtrap inbox

### Test Production (Railway):
1. Deploy to Railway
2. Use your production URL
3. Try "Forgot Password" with any email
4. Check actual email inbox

---

## ✅ Success Criteria

### Local Development:
- [ ] Mailtrap credentials added to `.env`
- [ ] Test email appears in Mailtrap inbox
- [ ] OTP emails work in local app
- [ ] Transaction emails work in local app

### Production:
- [ ] Domain verified with Resend OR Mailtrap
- [ ] Railway environment variables updated
- [ ] `MAIL_FROM_ADDRESS` uses verified domain
- [ ] Real emails arrive in user inboxes
- [ ] No "Resend sandbox" errors

---

## 💡 Key Points

1. **Local = Mailtrap Sandbox** (for testing, emails don't leave Mailtrap)
2. **Production = Resend OR Mailtrap Sending** (real emails, requires domain verification)
3. **Domain verification is REQUIRED** for production to work without restrictions
4. **Don't use `onboarding@resend.dev` in production** after verifying your domain

---

## 🆘 Need Help?

**For Local Setup:**
- Follow: `MAILTRAP_SETUP_STEPS.md`

**For Domain Verification:**
- Resend: https://resend.com/docs/dashboard/domains/introduction
- Mailtrap: https://help.mailtrap.io/article/69-sending-domain-setup

**For Railway:**
- Railway Docs: https://docs.railway.app/guides/variables

---

**Summary:**
- ✅ Local dev uses Mailtrap (get SMTP credentials and add to `.env`)
- ✅ Production uses Resend OR Mailtrap with verified domain
- ✅ Verify `transact-logs.com` before going to production
- ✅ Update `MAIL_FROM_ADDRESS` to use verified domain
