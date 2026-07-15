# 📧 Paano Ayusin ang Email Problem

## 🔍 Ano ang Problema?

Gumagana ang OTP at transaction status emails para sa **staff emails** pero **hindi gumagana** para sa **regular user emails**.

### Bakit?

Gumagamit ka ng **Resend** email service na may **sandbox mode**:
- Email address: `onboarding@resend.dev`
- Sandbox mode = Limited lang ang pwedeng pagpadalan ng email
- ✅ Pwede: Whitelisted/verified emails lang (tulad ng staff emails mo)
- ❌ Hindi pwede: Regular user emails na hindi naka-whitelist

**HINDI ITO BUG** - restriction lang to ng Resend service!

---

## ✅ Mga Solusyon (Pumili ng Isa)

### **PINAKAMABILIS (5 minuto lang):** Gamitin ang Mailtrap para sa Testing

**Kapag testing/development pa lang:**

1. **Sign up sa Mailtrap** - https://mailtrap.io (FREE!)

2. **Kumuha ng credentials** mula sa Mailtrap inbox mo

3. **I-update ang `.env` file:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=lagay_mo_dito_mailtrap_username
   MAIL_PASSWORD=lagay_mo_dito_mailtrap_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@nwssu.edu.ph
   MAIL_FROM_NAME="NWSSU Logs System"
   ```

4. **I-clear ang cache:**
   ```bash
   cd c:\xampp\htdocs\Logs-server-system\logs-server
   php artisan config:clear
   php artisan cache:clear
   ```

5. **Subukan:**
   ```bash
   php test-email.php your-email@example.com
   ```

**BENEFITS:**
- ✅ Agad-agad gumagana
- ✅ Makikita mo lahat ng emails sa Mailtrap dashboard
- ✅ Walang domain verification needed
- ✅ Perfect para sa testing
- ⚠️ NOTE: Hindi aabot sa actual email inbox (pang-testing lang)

---

### **PARA SA PRODUCTION:** I-verify ang Domain mo sa Resend

**Kapag ready na i-deploy:**

1. **Pumunta sa Resend Dashboard**
   - https://resend.com/domains
   - Login gamit ang account mo

2. **I-add ang domain mo**
   - Click "Add Domain"
   - Ilagay ang domain (halimbawa: `nwssu.edu.ph`)
   - Sundin ang DNS verification steps
   - Maghintay ng 15-30 minutes para ma-verify

3. **I-update ang `.env` file:**
   ```env
   MAIL_MAILER=resend
   RESEND_API_KEY=your_api_key_here
   MAIL_FROM_ADDRESS=noreply@nwssu.edu.ph
   MAIL_FROM_NAME="NWSSU Logs System"
   ```

4. **I-restart ang server:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

**BENEFITS:**
- ✅ Pwede na magpadala sa LAHAT ng email addresses
- ✅ Professional sender address
- ✅ Production-ready
- ✅ Walang limit sa sending

---

### **ALTERNATIVE:** Gamitin ang Gmail (Simple pero may limit)

**Para sa testing lang with real emails:**

1. **I-enable ang 2-Step Verification** sa Gmail mo

2. **Gumawa ng App Password:**
   - Pumunta: https://myaccount.google.com/apppasswords
   - Generate new app password
   - I-copy ang 16-character password

3. **I-update ang `.env` file:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your_gmail@gmail.com
   MAIL_PASSWORD=16_character_app_password_mo
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your_gmail@gmail.com
   MAIL_FROM_NAME="NWSSU Logs System"
   ```

4. **I-clear ang cache:**
   ```bash
   php artisan config:clear
   ```

**NOTE:**
- ⚠️ May limit ng 100-500 emails per day lang
- ⚠️ Hindi recommended para sa production
- ✅ OK lang para sa testing

---

## 🧪 Paano I-test kung Gumagana na?

### 1. Test Email Configuration
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
php test-email.php user@example.com
```

Dapat makita mo:
- ✅ "SUCCESS! Test email sent successfully!"
- O ❌ "FAILED!" with error message

### 2. Test OTP Email
1. Pumunta sa login page
2. Click "Forgot Password"
3. Ilagay ang user email
4. Tingnan kung dumating ang OTP

### 3. Test Transaction Email
1. Login bilang admin/staff
2. Pumunta sa transactions page
3. I-approve ang isang pending transaction
4. Check ang user email inbox

### 4. Tingnan ang Logs
```bash
cd c:\xampp\htdocs\Logs-server-system\logs-server
tail -f storage/logs/laravel.log
```

**Hanapin sa logs:**
- ✅ `OTP Email sent successfully` = OK!
- ✅ `Transaction status email sent successfully` = OK!
- ❌ `Failed to send ... email` = May problema pa

---

## 🎯 Recommended na Gawin

### Para sa Development/Testing ngayon:
1. ✅ Gamitin ang **Mailtrap** (pinakamabilis)
2. I-test lahat ng email features
3. Tingnan ang emails sa Mailtrap dashboard

### Para sa Production deployment:
1. ✅ I-verify ang domain mo sa **Resend**
2. I-setup ang SPF/DKIM records
3. Gumamit ng professional sender address
4. I-test nang mabuti bago i-launch

---

## 🐛 Common na Problema at Solusyon

### Problema: "Connection timeout"
**Solusyon:** Check firewall or antivirus kung nag-block ng SMTP ports

### Problema: "Authentication failed"
**Solusyon:** I-verify ang API key o SMTP credentials sa `.env`

### Problema: "Sender address not verified"
**Solusyon:** I-complete ang domain verification sa email provider

### Problema: Pumupunta sa spam
**Solusyon:**
- I-verify ang domain with SPF/DKIM
- Gumamit ng professional sender address
- Iwasan ang spam trigger words

---

## 📋 Checklist Bago at Pagkatapos

### Bago ayusin:
- [x] Na-identify na ang root cause
- [x] May enhanced error logging na
- [x] May testing tools na available

### Para ayusin:
- [ ] Pumili ng email solution (Mailtrap o domain verification)
- [ ] I-update ang `.env` file
- [ ] I-run: `php artisan config:clear`
- [ ] I-test: `php test-email.php your-email@example.com`
- [ ] I-test ang OTP functionality
- [ ] I-test ang transaction approval emails
- [ ] I-check ang logs

### Pagkatapos ayusin:
- [ ] Lahat ng users nakakatanggap ng OTP
- [ ] Transaction emails umabot na
- [ ] Walang errors sa logs
- [ ] Frontend shows "Email notification sent"

---

## 💡 Mga Tip

1. **Always check logs** kapag hindi umabot ang email
2. **Use Mailtrap** para sa development
3. **Verify domain** bago mag-production
4. **Set up SPF/DKIM** para better deliverability
5. **Monitor emails** sa production

---

## ✨ Summary

**Problema:** Resend sandbox restrictions = hindi makapag-send sa non-whitelisted emails

**Solusyon:** I-verify ang domain sa Resend OR gumamit ng Mailtrap

**Status:** OK na ang code, kailangan lang ng proper email configuration

**Improvements:** May enhanced logging at error reporting na

---

## 📞 Kailangan ng Tulong?

Basahin ang:
- `EMAIL_FIX_GUIDE.md` - Detailed English guide
- `EMAIL_ISSUE_SUMMARY.md` - Complete analysis
- O check ang logs: `storage/logs/laravel.log`

---

**Quick Answer:** Gamitin ang Mailtrap para sa testing ngayon, tapos i-verify ang domain bago mag-production! 🚀
