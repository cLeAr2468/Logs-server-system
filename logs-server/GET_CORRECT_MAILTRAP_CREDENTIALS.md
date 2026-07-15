# 🎯 Getting the CORRECT Mailtrap Credentials

## ⚠️ Current Issue

You're using **API credentials** instead of **SMTP inbox credentials**:
```env
MAIL_USERNAME=api  ← This is for Sending API, not testing
MAIL_PASSWORD=a72fd2debe8cc98c9220d85daea353b1
```

You need **SMTP credentials from your INBOX** instead!

---

## ✅ Step-by-Step: Get CORRECT Credentials

### Step 1: Go to Mailtrap Inboxes

1. Open: https://mailtrap.io/inboxes
2. You should see your inbox (probably named "My Inbox" or "Demo Inbox")

### Step 2: Click on Your INBOX (NOT Domains)

**IMPORTANT:** 
- ❌ **DON'T** go to "Domains" section
- ✅ **DO** go to "Inboxes" section
- Click on the inbox name to open it

### Step 3: Find SMTP Settings Tab

Inside the inbox, you'll see these tabs:
- Messages
- **SMTP Settings** ← Click this one!
- HTTP API
- etc.

### Step 4: Select "Laravel 9+" Integration

In the SMTP Settings page:
1. Look for a dropdown that says "Integration" or "Select Integration"
2. Select **"Laravel 9+"** from the list

### Step 5: Copy the CORRECT Credentials

You'll now see Laravel-specific configuration like this:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=1a2b3c4d5e6f7g  ← Copy THIS (it's a random string)
MAIL_PASSWORD=9h8i7j6k5l4m3n  ← Copy THIS (another random string)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**The username will look like:** `1a2b3c4d5e6f7g` or similar random characters
**The password will look like:** `9h8i7j6k5l4m3n` or similar random characters

### Step 6: Update Your .env File

Replace in your `.env` file:

**WRONG (what you have now):**
```env
MAIL_USERNAME=api
MAIL_PASSWORD=a72fd2debe8cc98c9220d85daea353b1
```

**RIGHT (what you need):**
```env
MAIL_USERNAME=your_actual_inbox_username
MAIL_PASSWORD=your_actual_inbox_password
```

---

## 🔍 How to Tell the Difference

### SMTP Inbox Credentials (for testing):
```
Location: Inboxes → Your Inbox → SMTP Settings
Username: Random alphanumeric string (like: 1a2b3c4d5e6f7g)
Password: Random alphanumeric string (like: 9h8i7j6k5l4m3n)
Host: sandbox.smtp.mailtrap.io
Port: 2525
Purpose: For testing emails locally
```

### API Token (for production sending):
```
Location: Domains → Your Domain → Settings → API Tokens
Username: "api" (literally the word "api")
Password: Long hexadecimal string (like: a72fd2debe8cc98c9220d85daea353b1)
Host: live.smtp.mailtrap.io
Port: 587
Purpose: For sending real emails in production
```

**You need the FIRST one (SMTP Inbox Credentials) for local testing!**

---

## 📊 Visual Guide

```
Mailtrap Dashboard
├── 📧 Inboxes ← Go here for testing
│   └── My Inbox
│       └── SMTP Settings ← Get credentials here
│           ├── Username: 1a2b3c4d5e6f7g
│           └── Password: 9h8i7j6k5l4m3n
│
└── 🌐 Domains ← Go here later for production
    └── transact-logs.com
        └── API Tokens ← NOT what you need now
            ├── Username: api
            └── Password: a72fd2... (This is what you're using - wrong for now!)
```

---

## ✅ Quick Checklist

After getting the right credentials:

1. [ ] Went to **Inboxes** (not Domains)
2. [ ] Clicked on inbox name
3. [ ] Clicked **SMTP Settings** tab
4. [ ] Selected **Laravel 9+** integration
5. [ ] Copied **USERNAME** (random string, not "api")
6. [ ] Copied **PASSWORD** (random string)
7. [ ] Updated `.env` file
8. [ ] Saved the file
9. [ ] Run: `php artisan config:clear`
10. [ ] Run: `php test-email.php test@example.com`

---

## 🧪 After Updating Credentials

Run these commands:

```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan config:clear
php test-email.php test@example.com
```

**Expected Success Output:**
```
📧 Testing Email Configuration
================================

Configuration:
  Mail Driver: smtp
  From Address: noreply@nwssu.edu.ph
  From Name: NWSSU Logs System
  To Address: test@example.com

Sending test OTP email...
✅ SUCCESS! Test email sent successfully!

Next steps:
  1. Check the recipient's inbox: test@example.com
  2. Check spam/junk folder if not in inbox
  3. Verify OTP code is: 123456
  4. Check Laravel logs: storage/logs/laravel.log
```

Then check your Mailtrap inbox - the email should appear there!

---

## 🐛 Still Getting Errors?

### Error: "Authentication failed"
- Double-check you copied the username and password correctly
- Make sure there are no extra spaces
- Username should NOT be "api"

### Error: "Connection timeout"
- Check if firewall is blocking port 2525
- Try using port 587 instead: `MAIL_PORT=587`

### Error: "Could not connect to host"
- Verify `MAIL_HOST=sandbox.smtp.mailtrap.io`
- Check your internet connection

---

## 💡 Remember

**For Local Testing (now):**
- Use **SMTP Inbox credentials**
- Host: `sandbox.smtp.mailtrap.io`
- Port: `2525`
- Username: Random string (NOT "api")

**For Production (later):**
- Use **API Token** or verify domain
- Host: `live.smtp.mailtrap.io`  
- Port: `587`
- Username: `api`

---

**Go get those inbox SMTP credentials and update your `.env` file! 🚀**
