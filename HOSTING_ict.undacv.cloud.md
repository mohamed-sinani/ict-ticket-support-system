# Hosting Guide for `ict.undacv.cloud`

This guide shows how to host the ICT Support Ticket System on the subdomain `ict.undacv.cloud`.

## 1. Prepare the server

- Install a web server with PHP and MySQL/MariaDB.
- Make sure PHP extensions for `mysqli`, `mbstring`, `gd`, and `fileinfo` are enabled.
- Create a document root for the subdomain, for example:
  - `/var/www/ict.undacv.cloud/public_html`

Commands you may use:

```bash
sudo mkdir -p /var/www/ict.undacv.cloud/public_html
sudo chown -R $USER:www-data /var/www/ict.undacv.cloud
sudo chmod -R 775 /var/www/ict.undacv.cloud
```

## 2. Point the subdomain to the server

- Create a DNS `A` record for `ict.undacv.cloud` that points to your server IP.
- If you use Cloudflare or another DNS provider, wait for the record to propagate.

## 3. Configure the web server

### Apache example

Create a virtual host for the subdomain:

```apache
<VirtualHost *:80>
    ServerName ict.undacv.cloud
    DocumentRoot /var/www/ict.undacv.cloud/public_html

    <Directory /var/www/ict.undacv.cloud/public_html>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable the site and reload Apache:

```bash
sudo a2ensite ict.undacv.cloud.conf
sudo systemctl reload apache2
```

### Nginx example

```nginx
server {
    server_name ict.undacv.cloud;
    root /var/www/ict.undacv.cloud/public_html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Reload Nginx after saving the file:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 4. Upload the application files

- Copy the entire project into the subdomain document root.
- Keep the folder structure intact so paths like `includes/`, `config/`, `assets/`, and `database/` still work.
- Ensure `uploads/` is writable by the web server.

Commands you may use:

```bash
rsync -av --delete ./ /var/www/ict.undacv.cloud/public_html/
sudo chmod -R 775 /var/www/ict.undacv.cloud/public_html/uploads
```

## 5. Import the database

- Create a MySQL database for the app.
- Import `database/schema.sql` into that database.
- If you already have data, back it up before importing.

Example commands:

```bash
mysql -u root -p -e "CREATE DATABASE ict_support_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ict_support_system < database/schema.sql
```

## 6. Update app configuration

Open `config/app.php` and change `BASE_URL` to match where the app is hosted.

If the app is hosted directly at the subdomain root, use:

```php
const BASE_URL = '/';
```

If it is hosted inside a subfolder, set it to that folder path instead.

Then update `config/db.php` with the correct database host, username, password, database name, and port for production.

If you want to automate the configuration edits on the server, use your editor or a scripted replacement, for example:

```bash
nano config/app.php
nano config/db.php
```

## 7. Check file permissions

- Make `uploads/` writable by the web server.
- If your server requires it, also allow the web server to read the project files.

Example:

```bash
chmod -R 775 uploads
```

## 8. Configure HTTPS

- Install an SSL certificate for `ict.undacv.cloud`.
- Use Let's Encrypt if your hosting provider supports it.
- Redirect HTTP to HTTPS after the certificate is active.

Example Let's Encrypt command:

```bash
sudo certbot --apache -d ict.undacv.cloud
```

Or with Nginx:

```bash
sudo certbot --nginx -d ict.undacv.cloud
```

## 9. Test the deployment

Open these pages after deployment:

- `https://ict.undacv.cloud/`
- `https://ict.undacv.cloud/login.php`
- `https://ict.undacv.cloud/register.php`
- `https://ict.undacv.cloud/report.php`
- `https://ict.undacv.cloud/track.php`

Also verify:

- Login works for admin and ICT staff.
- Ticket submission works.
- File uploads save correctly.
- Mobile menu and role dashboards load without layout issues.

Quick test commands:

```bash
curl -I https://ict.undacv.cloud/
curl -I https://ict.undacv.cloud/login.php
curl -I https://ict.undacv.cloud/report.php
```

## 10. Email and notifications

- If you want email notifications, configure the SMTP settings in `config/app.php` for your production mail server.
- Confirm the server allows outbound SMTP traffic.

After editing SMTP values, restart PHP-FPM or Apache if needed:

```bash
sudo systemctl restart php8.1-fpm
sudo systemctl reload apache2
```

## 11. Common issues

- Blank page: check PHP error logs and make sure the database is reachable.
- 404 errors: confirm the document root and `BASE_URL` are correct.
- Upload failures: check permissions on `uploads/`.
- Broken assets: confirm the project was uploaded with the same folder structure.

## Recommended deployment checklist

- DNS points to the correct server.
- Apache or Nginx virtual host is created.
- Database is imported.
- `config/app.php` has the right `BASE_URL`.
- `config/db.php` has production credentials.
- `uploads/` is writable.
- HTTPS is active.
