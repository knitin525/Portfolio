# Portfolio Contact Form Setup Guide

## Prerequisites

- Node.js installed (version 14 or higher)
- Hostinger email account (contact@knitin525.in)

## Step-by-Step Setup

### 1. Install Dependencies

Open your terminal in the project folder and run:

```bash
npm install
```

### 2. Configure Environment Variables

1. Copy the `.env.example` file to `.env`:

```bash
# Windows
copy .env.example .env

# Mac/Linux
cp .env.example .env
```

2. Edit the `.env` file and add your credentials:

```
PORT=3000

# SMTP Configuration (Hostinger)
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465

# Your Hostinger email and password
SMTP_USER=contact@knitin525.in
SMTP_PASS=your_actual_password_here

# Where to receive contact form emails
TO_EMAIL=contact@knitin525.in
```

**Important:** Replace `your_actual_password_here` with your actual email password or app password.

### 3. Get Your Email Password

If you're using Hostinger email:
- Log into hPanel (Hostinger control panel)
- Go to Email Accounts
- If you haven't set a password for contact@knitin525.in, create one
- Use that password in SMTP_PASS

### 4. Start the Server

```bash
npm start
```

The server will start at `http://localhost:3000`

### 5. Test the Contact Form

1. Open `http://localhost:3000` in your browser
2. Go to the Contact section
3. Fill in the form and click "Send Message"
4. Check your email at contact@knitin525.in

## Deploying to Hostinger

### Option 1: Use Node.js on Hostinger

1. Upload all files to your hosting account via File Manager or FTP
2. Open SSH Terminal in Hostinger hPanel
3. Run:

```bash
npm install
npm start
```

4. Your site will be available at your domain

### Option 2: Keep frontend static, use serverless (Alternative)

If you can't run Node.js on Hostinger, consider:
- Deploy frontend to Hostinger (public_html)
- Use a separate service for email (Formspree, Netlify Forms)
- Or deploy the Node.js backend to Render.com or Railway.app

## Troubleshooting

### Error: "Connection refused" or "Authentication failed"
- Check that SMTP_HOST and SMTP_PORT are correct
- Verify your email credentials are correct
- Make sure your Hostinger email password is correct

### Error: "Too many connections"
- Wait a moment and try again
- Check if your email account is properly configured

### Emails not received
- Check spam/junk folder
- Verify TO_EMAIL is correct
- Check Hostinger email inbox for any restrictions

### Common Issues

1. **Port already in use**: Change PORT in .env to 3001 or another number
2. **Module not found**: Make sure you ran `npm install`
3. **CORS error**: Server is configured to allow all origins for this portfolio

## Project Structure

```
knitin-v3/
├── index.html          # Main portfolio page
├── projects.html       # All projects page
├── server.js           # Express server with contact form API
├── package.json        # Node dependencies
├── .env.example        # Environment variables template
├── SETUP.md            # This file
├── css/
│   ├── styles.css      # Main styles
│   └── projects.css    # Projects page styles
├── js/
│   ├── main.js         # Main JavaScript
│   └── projects.js     # Projects page JS
└── img/                # Image assets
```

## Email Format

When someone submits the contact form, you'll receive:

**Subject:** New Portfolio Contact Message

**Body:**
```
Name: John Doe
Email: john@example.com

Message:
Hi, I'm interested in working with you on my new project...
```

## Security Notes

- Never commit `.env` file to version control
- The server.js file is configured to ignore .env in git
- Credentials are stored server-side only
- Frontend never sees email passwords