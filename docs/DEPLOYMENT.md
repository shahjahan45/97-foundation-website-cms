# Deployment Notes

## XAMPP / Apache

The bundled `.htaccess` routes application pages to `index.php`, leaves intended public assets accessible and blocks direct web access to application/database/private directories.

For XAMPP ensure Apache `mod_rewrite` is enabled and the project directory allows `.htaccess` overrides. The code automatically detects a localhost sub-folder such as `/97-foundation`.

## Production Apache / Nginx

For production, prefer a dedicated virtual host. Enforce HTTPS and deny access to source/config/private paths at the server configuration level as an additional layer even though Apache deny rules are bundled.

For Nginx, route application requests to the front controller and explicitly deny `app`, `database`, `storage`, `tests`, `docs`, configuration and dotfiles.

## Recommended environment values

```text
APP_NAME="97 Foundation"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Colombo
SESSION_NAME=foundation97_session
SESSION_LIFETIME=7200
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=foundation97
DB_USERNAME=foundation97_app
DB_PASSWORD=REPLACE_WITH_RANDOM_SECRET
```

## Upgrade from previous package

Back up database + uploaded/private files. Replace code, preserve `public/uploads` and `storage/private`, then run:

```bash
php database/upgrade_v2.php
```

The helper is designed to add missing V2 columns/tables without dropping existing operational records.

## Filesystem permissions

Grant the web-server account write access only where the application needs it, especially `public/uploads`, `storage/logs`, `storage/cache` and `storage/private/donation-proofs`. Avoid world-writable `777` permissions.

## Production operational requirements

- HTTPS/TLS
- Daily backups
- Error logging and monitoring
- Security patching
- Dedicated least-privilege database credentials
- Review of CSP/reverse-proxy headers
- Upload malware scanning when available
- Periodic security tests
