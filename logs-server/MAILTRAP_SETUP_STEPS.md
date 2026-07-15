# 🚀 Mailtrap Setup - Follow These Steps

## ✅ I've already updated your `.env` file!

Your `.env` file has been configured for Mailtrap. You just need to get your credentials and fill them in.

---

## 📋 Step-by-Step Instructions

### Step 1: Sign Up for Mailtrap (2 minutes)

1. **Go to:** https://mailtrap.io
2. **Click:** "Sign Up" button (top right)
3. **Choose:** Sign up with Email/Google/GitHub
4. **Verify:** Check your email and verify your account

✅ **It's completely FREE!**

---

### Step 2: Get Your SMTP Credentials (1 minute)

After logging in to Mailtrap:

1. You'll see your **Inbox** on the dashboard
2. Click on the **inbox name** (usually "My Inbox" or "Demo Inbox")
3. Look for **"SMTP Settings"** tab
4. In the dropdown, select **"Laravel 9+"**
5. You'll see credentials like this:

```env
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=1a2b3c4d5e6f7g    ← Copy this
MAIL_PASSWORD=9h8i7j6k5l4m3n    ← Copy this
```

**Copy the USERNAME and PASSWORD** - you'll need them next!

---

### Step 3: Update Your .env File (1 minute)

1. **Open:** `c:\xampp\htdocs\Logs-server-system\logs-server\.env`

2. **Find these lines** (I already added them for you):
```env
MAIL_USERNAME=YOUR_MAILTRAP_USERNAME_HERE
MAIL_PASSWORD=YOUR_MAILTRAP_PASSWORD_HERE
```

3. **Replace** `YOUR_MAILTRAP_USERNAME_HERE` with your actual username
4. **Replace** `YOUR_MAILTRAP_PASSWORD_HERE` with your actual password

**Example:**
```env
MAIL_USERNAME=1a2b3c4d5e6f7g
MAIL_PASSWORD=9h8i7j6k5l4m3n
```

5. **Save the file** (Ctrl + S)

---

### Step 4: Clear Laravel Cache (30 seconds)

Open Command Prompt or Terminal and run:

```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php artisan config:clear
php artisan cache:clear
```

You should see:
```
Configuration cache cleared successfully.
Application cache cleared successfully.
```

---

### Step 5: Test the Email (1 minute)

Run the test email script:

```bash
php test-email.php your-email@example.com
```

**Expected output:**
```
📧 Testing Email Configuration
================================

Configuration:
  Mail Driver: smtp
  From Address: noreply@nwssu.edu.ph
  From Name: NWSSU Logs System
  To Address: your-email@example.com

Sending test OTP email...
✅ SUCCESS! Test email sent successfully!

Next steps:
  1. Check the recipient's inbox: your-email@example.com
  2. Check spam/junk folder if not in inbox
  3. Verify OTP code is: 123456
  4. Check Laravel logs: storage/logs/laravel.log
```

---

### Step 6: Check Mailtrap Dashboard (View the Email!)

1. **Go back to:** https://mailtrap.io
2. **Click:** Your inbox
3. **You should see** the test email! 📧
4. **Click on it** to view the email content

✅ **If you see the email, it's working!**

---

### Step 7: Test in Your Application

#### Test 1: OTP Email (Forgot Password)

1. Open your application in browser
2. Go to **Login page**
3. Click **"Forgot Password"**
4. Enter **any email address** (e.g., `student@test.com`)
5. Click **"Send OTP"**
6. **Check Mailtrap inbox** - you should see the OTP email!

#### Test 2: Transaction Status Email

1. Login as **Admin/Staff**
2. Go to **Transactions page**
3. Find a **pending transaction**
4. Click **"Approve"** button
5. **Check Mailtrap inbox** - you should see the approval email!

---

## 🎉 What You'll See in Mailtrap

All emails sent from your application will appear in your Mailtrap inbox:
- ✅ OTP emails
- ✅ Transaction approval emails
- ✅ Transaction rejection emails
- ✅ Transaction completion emails

**You can view:**
- Full email HTML/Text content
- Email headers
- Spam score
- HTML/CSS validation

---

## 🐛 Troubleshooting

### Problem: "Authentication failed"
**Solution:**
- Double-check username and password in `.env`
- Make sure there are no extra spaces
- Copy-paste directly from Mailtrap dashboard

### Problem: "Connection timeout"
**Solution:**
- Check if antivirus/firewall is blocking port 2525
- Try using port 587 instead: `MAIL_PORT=587`

### Problem: Test email script shows error
**Solution:**
```bash
# Clear cache again
php artisan config:clear

# Check logs
tail -f storage/logs/laravel.log

# Verify .env is saved
cat .env | grep MAIL
```

### Problem: Emails not showing in Mailtrap
**Solution:**
- Refresh the Mailtrap dashboard
- Check if you're looking at the correct inbox
- Verify the test actually ran successfully

---

## ✅ Success Checklist

After completing all steps, verify:

- [ ] Mailtrap account created
- [ ] SMTP credentials copied
- [ ] `.env` file updated with real credentials
- [ ] Cache cleared (`php artisan config:clear`)
- [ ] Test email script runs successfully
- [ ] Email appears in Mailtrap inbox
- [ ] Forgot Password OTP works
- [ ] Transaction approval email works

---

## 📊 Current Configuration Summary

**Your .env now has:**
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=YOUR_MAILTRAP_USERNAME_HERE  ← Replace this
MAIL_PASSWORD=YOUR_MAILTRAP_PASSWORD_HERE  ← Replace this
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@nwssu.edu.ph
MAIL_FROM_NAME="NWSSU Logs System"
```

**What you need to do:**
1. Get credentials from Mailtrap
2. Replace the placeholders
3. Clear cache
4. Test!

---

## 🚀 Next Steps After Testing

Once everything works with Mailtrap:

**For Production Deployment:**
- Switch to Resend with verified domain
- Or use a production SMTP service
- See `EMAIL_FIX_GUIDE.md` for production setup

**For Now:**
- Keep using Mailtrap for all testing
- All emails will be caught by Mailtrap
- No emails will actually be sent to users (safe for testing!)

---

## 💡 Pro Tips

1. **Bookmark Mailtrap dashboard** - you'll use it often
2. **Create separate inboxes** for different projects
3. **Use Mailtrap's API** if you need automated testing
4. **Check spam score** to ensure production emails won't be spam

---

## 🎯 Quick Commands Reference

```bash
# Navigate to project
cd c:\xampp\htdocs\Logs-server-system\logs-server

# Clear cache
php artisan config:clear
php artisan cache:clear

# Test email
php test-email.php test@example.com

# View logs
tail -f storage/logs/laravel.log

# Check mail config
php artisan tinker
>>> config('mail')
```

---

**That's it! You're all set up! 🎉**

Any questions? Just check the Mailtrap inbox after triggering any email in your app!
