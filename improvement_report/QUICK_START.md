# Quick Reference: Running PHP on Remote

## ⚡ TL;DR

GitHub Pages = **Static only** (HTML, CSS, JS)
- ❌ PHP won't work
- ❌ No server-side code

## 🎯 Recommended Solution (5 minutes)

### 1. Use Railway (Free)
```bash
# 1. Sign up at railway.app with GitHub
# 2. Connect your GitHub repo
# 3. Set environment variables
# 4. Auto-deploys when you push

# Your backend URL:
https://your-app-railway.app/api/contact
```

### 2. Update Your Form
```html
<!-- Change this line in index.html -->
<form action="forms/contact.php" method="post">

<!-- To this: -->
<form action="https://your-app-railway.app/api/contact" method="post">
```

### 3. Create server.js
```javascript
const express = require('express');
const app = express();
require('dotenv').config();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.post('/api/contact', async (req, res) => {
  const { name, email, subject, message } = req.body;
  
  // Your email sending logic here
  
  res.json({ success: true });
});

app.listen(process.env.PORT || 3000);
```

### 4. Create package.json
```json
{
  "name": "sebby-contact-api",
  "main": "server.js",
  "scripts": { "start": "node server.js" },
  "dependencies": {
    "express": "^4.18.2",
    "nodemailer": "^6.9.3",
    "dotenv": "^16.3.1"
  }
}
```

### 5. Create .env
```
EMAIL_USER=sebbywakis@gmail.com
EMAIL_PASS=your-gmail-app-password
RECAPTCHA_SECRET=your-key
```

### 6. Push & Deploy
```bash
git add .
git commit -m "Add backend API"
git push origin main
# Railway auto-deploys!
```

---

## 📊 Quick Comparison

| Option | Cost | Ease | Recommended |
|--------|------|------|-------------|
| **Railway** | Free-$5 | ⭐⭐⭐ Easy | ✅ Best |
| **Vercel** | Free | ⭐⭐ Medium | ✅ Good |
| **Hostinger** | $3-5 | ⭐⭐⭐ Easy | ✅ Good |
| **GitHub Pages** | Free | ⭐ | ❌ No PHP |

---

## 🚀 Why Railway?

✅ Free tier available
✅ Auto-deploys from GitHub
✅ Supports Node.js, Python, PHP
✅ Easy env variables
✅ MySQL database included
✅ Simple pricing

---

## 📝 Architecture

```
GitHub Pages (Frontend)
    ↓
sebbywekesa.com
    ↓
Railway (Backend API)
    ↓
your-app.railway.app/api/contact
    ↓
Send Email via Gmail SMTP
```

---

## ✅ Checklist

- [ ] Create Railway account
- [ ] Connect GitHub repo
- [ ] Create server.js
- [ ] Create package.json
- [ ] Create .env with secrets
- [ ] Update form action URL
- [ ] Test contact form
- [ ] Monitor logs

---

## 🔗 Useful Links

- **Railway:** https://railway.app
- **Express.js:** https://expressjs.com
- **Nodemailer:** https://nodemailer.com
- **Gmail App Password:** https://support.google.com/accounts/answer/185833

---

## 💡 Next Steps

1. **Choose Platform:** Railway recommended
2. **Create Account:** Sign up with GitHub
3. **Deploy:** Follow steps above
4. **Test:** Send test email
5. **Monitor:** Check Railway logs

---

**Need help?** See DEPLOYMENT_GUIDE.md for detailed instructions
