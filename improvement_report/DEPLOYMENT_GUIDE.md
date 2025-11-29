# 🚀 How to Run PHP Code on Your Remote Repository

## Important: GitHub Pages Limitation ⚠️

**GitHub Pages only serves static content (HTML, CSS, JavaScript).** It **does NOT support PHP** or any server-side languages.

Your repository (`sebby-wekesa/github.io`) is a GitHub Pages site, which means:
- ❌ PHP files won't execute
- ❌ Server-side code won't run
- ✅ Only static HTML/CSS/JS works

---

## 🎯 Solutions to Run PHP on Remote

### Option 1: Use a Separate Hosting Service (⭐ Recommended)

#### A. Hostinger / Bluehost / GoDaddy
- **Cost:** $3-10/month
- **Includes:** PHP support, MySQL, FTP
- **Setup:** 
  1. Buy hosting plan
  2. Upload files via FTP/SFTP
  3. Configure domain
  4. PHP runs automatically

#### B. Heroku (Free tier deprecated, but still available)
- **Cost:** Free to paid
- **Setup:**
  ```bash
  # Install Heroku CLI
  npm install -g heroku
  
  # Login
  heroku login
  
  # Create app
  heroku create your-app-name
  
  # Deploy
  git push heroku main
  ```

#### C. Railway / Render / PythonAnywhere
- **Cost:** Free tier available
- **Perfect for:** Hobby projects
- **Setup:** Connect GitHub, auto-deploy

---

### Option 2: Split Your Project Structure

#### A. Keep GitHub Pages for Frontend
```
your-site.com (GitHub Pages - Static)
├── index.html
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
└── forms/
    └── contact-form.html (static form)
```

#### B. Move PHP Backend to Separate Service
```
api.your-site.com (PHP Hosting)
├── contact.php
├── email-form.php
└── config.php
```

#### C. Connect Frontend to Backend
```html
<!-- In your HTML form -->
<form action="https://api.your-site.com/forms/contact.php" method="POST">
  <!-- form fields -->
</form>
```

---

### Option 3: Use Serverless Functions (Modern Approach)

#### A. Vercel Functions (JavaScript)
Convert PHP to Node.js:
```javascript
// api/contact.js
module.exports = async (req, res) => {
  if (req.method === 'POST') {
    const { name, email, message } = req.body;
    
    // Process form
    // Send email
    
    res.status(200).json({ success: true });
  }
};
```

#### B. AWS Lambda
- Run PHP via custom runtime
- Scales automatically
- Pay per execution

#### C. Firebase Cloud Functions
- Easy integration
- Free tier available
- JavaScript/TypeScript based

---

## 📋 Recommended Setup for Your Project

### Step-by-Step Guide

#### 1. Keep Frontend on GitHub Pages
```bash
# Your current setup remains unchanged
# GitHub Pages serves: index.html, CSS, JS
```

#### 2. Move PHP to Separate Hosting

**Choose one:**
- **Option A: Shared Hosting** (Hostinger, Bluehost)
  - Easiest to set up
  - Supports PHP natively
  - ~$5/month

- **Option B: Vercel** (Free)
  - Convert PHP to JavaScript
  - Automatic deployment
  - Perfect for serverless

- **Option C: Railway** (Free)
  - Connect GitHub
  - Auto-deploys changes
  - Supports PHP

#### 3. Update Form Action

**Current HTML (forms/contact.php):**
```html
<form action="forms/contact.php" method="post">
```

**Update to point to remote PHP:**
```html
<!-- If using Hostinger at example.com -->
<form action="https://example.com/forms/contact.php" method="post">

<!-- If using Vercel API -->
<form action="https://your-api.vercel.app/api/contact" method="post">

<!-- If using Railway -->
<form action="https://your-app.railway.app/forms/contact.php" method="post">
```

---

## 🔧 Best Practice: Convert PHP to JavaScript (Node.js)

This works with **Vercel, Railway, Render, etc.**

### Your contact.php → contact.js (Node.js)

#### Original PHP:
```php
<?php
$receiving_email_address = 'sebbywakis@gmail.com';
$php_email_form_path = '../assets/vendor/php-email-form/php-email-form.php';

if( file_exists($php_email_form_path )) {
    include( $php_email_form_path );
} else {
    die( 'Unable to load the "PHP Email Form" Library!');
}

$contact = new PHP_Email_Form($receiving_email_address);
$contact->set_from_name($_POST['name']);
$contact->set_from_email($_POST['email']);
$contact->set_subject($_POST['subject']);
$contact->add_message($_POST['message'], 'Message', 10);

if ($contact->send()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'errors' => $contact->get_errors()]);
}
?>
```

#### Converted to Node.js/Express:
```javascript
const express = require('express');
const nodemailer = require('nodemailer');
const app = express();

app.use(express.json());

app.post('/api/contact', async (req, res) => {
  try {
    const { name, email, subject, message } = req.body;
    
    // Validate inputs
    if (!name || !email || !subject || !message) {
      return res.status(400).json({ 
        success: false, 
        message: 'All fields required' 
      });
    }
    
    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return res.status(400).json({ 
        success: false, 
        message: 'Invalid email format' 
      });
    }
    
    // Setup email transport
    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: process.env.EMAIL_USER,
        pass: process.env.EMAIL_PASS
      }
    });
    
    // Send email
    await transporter.sendMail({
      from: email,
      to: 'sebbywakis@gmail.com',
      subject: `Contact Form: ${subject}`,
      html: `
        <p><strong>From:</strong> ${name} (${email})</p>
        <p><strong>Subject:</strong> ${subject}</p>
        <p><strong>Message:</strong></p>
        <p>${message}</p>
      `
    });
    
    res.json({ success: true, message: 'Email sent successfully' });
  } catch (error) {
    console.error(error);
    res.status(500).json({ 
      success: false, 
      message: 'Failed to send email' 
    });
  }
});

module.exports = app;
```

---

## 🚀 Quick Start: Deploy to Railway (Free & Easy)

### Step 1: Create Railway Account
```
1. Visit https://railway.app
2. Sign up with GitHub
3. Allow Railway to access your repositories
```

### Step 2: Create New Project
```
1. Click "New Project"
2. Select "Deploy from GitHub"
3. Choose your repository
4. Configure environment variables
```

### Step 3: Set Environment Variables
```
EMAIL_USER=sebbywakis@gmail.com
EMAIL_PASS=your-app-password
RECAPTCHA_SECRET=your-secret-key
```

### Step 4: Add package.json to Your Repo
```json
{
  "name": "sebby-wekesa-contact-api",
  "version": "1.0.0",
  "main": "api/contact.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "nodemailer": "^6.9.3",
    "dotenv": "^16.3.1"
  }
}
```

### Step 5: Create Server File
```javascript
// server.js
const express = require('express');
const app = express();
require('dotenv').config();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Your contact route here
app.post('/api/contact', require('./api/contact'));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
```

### Step 6: Deploy
```bash
git add .
git commit -m "Add backend API"
git push
# Railway auto-deploys!
```

---

## 📊 Comparison: Where to Host PHP

| Service | Cost | Ease | PHP | Database | Recommended |
|---------|------|------|-----|----------|-------------|
| **Hostinger** | $3-5 | Easy | ✅ | ✅ | ✅ Professional |
| **Bluehost** | $2-12 | Easy | ✅ | ✅ | ✅ Beginner |
| **Railway** | Free-$5 | Easy | ✅ | ✅ | ✅ Dev/Hobby |
| **Vercel** | Free-$20 | Medium | Via Node | ✅ | ✅ Serverless |
| **GitHub Pages** | Free | Easy | ❌ | ❌ | ❌ Static only |

---

## ✅ Recommended Solution for You

### Given Your Setup:

1. **Keep GitHub Pages** for frontend
   - Your `index.html`, CSS, JavaScript
   - Free hosting
   - Fast CDN

2. **Use Railway for Backend** (PHP/Node.js)
   - Connect GitHub for auto-deploy
   - Free tier available
   - Easy configuration
   - Supports PHP

3. **Update Form Target**
   ```html
   <form action="https://your-railway-app.railway.app/api/contact" method="POST">
   ```

4. **Result:**
   - Frontend: `github.io` (GitHub Pages)
   - Backend: `your-railway-app.railway.app` (Railway)
   - Email: Sends via backend service

---

## 🔐 Security Tips

1. **Never commit secrets to GitHub**
   ```bash
   # Use .env file
   EMAIL_PASS=your-password
   RECAPTCHA_SECRET=your-secret
   ```

2. **Use environment variables on remote service**
   - Railway: Project Settings > Variables
   - Vercel: Settings > Environment Variables

3. **Validate all input on backend**
   - Check email format
   - Check message length
   - Implement rate limiting

4. **Use CORS properly**
   ```javascript
   app.use(cors({
     origin: 'https://sebbywekesa.com',
     credentials: true
   }));
   ```

---

## 📞 Contact Form Flow (Final Architecture)

```
┌─────────────────┐
│  Your Website   │
│  (GitHub Pages) │
│  sebbywekesa.com │
└────────┬────────┘
         │
         │ POST form data
         │
         ▼
┌─────────────────────────────────┐
│  Backend API (Railway/Vercel)    │
│  /api/contact                    │
│  - Validate input                │
│  - Check reCAPTCHA               │
│  - Send email via SMTP           │
└────────┬────────────────────────┘
         │
         │ Send email
         │
         ▼
┌──────────────────────────┐
│  Email Service (Gmail)   │
│  sebbywakis@gmail.com    │
└──────────────────────────┘
```

---

## 🎓 Next Steps

1. **Decision:** Choose hosting solution (Railway recommended)
2. **Setup:** Create account and configure
3. **Code:** Convert PHP to Node.js OR upload PHP directly
4. **Test:** Send test email to verify
5. **Deploy:** Push changes to GitHub
6. **Update:** Change form action URL

---

## 💬 Questions?

Need help with:
- Setting up Railway? → Visit railway.app docs
- Converting PHP to Node.js? → See example above
- Email configuration? → Check Gmail App Passwords
- Domain configuration? → Contact your registrar

---

**Last Updated:** November 29, 2025
**Status:** Complete Guide Ready for Implementation
