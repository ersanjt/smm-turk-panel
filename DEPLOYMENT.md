# 🚀 راهنمای راه‌اندازی SMM Turk Panel روی cPanel

---

## چک‌لیست: چه چیزهایی باید داشته باشی

| مورد | توضیح |
|------|--------|
| **هاست cPanel** | هاست با PHP 8+ و MySQL |
| **دامنه یا ساب‌دامین** | مثلاً `smm-turk.com` یا `panel.yoursite.com` |
| **API Key از SmmFollows** | از [smmfollows.com](https://smmfollows.com) → Account → API |
| **حساب SmmFollows** | با موجودی کافی برای سفارش‌ها |

---

## مرحله ۱: آپلود فایل‌ها

1. وارد **File Manager** در cPanel شو.
2. به پوشه `public_html` (یا `domains/yourdomain.com/public_html`) برو.
3. فایل‌ها را به این پوشه آپلود کن. می‌توانی:
   - از طریق File Manager فایل ZIP آپلود کنی و Extract بزنی، یا
   - با FTP (مثلاً FileZilla) وصل شوی و فایل‌ها را بکشی.

---

## مرحله ۲: ایجاد دیتابیس MySQL

1. در cPanel به **MySQL® Databases** برو.
2. یک **دیتابیس جدید** بساز (مثلاً `cpanel_user_smmturk`).
3. یک **کاربر MySQL** بساز و پسورد قوی بده.
4. کاربر را به دیتابیس **Add** کن و **All Privileges** بده.
5. نام دیتابیس، کاربر و پسورد را یادداشت کن.

---

## مرحله ۳: ایمپورت install.sql

1. **phpMyAdmin** را در cPanel باز کن.
2. دیتابیس ساخته‌شده را انتخاب کن.
3. تب **Import** → انتخاب فایل `install.sql` → **Go**.

---

## مرحله ۴: تنظیم config.php

1. فایل `config.example.php` را **کپی** کن و اسمش را به `config.php` بگذار.
2. `config.php` را ویرایش کن و مقدارها را پر کن:

```php
define('DB_HOST', 'localhost');              // معمولاً localhost
define('DB_NAME', 'نام_دیتابیس');            // مثلاً cpanel_user_smmturk
define('DB_USER', 'نام_کاربر');              // کاربر MySQL
define('DB_PASS', 'رمز_دیتابیس');            // پسورد MySQL
define('SITE_URL', 'https://yourdomain.com'); // بدون / در آخر
define('PROVIDER_API_KEY', 'API_KEY_SMMFOLLOWS');
```

---

## مرحله ۵: تنظیم .htaccess (Apache)

اگر از Apache استفاده می‌کنی، این قوانین را در `.htaccess` پوشه اصلی اضافه کن:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/v2$ /api/v2.php [L]
```

اگر فایل `.htaccess` نداشتی، یک فایل جدید با همین نام بساز.

---

## مرحله ۶: دسترسی‌ها (Permissions)

پوشه‌ها را معمولاً روی `755` و فایل‌ها روی `644` بگذار.  
فایل `config.php` را `640` کن تا فقط سرور آن را بخواند.

---

## مرحله ۷: ورود به ادمین

1. به آدرس `https://yourdomain.com/login.php` برو.
2. ورود ادمین:
   - **Email:** `admin@smm-turk.com`
   - **Password:** `password`
3. حتماً بلافاصله پسورد را عوض کن.

---

## مرحله ۸: همگام‌سازی سرویس‌ها

1. وارد پنل ادمین شو → **Admin** یا `/admin/`
2. **Sync Services** را بزن.
3. دکمه **Sync Now** را بزن تا سرویس‌های SmmFollows وارد شوند.

---

## Cron Job (اختیاری اما توصیه‌شده)

برای به‌روزرسانی خودکار وضعیت سفارش‌ها، یک Cron Job اضافه کن:

- **محدوده اجرا:** هر ۵ دقیقه
- **دستور:**

```bash
*/5 * * * * php /home/YOUR_CPANEL_USER/public_html/cron-sync.php
```

---

## دیپلوی با Git و rsync

اگر از گیت و rsync برای آپدیت استفاده می‌کنی:

- **`config.php`** همیشه از حذف مصون است (با `--exclude="config.php"`).
- بعد از هر rsync دسترسی فایل‌ها خودکار **755** (پوشه‌ها) و **644** (فایل‌ها) می‌شود تا خطای 403 برای `.htaccess` پیش نیاید.
- روی سرور فقط `~/deploy-smm.sh` را اجرا کن (بعد از `git pull` در رپو از لپ‌تاپ). push را فقط از سیستم خودت بزن.

---

## مشکل‌های رایج

| مشکل | راه‌حل |
|------|--------|
| صفحه سفید | `display_errors = 1` در config را موقتاً فعال کن و لاگ خطا را چک کن |
| خطای دیتابیس | نام دیتابیس، کاربر و رمز در cPanel را دقیق وارد کن |
| API کار نمی‌کند | API Key را از SmmFollows چک کن و موجودی حساب را ببین |
| 404 برای api/v2 | قوانین `.htaccess` را اضافه کن و mod_rewrite را فعال کن |

---

## امنیت در Production

- [ ] پسورد ادمین را عوض کن
- [ ] در `config.php` قرار بده: `error_reporting(0); ini_set('display_errors', 0);`
- [ ] حتماً از HTTPS استفاده کن
- [ ] دسترسی `config.php` را روی 640 بگذار
