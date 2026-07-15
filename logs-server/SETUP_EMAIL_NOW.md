# 🚀 Setup Email Now - Step by Step

## Choose Your Solution:

---

## ✅ OPTION 1: Mailtrap (RECOMMENDED FOR TESTING)

### Step 1: Get Mailtrap Credentials

1. Go to: **https://mailtrap.io**
2. Click **"Sign Up"** (FREE account)
3. Verify your email
4. After login, you'll see your inbox
5. Click on your inbox name
6. Go to **"SMTP Settings"**
7. Select **"Laravel 9+"** from the dropdown
8. You'll see credentials like this:
   ```
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=abc123def456
   MAIL_PASSWORD=xyz789ghi012
   ```

### Step 2: Update Your .env File

Open: `c:\xampp\htdocs\Logs-server-system\logs-server\.env`

Find these lines:
```env
RESEND_API_KEY=re_hepQRdkj_7awHSaRTmpEAUiUi5yNyGf4m
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="NWSSU Logs System"
```

Replace with:
```env
# Comment out Resend
# RESEND_API_KEY=re_hepQRdkj_7awHSaRTmpEAUiUi5yNyGf4m

# Mailtrap Configuration
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=YOUR_MAILTRAP_USERNAME
MAIL_PASSWORD=YOUR_MAILTRAP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@nwssu.edu.ph
MAIL_FROM_NAME="NWSSU Logs System"
```

Replace `YOUR_MAILTRAP_USERNAME` and `YOUR_MAILTRAP_PASSWORD` with your actual credentials.

### Step 3: Clear Cache

```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test It!

```bash
php test-email.php your-email@example.com
```

You should see: ✅ **SUCCESS!**

### Step 5: Check Mailtrap Dashboard

1. Go back to https://mailtrap.io
2. Click on your inbox
3. You'll see the test email there!

### Step 6: Test in Your App

1. **Test OTP:**
   - Go to login page
   - Click "Forgot Password"
   - Enter any email
   - Check Mailtrap dashboard for OTP

2. **Test Transaction Email:**
   - Login as admin
   - Approve a transaction
   - Check Mailtrap dashboard for status email

---

## ✅ OPTION 2: Verify Domain with Resend (PRODUCTION)

### Step 1: Go to Resend Dashboard

1. Visit: **https://resend.com/domains**
2. Login with your account

### Step 2: Add Your Domain

1. Click **"Add Domain"**
2. Enter your domain (e.g., `nwssu.edu.ph`)
3. Click "Add"

### Step 3: Verify Domain

Resend will show you DNS records to add:

**TXT Record:**
```
Name: _resend
Value: (they'll provide this)
```

**CNAME Records:**
```
Name: resend._domainkey
Value: (they'll provide this)

Name: resend2._domainkey
Value: (they'll provide this)
```

Add these records to your domain's DNS settings (contact your IT admin if needed).

### Step 4: Wait for Verification

- Check back in 15-30 minutes
- Click "Verify" button in Resend dashboard
- Status should change to "Verified"

### Step 5: Update .env File

Open: `c:\xampp\htdocs\Logs-server-system\logs-server\.env`

Change:
```env
MAIL_FROM_ADDRESS=onboarding@resend.dev
```

To:
```env
MAIL_FROM_ADDRESS=noreply@nwssu.edu.ph
```
(or any email using your verified domain)

### Step 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 7: Test It!

```bash
php test-email.php your-real-email@example.com
```

Email should arrive in actual inbox now!

---

## ✅ OPTION 3: Gmail SMTP (SIMPLE BUT LIMITED)

### Step 1: Enable 2-Step Verification

1. Go to: **https://myaccount.google.com/security**
2. Enable "2-Step Verification" if not enabled

### Step 2: Generate App Password

1. Go to: **https://myaccount.google.com/apppasswords**
2. Select "Mail" and "Windows Computer"
3. Click "Generate"
4. Copy the 16-character password (no spaces)

### Step 3: Update .env File

Open: `c:\xampp\htdocs\Logs-server-system\logs-server\.env`

Find:
```env
RESEND_API_KEY=re_hepQRdkj_7awHSaRTmpEAUiUi5yNyGf4m
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="NWSSU Logs System"
```

Replace with:
```env
# Comment out Resend
# RESEND_API_KEY=re_hepQRdkj_7awHSaRTmpEAUiUi5yNyGf4m

# Gmail SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="NWSSU Logs System"
```

### Step 4: Clear Cache

```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan config:clear
php artisan cache:clear
```

### Step 5: Test It!

```bash
php test-email.php your-email@example.com
```

Email should arrive in real inbox!

**⚠️ NOTE:** Gmail limits:
- 100 emails per day for free Gmail
- 500 emails per day for Google Workspace

---

## 🎯 Which One Should I Use?

| Feature | Mailtrap | Resend Domain | Gmail |
|---------|----------|---------------|-------|
| Setup Time | 5 min | 30 min | 10 min |
| Cost | FREE | FREE | FREE |
| Real Emails | ❌ No | ✅ Yes | ✅ Yes |
| Production Ready | ❌ No | ✅ Yes | ⚠️ Limited |
| Email Dashboard | ✅ Yes | ❌ No | ❌ No |
| Best For | Testing | Production | Small Projects |

**My Recommendation:**
1. **Right now:** Use Mailtrap (fastest for testing)
2. **Before production:** Verify domain with Resend

---

## 🐛 Troubleshooting

### "Connection timeout"
- Check if firewall is blocking SMTP ports
- Try changing MAIL_PORT to 465 or 2525

### "Authentication failed"
- Double-check username/password
- For Gmail: Make sure you used App Password, not regular password

### "Address not verified" (Resend only)
- Complete domain verification in Resend dashboard
- Wait 15-30 minutes after adding DNS records

### Still not working?
```bash
# Check detailed logs
tail -f storage/logs/laravel.log

# Test with debug
php test-email.php your-email@example.com
```

---

## ✅ Success Checklist

After setup, test these:
- [ ] `php test-email.php` works
- [ ] OTP emails arrive (check Mailtrap or real inbox)
- [ ] Transaction approval emails arrive
- [ ] No errors in `storage/logs/laravel.log`
- [ ] Frontend shows "Email notification sent"

---

**Need help?** Let me know which option you chose and any errors you see!
