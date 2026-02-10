# 🛠️ Mstore API - Comprehensive Setup & Deployment Guide

This document provides a complete walkthrough for setting up the **Mstore API** environment. Follow these steps meticulously to ensuring a stable and secure deployment.

---

## 📋 Table of Contents

1.  [System Requirements](#1-system-requirements)
2.  [Local Development Setup](#2-local-development-setup)
3.  [Configuration Guide](#3-configuration-guide)
4.  [Database Setup](#4-database-setup)
5.  [Running the Application](#5-running-the-application)
6.  [Production Deployment Checklist](#6-production-deployment-checklist)
7.  [Troubleshooting](#7-troubleshooting)

---

## 1. System Requirements

Before initializing the project, ensure your server or local machine meets the following criteria:

*   **PHP**: >= 8.1
*   **Extensions**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
*   **Composer**: >= 2.x
*   **Database**: MySQL 5.7+ or MariaDB 10.3+
*   **Web Server**: Nginx or Apache
*   **OS**: Linux (Ubuntu 20.04+ recommended) / Windows / macOS

---

## 2. Local Development Setup

### Step 1: Clone the Repository
```bash
git clone https://github.com/yashensha-dms/fastkart-api.git
cd fastkart-api
```

### Step 2: Install Dependencies
Install all PHP dependencies using Composer.
```bash
composer install
```
> **Note:** If you encounter issues, try running `composer update` or ensure necessary PHP extensions are enabled in your `php.ini`.

### Step 3: Environment Configuration
Duplicate the example environment file to create your local config.
```bash
cp .env.example .env
```
> **Important:** Never share your `.env` file or commit it to version control.

### Step 4: Generate Application Key
Generate a unique encryption key for your application.
```bash
php artisan key:generate
```

### Step 5: Storage Linking
Create a symbolic link to make your storage accessible publicly.
```bash
php artisan storage:link
```

---

## 3. Configuration Guide

Open the `.env` file and configure the following core settings:

### App Settings
```env
APP_NAME=Mstore
APP_ENV=local          # Change to 'production' on live server
APP_DEBUG=true         # Set to 'false' on live server
APP_URL=http://localhost:8000
```

### Database Connection
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mstore_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Mail Configuration (Example: SMTP)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@mstore.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 4. Database Setup

### Step 1: Create Database
Manually create an empty database named `mstore_db` (or whatever you defined in `.env`) in your MySQL server.

### Step 2: Run Migrations
This command creates all the necessary tables in your database.
```bash
php artisan migrate
```

### Step 3: Seed Default Data
Populate the database with essential initial data (roles, permissions, default settings).
```bash
php artisan db:seed
```
*Alternatively, for dummy content:*
```bash
php artisan mstore:import
```

---

## 5. Running the Application

Start the local development server:
```bash
php artisan serve
```

The API will be accessible at: `http://127.0.0.1:8000`

### Initial Verification
Visit `http://127.0.0.1:8000` in your browser. You should see a JSON response indicating the API is running successfully.

---

## 6. Production Deployment Checklist

When deploying to a live server (e.g., AWS, DigitalOcean, Shared Hosting), follow these additional steps:

1.  **Set Environment**: Update `.env` with `APP_ENV=production` and `APP_DEBUG=false`.
2.  **Optimize Caches**:
    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```
3.  **File Permissions**:
    *   `storage/` and `bootstrap/cache/` directories should be writable (chmod 775).
4.  **Queue Worker**: Setup Supervisor to keep `php artisan queue:work` running.
5.  **Scheduler**: Add the following cron entry to run scheduled tasks:
    ```bash
    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    ```
6.  **SSL Certificate**: Ensure your domain is secured with HTTPS.

---

## 7. Troubleshooting

**Issue: "500 Server Error"**
*   Check `storage/logs/laravel.log` for error details.
*   Ensure `.env` exists and has correct DB credentials.
*   Verify permissions on `storage` folders.

**Issue: "Images not loading"**
*   Run `php artisan storage:link`.
*   Check `APP_URL` in `.env` matches your actual domain.

---

**Built with ❤️ by DMSG Team.**
For support, please contact the development team.
