# Complete Domain Setup Guide: transactlogs.pro + Resend

## ✅ Step 1: Domain Purchased (DONE!)
Your domain `transactlogs.pro` is now active in Railway!

---

## 📧 Step 2: Add Domain to Resend (DO THIS NOW)

### **Action: Add Your Domain to Resend Dashboard**

1. **Go to Resend Dashboard:**
   - Open: https://resend.com/domains
   - Login to your Resend account

2. **Add Domain:**
   - Click **"Add Domain"** button
   - Enter: `transactlogs.pro`
   - Click **"Add"**

3. **Resend Will Show DNS Records:**
   
   You'll see something like this (example):

   ```
   ┌─────────────────────────────────────────────────────────┐
   │  Add these DNS records to verify your domain           │
   ├─────────────────────────────────────────────────────────┤
   │                                                         │
   │  1. SPF Record                                          │
   │     Type: TXT                                           │
   │     Name: @                                             │
   │     Value: v=spf1 include:_spf.resend.com ~all         │
   │                                                         │
   │  2. DKIM Record                                         │
   │     Type: TXT                                           │
   │     Name: resend._domainkey                             │
   │     Value: k=rsa; p=MIGfMA0GCSqGSIb3DQEBA...          │
   │                                                         │
   │  3. Verification Record                                 │
   │     Type: TXT                                           │
   │     Name: _resend                                       │
   │     Value: resend_verify_abc123xyz789                   │
   │                                                         │
   └─────────────────────────────────────────────────────────┘
   ```

4. **KEEP THIS WINDOW OPEN!**
   - You'll need these records for the next step
   - Don't close the Resend tab

---

## 🌐 Step 3: Add DNS Records to Railway

### **Where to Add DNS Records in Railway:**

Looking at your screenshot, I can see:
- ✅ Domain is added: `transactlogs.pro`
- ⚠️ Status: "Waiting for DNS to propagate..."

This means Railway is waiting for DNS records.

### **How to Add DNS Records:**

#### **Option A: Railway Manages DNS (Recommended)**

If Railway is your domain registrar (you bought from Railway):

1. **In Railway Dashboard:**
   - You're already on the Settings page
   - Look for **"DNS Settings"** or **"DNS Records"** section
   - If you don't see it, the domain might need to finish initial setup

2. **Wait for Domain Activation:**
   - Sometimes takes 5-10 minutes after purchase
   - Railway will send confirmation email
   - Check the domain status in Railway

3. **Add Custom DNS Records:**
   - Once domain is active, you can add TXT records
   - Add the 3 records from Resend

#### **Option B: External DNS Provider**

If you need to use external DNS (Cloudflare, etc.):

1. **Get Railway Nameservers:**
   - Railway will provide nameservers
   - Usually shown in domain settings

2. **Point Domain to DNS Provider:**
   - Add nameservers to your domain
   - Then add Resend DNS records in DNS provider

---

## 📝 Step 4: Copy DNS Records from Resend

### **What You Need to Copy:**

Once Resend shows you the DNS records, copy them **EXACTLY** as shown.

Here's what each record does:

### **1. SPF Record (Email Sender Authorization)**
```
Type: TXT
Name: @ (or leave blank)
Value: v=spf1 include:_spf.resend.com ~all
```
**Purpose:** Tells email providers that Resend is authorized to send emails from your domain.

### **2. DKIM Record (Email Authentication)**
```
Type: TXT
Name: resend._domainkey
Value: [Long cryptographic key from Resend]
```
**Purpose:** Cryptographically signs emails to prove they're legitimate.

### **3. Verification Record (Domain Ownership)**
```
Type: TXT
Name: _resend
Value: [Verification code from Resend]
```
**Purpose:** Proves you own the domain.

---

## ⏱️ Step 5: Wait for DNS Propagation

### **What Happens Now:**

1. **DNS Propagation Time:**
   - Minimum: 5-10 minutes
   - Average: 30 minutes
   - Maximum: 24-48 hours (rare)

2. **Check Verification Status:**
   - Go back to Resend dashboard
   - Your domain will show:
     - ⏳ "Pending Verification" → Still waiting
     - ✅ "Verified" → Ready to use!

3. **How to Speed Up:**
   - Wait 5 minutes
   - Refresh Resend dashboard
   - Click "Verify" button if available

---

## ✉️ Step 6: Update Railway Environment Variables

### **Once Domain is Verified in Resend:**

Go to Railway → Your Service → **Variables** → **Raw Editor**

**Replace everything with this:**

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

RESEND_API_KEY=re_WvNJX4EK_CW73RVF8nt1QFhKs7fRTDarW
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@transactlogs.pro
MAIL_FROM_NAME=NWSSU Logs System
```

### **Key Changes:**
- ✅ Keep `RESEND_API_KEY` (no quotes!)
- ✅ Keep `MAIL_MAILER=resend`
- ✅ Change `MAIL_FROM_ADDRESS` to: `noreply@transactlogs.pro`
- ✅ Or use: `support@transactlogs.pro`, `notifications@transactlogs.pro`, etc.

### **Save and Redeploy:**
- Click "Save" in Railway
- Wait 2-3 minutes for automatic redeployment

---

## 🧪 Step 7: Test Email Sending

### **Test 1: Forgot Password (Client Module)**

1. Go to your Client Module login page
2. Click "Forgot Password"
3. Enter ANY email address (gmail, yahoo, etc.)
4. Click "Send OTP"
5. Check email inbox (and spam folder)

**Expected Result:** ✅ Email arrives from `noreply@transactlogs.pro`

### **Test 2: Check Email Headers**

When you receive the email:
1. Open the email
2. View email headers ("Show Original" in Gmail)
3. Look for:
   ```
   From: noreply@transactlogs.pro
   SPF: PASS
   DKIM: PASS
   ```

### **Test 3: Multiple Recipients**

Test with different email providers:
- ✅ Gmail: test@gmail.com
- ✅ Yahoo: test@yahoo.com
- ✅ Outlook: test@outlook.com
- ✅ Student emails: test@student.com

All should work! 🎉

---

## 🔍 Troubleshooting

### **Issue: Domain Not Showing DNS Settings in Railway**

**Solution:**
1. Wait 10 minutes after purchase
2. Check Railway email for confirmation
3. Refresh Railway dashboard
4. Contact Railway support if domain status stuck

### **Issue: Resend Says "Domain Not Verified"**

**Solution:**
1. Check DNS records are added correctly
2. Wait 30 minutes for propagation
3. Click "Verify" button in Resend dashboard
4. Use DNS checker: https://mxtoolbox.com/SuperTool.aspx

### **Issue: Emails Still Not Sending**

**Solution:**
1. Check Railway logs for errors:
   ```
   railway logs
   ```
2. Verify `RESEND_API_KEY` has no quotes
3. Verify `MAIL_FROM_ADDRESS` uses your domain
4. Check Resend dashboard for errors

### **Issue: Emails Going to Spam**

**Solution:**
1. Ensure all 3 DNS records are added (SPF, DKIM, Verification)
2. Wait for DNS propagation (24-48 hours for best results)
3. Warm up domain by sending to yourself first
4. Add DMARC record (optional, advanced)

---

## 📊 DNS Verification Checklist

Use this to verify your DNS records:

```
□ SPF Record Added
  Type: TXT
  Name: @
  Value: v=spf1 include:_spf.resend.com ~all

□ DKIM Record Added
  Type: TXT
  Name: resend._domainkey
  Value: [Long key from Resend]

□ Verification Record Added
  Type: TXT
  Name: _resend
  Value: [Verification code from Resend]

□ DNS Propagated (wait 5-30 minutes)

□ Domain Verified in Resend Dashboard

□ Railway Environment Variables Updated

□ Application Redeployed

□ Test Email Sent Successfully
```

---

## 🎯 Quick Reference

### **Resend Dashboard:**
https://resend.com/domains

### **Railway DNS Status:**
Check your current page - look for "DNS propagated" status

### **Environment Variables:**
```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@transactlogs.pro
RESEND_API_KEY=re_your_key_here
```

### **Email Addresses You Can Use:**
- `noreply@transactlogs.pro` (recommended)
- `support@transactlogs.pro`
- `notifications@transactlogs.pro`
- `admin@transactlogs.pro`
- `otp@transactlogs.pro`

All will work once domain is verified!

---

## 🚀 Expected Timeline

| Step | Time Required | Status |
|------|---------------|--------|
| Buy Domain | Instant | ✅ DONE |
| Add to Resend | 2 minutes | ⏳ Next |
| Add DNS Records | 5 minutes | ⏳ Waiting |
| DNS Propagation | 5-30 minutes | ⏳ Waiting |
| Update Variables | 2 minutes | ⏳ Waiting |
| Test Emails | 2 minutes | ⏳ Waiting |
| **TOTAL** | **~30-45 minutes** | |

---

## ✅ Success Criteria

You'll know everything is working when:

1. ✅ Resend dashboard shows domain as "Verified"
2. ✅ Railway shows domain as "Active" 
3. ✅ Test email arrives in inbox
4. ✅ Email headers show SPF and DKIM passing
5. ✅ Staff emails still work
6. ✅ User/client emails now work
7. ✅ No whitelist restrictions!

---

## 📧 Professional Email Setup (Bonus)

### **Recommended From Addresses:**

For different email types:

```env
# OTP/Password Reset
MAIL_FROM_ADDRESS=noreply@transactlogs.pro

# Support Emails
MAIL_FROM_ADDRESS=support@transactlogs.pro

# Notifications
MAIL_FROM_ADDRESS=notifications@transactlogs.pro

# Announcements
MAIL_FROM_ADDRESS=announcements@transactlogs.pro
```

You can use ANY email @transactlogs.pro once domain is verified!

---

## 🎓 Why This Is Better for Thesis

### **Before (with Gmail):**
```
From: youremail@gmail.com
Subject: Password Reset OTP
```
❌ Looks unprofessional
❌ Might go to spam
❌ Can't showcase on portfolio

### **After (with Custom Domain):**
```
From: noreply@transactlogs.pro
Subject: Password Reset OTP
```
✅ Professional appearance
✅ Better deliverability
✅ Perfect for thesis defense
✅ Impressive to panelists
✅ Portfolio-ready

---

## Next Steps Summary

1. **Now:** Go to https://resend.com/domains and add `transactlogs.pro`
2. **Copy:** DNS records from Resend
3. **Wait:** For Railway to allow DNS configuration
4. **Add:** DNS records in Railway
5. **Wait:** 5-30 minutes for propagation
6. **Update:** Railway environment variables
7. **Test:** Send OTP email to any address
8. **Success:** Emails work without whitelist! 🎉

**Let me know when you get to each step and need help!**
