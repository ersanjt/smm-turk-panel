# SMM Turk Panel — Setup Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- cURL extension enabled
- Apache / Nginx web server

---

## Installation Steps

### 1. Upload Files
Upload all files to your web server's public directory (e.g., `/var/www/html/` or `public_html/`).

### 2. Create Database
1. Go to **phpMyAdmin** or run MySQL CLI
2. Create a new database named `smm_turk`
3. Import the file `install.sql`:
   ```sql
   mysql -u root -p smm_turk < install.sql
   ```

### 3. Configure the App
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smm_turk');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
define('SITE_URL', 'https://smm-turk.com');
define('PROVIDER_API_KEY', 'YOUR_SMMFOLLOWS_API_KEY');
```

### 4. Get Your SmmFollows API Key
1. Login to https://smmfollows.com
2. Go to **Account → API**
3. Copy your API key
4. Paste it in `config.php` as `PROVIDER_API_KEY`

### 5. Admin Login
Default admin credentials:
- **Email:** admin@smm-turk.com  
- **Password:** password  
⚠️ Change the password immediately after login!

### 6. Sync Services
1. Login as admin
2. Go to **Admin → Sync Services**
3. Click **Sync Now**
4. All services from SmmFollows will be imported

### 7. Apache Configuration (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/v2$ /api/v2.php [L]
```

### 8. Nginx Configuration
```nginx
location /api/v2 {
    try_files $uri /api/v2.php$is_args$args;
}
```

---

## File Structure
```
smm-turk/
├── config.php              # Main configuration
├── install.sql             # Database schema
├── index.php               # New Order page
├── orders.php              # Order history
├── services.php            # Services list
├── add-funds.php           # Add funds
├── mass-order.php          # Mass ordering
├── tickets.php             # Support tickets
├── affiliates.php          # Referral system
├── api-page.php            # API documentation
├── login.php               # Login/Register
├── logout.php              # Logout
├── includes/
│   ├── init.php            # Bootstrap loader
│   ├── Database.php        # Database class (PDO)
│   ├── Auth.php            # Authentication
│   ├── SmmApi.php          # SmmFollows API wrapper
│   ├── OrderManager.php    # Order management
│   ├── header.php          # Layout header
│   └── footer.php          # Layout footer
├── admin/
│   ├── index.php           # Admin dashboard
│   ├── admin-users.php     # Manage users + add balance
│   ├── admin-orders.php    # Manage orders
│   ├── admin-services.php  # Manage services
│   ├── admin-sync.php      # Sync services from API
│   ├── admin-tickets.php   # Manage tickets
│   └── admin-settings.php  # Site settings
└── api/
    └── v2.php              # Public API endpoint
```

---

## API Usage (for resellers)
Your customers can integrate via API:

**Endpoint:** `POST https://smm-turk.com/api/v2`

```php
$api_key = 'USER_API_KEY';

// Get services
$ch = curl_init('https://smm-turk.com/api/v2');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "key=$api_key&action=services");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = json_decode(curl_exec($ch));
```

---

## Cron Jobs (recommended)
Add to crontab to auto-sync order statuses:
```bash
# Sync order statuses every 5 minutes
*/5 * * * * php /var/www/html/cron-sync.php
```

---

## Security Notes
- Change admin password immediately
- Set `error_reporting(0)` in config.php for production
- Use HTTPS (SSL certificate required)
- Keep `config.php` permissions to 640

---

## GitHub Setup

### First-time setup

1. Copy config template and never commit real credentials:
   ```bash
   cp config.example.php config.php
   # Edit config.php with your values (config.php is in .gitignore)
   ```

2. Create a new repository on GitHub (do not initialize with README).

3. Initialize and push from your project folder:
   ```bash
   git init
   git add .
   git commit -m "Initial commit: SMM Turk Panel"
   git branch -M main
   git remote add origin https://github.com/YOUR_USERNAME/smm-turk-panel.git
   git push -u origin main
   ```
