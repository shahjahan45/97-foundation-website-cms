# 97 Foundation — Localhost Routing / CSS Fix

## Issue fixed

An older build used `http://localhost/97-foundation-php` as a hard-coded default application URL. If the project folder was renamed to `97-foundation`, Apache still loaded `index.php`, but:

- the router received `/97-foundation/` and returned **Page not found**;
- CSS/JS URLs still pointed to `/97-foundation-php/public/assets/...`, so the page appeared unstyled.

## What changed

- Application base path is now detected automatically from Apache/PHP `SCRIPT_NAME`.
- Router safely removes the detected sub-folder before route matching.
- CSS, JS, navigation links, redirects and form actions use the detected base path.
- `.htaccess` has `DirectoryIndex index.php` and disables `MultiViews` for predictable routing.
- The project can now be placed in `C:\xampp\htdocs\97-foundation` without editing PHP files.

## After replacing the files

Open:

- Website: `http://localhost/97-foundation/`
- Admin: `http://localhost/97-foundation/admin/login`

Then press **Ctrl+F5** once to clear the old cached page/assets.
