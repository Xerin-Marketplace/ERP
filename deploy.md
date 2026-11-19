# XerinERP — Deployment Guide

> Production deployment runbook for **xerinhub.com**

| | |
|---|---|
| **Domain** | https://xerinhub.com / https://www.xerinhub.com |
| **Server** | Contabo VPS `vmi3739504` |
| **IPv4** | 161.97.160.177 |
| **IPv6** | 2a02:c207:2373:9504::1 |
| **OS** | Ubuntu 22.04 LTS (Plesk installed — bypassed) |
| **Stack** | nginx 1.18 · PHP 8.4-FPM · SQLite · Let's Encrypt SSL |
| **App** | Laravel 13 · Breeze (Blade) · Tailwind + Alpine |
| **Repo** | https://github.com/Xerin-Marketplace/ERP.git |
| **Doc root** | `/var/www/xerinerp/public` |

---

## 1. Server prerequisites

```bash
apt update && apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

# PHP 8.4 (Laravel 13 requires >= 8.3)
apt install -y php8.4-cli php8.4-fpm php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-sqlite3 php8.4-bcmath php8.4-intl php8.4-gd

# Web server + tools
apt install -y nginx unzip

# Node 22 (for Vite asset builds)
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
```

## 2. Clone & install

```bash
cd /var/www
git clone https://github.com/Xerin-Marketplace/ERP.git xerinerp
cd xerinerp

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --force

npm ci && npm run build

chown -R www-data:www-data storage bootstrap/cache database
php artisan optimize
```

### `.env` production values

```env
APP_NAME=XerinERP
APP_ENV=production
APP_DEBUG=false
APP_URL=https://xerinhub.com

SESSION_SECURE_COOKIE=true
DB_CONNECTION=sqlite
```

## 3. Nginx vhost

`/etc/nginx/sites-available/xerinerp`, symlinked into `sites-enabled/`
(`sites-enabled/default` removed):

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name xerinhub.com www.xerinhub.com;

    root /var/www/xerinerp/public;
    index index.php;

    client_max_body_size 50M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript
               text/xml application/xml image/svg+xml;
    gzip_min_length 256;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|webp|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> Certbot added the `443 ssl` block + HTTP→HTTPS redirect automatically.

## 4. Plesk conflicts — resolved ⚠️

This VPS runs Plesk, which fought nginx for ports. Fixes applied:

| Item | Action | Why |
|---|---|---|
| `apache2` service | `stop` + `mask` | Held ports 80/443 as Plesk backend |
| `/etc/nginx/conf.d/brotli.conf` | loaded modules via `/etc/nginx/modules-enabled/50-brotli.conf` | `brotli` directive needs `load_module` |
| `/etc/nginx/conf.d/ssl.conf` | renamed → `.off` | ssl_* duplicated by `nginx.conf` |
| `/etc/nginx/conf.d/fixssl.conf` | renamed → `.off` | same duplicate |
| `/etc/nginx/plesk.conf.d/*.conf` | all renamed → `.off` | proxied everything to dead Apache `127.0.0.1:7080` |
| `/etc/nginx/plesk.conf.d/server.conf` | emptied to a comment | `zz010_psa_nginx.conf` includes it by name — file must exist |

**Restore Plesk hosting (if ever needed):**

```bash
systemctl unmask apache2
# restore the .off files to .conf, then:
systemctl restart apache2 nginx
```

## 5. SSL — Let's Encrypt

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d xerinhub.com -d www.xerinhub.com --redirect
```

- Certs: `/etc/letsencrypt/live/xerinhub.com/`
- Auto-renewal: `certbot.timer` (current cert expires **2027-02-17**)
- Test: `certbot renew --dry-run`

## 6. Deploy update workflow

```bash
cd /var/www/xerinerp
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
systemctl reload php8.4-fpm
```

## 7. Health checks

```bash
curl -sI https://xerinhub.com -o /dev/null -w "%{http_code}\n"   # expect 302
systemctl status nginx php8.4-fpm --no-pager | grep Active
nginx -t
```

## 8. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `502 Bad Gateway` | Apache/Plesk revived, or PHP-FPM down | `systemctl status apache2 php8.4-fpm` |
| `nginx -t` fails | Plesk restored `.conf` files | re-rename offenders to `.off` |
| 403 / write errors | wrong ownership | `chown -R www-data:www-data storage bootstrap/cache database` |
| Vite assets missing | build not run | `npm ci && npm run build` |

**Logs:**

```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
tail -f /var/www/xerinerp/storage/logs/laravel.log
journalctl -xeu php8.4-fpm
```

---

*Last deployed: 2026-11-19 — Laravel 13.43 · PHP 8.4.27*
