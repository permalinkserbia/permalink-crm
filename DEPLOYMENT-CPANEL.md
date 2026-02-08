# Deploying Laravel to cPanel

Laravel returns **404 on cPanel** when the web server’s document root is not the `public` folder. Fix it using one of the approaches below.

---

## Option 1: Set document root to `public` (recommended)

1. In **cPanel** go to **Domains** → **Domains** (or **Addon Domains** / **Subdomains** if applicable).
2. Click **Manage** for the domain you use for this app.
3. Set **Document Root** to the `public` folder of your Laravel app, for example:
   - `public_html/permalink-crm/public`  
   or  
   - `permalink-crm/public`  
   (exact path depends on where you uploaded the project.)
4. Save. The site should load without 404.

---

## Option 2: Keep document root as project root

If you cannot change the document root (e.g. it must stay as `public_html` or the project root), use the **root `.htaccess`** included in this project.

1. Upload the **entire project** so that the Laravel root (where `artisan` and `public/` live) **is** the document root.  
   Example: contents of `permalink-crm/` are in `public_html/` (so `public_html/public`, `public_html/app`, etc. exist).
2. Ensure the **root `.htaccess`** (the one next to `artisan`) is present in that document root. It:
   - Serves real files/directories from `public/` (CSS, JS, images).
   - Sends all other requests to `public/index.php` so Laravel handles routes correctly.
3. Restart or reload the site; 404 should stop.

---

## Checklist on the server

- **PHP**: 8.1+ (or whatever your `composer.json` requires).
- **`.env`**: Created and filled (e.g. `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, DB credentials).
- **Composer**: Run `composer install --no-dev` in the project root on the server (or deploy with `vendor/` and no-dev already installed).
- **Storage/cache**:  
  `php artisan storage:link`  
  `chmod -R 775 storage bootstrap/cache`  
  (and ensure the web server user can write to them.)
- **`AllowOverride`**: So `.htaccess` is respected; on cPanel this is usually already allowed for `public_html`.

If you still get 404, confirm the document root and that the root `.htaccess` is in place and that `public/index.php` and `public/.htaccess` exist and are readable.

---

## "Dump in index.php works but app stops during lifecycle"

If a dump in `public/index.php` appears but the app never renders the page, something is failing later (autoload, bootstrap, or handling the request).

1. **See the real error**: Visit the site with **`?debug=1`** in the URL (e.g. `https://yoursite.com/?debug=1`). The modified `index.php` will show PHP errors and any exception (message, file, line, stack trace) so you can fix the cause.
2. **Typical causes on cPanel**:
   - **Missing `vendor/`**: Run `composer install --no-dev` on the server (or upload a copy).
   - **Wrong or missing `.env`**: Ensure `.env` exists in the project root (same level as `artisan`); set at least `APP_KEY`, `APP_ENV`, `APP_DEBUG`, and DB credentials.
   - **Paths**: If you moved only `public/` into the web root, the paths in `index.php` (`__DIR__.'/../vendor/autoload.php'` etc.) must still point to the real app root (e.g. `__DIR__.'/../laravel/vendor/autoload.php'` if the app lives in a sibling `laravel/` folder).
   - **Permissions**: `storage` and `bootstrap/cache` must be writable by the web server (`chmod -R 775 storage bootstrap/cache`).
   - **PHP version/extensions**: Laravel requires certain extensions (e.g. mbstring, openssl, PDO). In cPanel → **Select PHP Version** / **PHP Extensions**, enable the required ones.

After fixing, remove the `?debug=1` logic from `public/index.php` for production, or leave it and rely on not passing `debug=1` so errors are not shown to users.
