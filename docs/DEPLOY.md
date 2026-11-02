# Deploying Yaqot to InfinityFree

Step by step, from an empty InfinityFree account to a working site, and how to update it later.
Everything here was checked on a local copy of the package (see "What was verified"); the parts that
could only be read from InfinityFree's own pages or forum are marked **(not tested on the host)**.

## What you need before you start

| Item | Notes |
|---|---|
| The real domain, or the free subdomain you will use | `APP_URL` must be exactly the address visitors use. |
| An InfinityFree account | Free. Create it at infinityfree.com, then create a hosting account in the client area. |
| FileZilla (or another FTP client) | The package is ~7,250 files: FTP is the practical way. |
| This project on your computer | PHP 8.2+, Composer, MySQL (XAMPP is fine). |
| The first admin's email and password | You create it locally, see step 2. |

## The host's limits (and what the project does about each)

| Limit (InfinityFree) | Source | What the project does |
|---|---|---|
| No SSH, no Composer on the server | official docs and the forum's Laravel guide | `vendor/` is built on your computer and uploaded; the database is imported as SQL; later migrations are applied from the dashboard. |
| About 30,000 files and folders | forum (several staff answers) | The package is **8,527** entries (7,255 files + 1,272 folders). `public/uploads` grows by one folder per product plus its images, so about 18,000 entries remain: roughly 1,500 products at 12 images, several thousand at 3–4. |
| PHP, HTML and JS files over **1 MB** are deleted silently; `.htaccess` over **10 kB**; other files over **10 MB** | [InfinityFree: File size and upload limits](https://forum.infinityfree.com/t/file-size-and-upload-limits/49308) | The builder fails if any file breaks these. The largest PHP file is 436 KB; the non-optimized autoloader is used (the optimized one has files near 1 MB). |
| PHP upload limit **20 MB** for files sent through your site (older posts say 10 MB), and it cannot be raised | same page; older thread: [php.ini limit](https://forum.infinityfree.net/t/file-upload-size-limit-in-wordpress-and-php-ini-limit/10730) | The admin reads the real `post_max_size` / `upload_max_filesize` and shows them (dashboard, under the gallery); `admin.js` refuses a save that cannot fit, before sending it. Add a few photos per save. |
| Document root is `htdocs` and cannot be changed | [InfinityFree Laravel guide](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578) | The whole application sits in `htdocs`, and a root `.htaccess` answers every request from `public/` only (three layers, see "Security of the layout"). |
| No symlinks (`storage:link`) | same guide | Uploads are written into the real folder `public/uploads` (`PUBLIC_DISK_ROOT=uploads`). |
| PHP version cannot be chosen on free hosting; staff say it is currently **PHP 8.4** | [forum](https://forum.infinityfree.com/t/please-confirm-php-8-3-support-for-my-free-hosting-account/121288) | The project needs 8.2+. It was tested on 8.2 only, so check the log after the first visits (step 8). |
| Cron | InfinityFree announced cron jobs for everyone in 2022 ([post](https://forum.infinityfree.com/t/new-feature-cron-jobs-from-infinityfree/66630)); not tested here | The project does **not** rely on it. Old statistics are deleted with a dashboard button (see "Routine tasks"). |
| Mail unreliable | brief | Nothing sends mail: the admin has no password reset. |

## 1. Create the account and the site

1. Sign up at infinityfree.com and create a hosting account. Choose your domain, or a free subdomain
   to try the site first.
2. For your own domain, point its nameservers (or the records InfinityFree shows) to InfinityFree and
   wait until the control panel shows the domain as active.
3. In the client area, open the account's **FTP details**: you need the FTP host, the user name and the
   password. Delete the placeholder files InfinityFree puts in `htdocs` (for example `index2.html`).

## 2. Prepare on your computer

Everything in this step runs on your computer, never on the host.

1. **The production secret.** Generate the application key once and keep it (changing it later logs
   every admin out and breaks stored encrypted values):

   ```
   php artisan key:generate --show
   ```

2. **The clean database.** Use `yaqot_deploy`, never `yaqot_jewelry` (that one holds demo data):

   ```
   DB_DATABASE=yaqot_deploy php artisan migrate --force
   DB_DATABASE=yaqot_deploy php artisan yaqot:create-admin
   ```

   The migrations also create the base gold colours and stone types. There is no demo data, and no
   seeder is run.

3. **Export it:**

   ```
   php tools/deploy/export-db.php --database=yaqot_deploy --out=D:\yaqot-release\yaqot_deploy.sql
   ```

   The script refuses to export the development database, and tells you if the file has no admin account.

4. **Build the package** (nothing is uploaded; the output folder must be outside the project):

   ```
   php tools/deploy/build.php --out=D:\yaqot-release
   ```

   It produces `htdocs/` (the tree to upload), `zips/` (the same tree in two zips under 10 MB),
   `MANIFEST.txt` (every file and its size) and `REPORT.txt` (the numbers). It stops with an error if a
   file is over the host's limits or a forbidden file (`.env`, tests, docs, `.git`, `.claude`, demo seeders,
   design sources) is in the package. The last build: 7,255 files, 1,272 folders, 30.4 MB, no PHP file over 1 MB,
   routes cached for both languages, non-optimized autoloader.

## 3. Create the database on the host and import

1. In the client area open **MySQL Databases**, create a database, and note the **host name** (it looks like
   `sqlNNN.infinityfree.com`, never `localhost`), the full **database name** (`if0_…_name`) and the **user name**.
   The password is your account password.
2. Open **phpMyAdmin** for that database, choose **Import**, pick `yaqot_deploy.sql`, and run it.
   The file has no `CREATE DATABASE` line, so it imports into whatever database you opened. It can be imported
   again at any time (every table is dropped and recreated), which wipes the data.
3. Check that you see 28 tables, `migrations` with 27 rows, `product_options` with 9 rows and `admins` with your admin.

## 4. Upload the files

Use FTP and upload the **contents** of `D:\yaqot-release\htdocs` into the host's `htdocs` folder, so that
`htdocs/app`, `htdocs/vendor`, `htdocs/public` and `htdocs/.htaccess` exist (not `htdocs/htdocs`).

Upload in this order, so a half-uploaded site never runs with half its code:

1. `vendor/` (the biggest part; FTP clients may stop on errors: let them retry).
2. `app/`, `bootstrap/`, `config/`, `database/`, `lang/`, `Modules/`, `resources/`, `routes/`, `storage/`, `public/`.
3. The loose files: `composer.json`, `composer.lock`, `modules_statuses.json` (**required**: without it no
   module is enabled and every page is a 404) and `.env.production.example`.
4. Last, the root **`.htaccess`**.

Hidden files (`.htaccess`) must be shown in the FTP client's settings, or they are skipped. Afterwards compare
the number of files on the host with `MANIFEST.txt` (a missing PHP file is almost always one the host deleted
for being over 1 MB, or an interrupted transfer).

The zips in `zips/` hold the same tree. They are for the control panel's file manager **(not tested on the host:
upload size limits and whether it can extract zips depend on the account)**; they are numbered, and the last one
contains the root `.htaccess`.

## 5. Create `.env` on the server

1. Copy `.env.production.example` to `.env` (on your computer, then upload; or rename on the host).
2. Fill in:
   - `APP_KEY` with the value from step 2;
   - `APP_URL` with the real address: `https://your-domain` (no trailing slash). The site answers to that host and
     its subdomains (`www.` works when `APP_URL` is `example.com`, **not** the other way round); any other host gets a
     400 error. Use `http://` only until SSL is active, then change it. **While the site is on `http://`, also set
     `SESSION_SECURE_COOKIE=false`**: browsers refuse to store a "Secure" cookie sent over http, so nobody could log
     in (a 419 "page expired"). Set it back to `true` the moment https works (step 9);
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from step 3.
3. Leave the rest as it is: `APP_DEBUG=false`, sessions and cache in the database, secure cookies,
   `PUBLIC_DISK_ROOT=uploads`. Never put the `.env` file anywhere else or send it by email.

## 6. Check that the layout is safe

Open these addresses in a browser (replace the domain). **Every one must show an error page, never file
contents or a blank page:**

```
/.env     /.env.production.example     /composer.json     /modules_statuses.json
/vendor/autoload.php     /app/Models/User.php     /config/app.php     /storage/logs/
/bootstrap/cache/packages.php     /public/.htaccess     /database/migrations/
```

These must work: `/en`, `/ar`, `/en/catalog`, `/robots.txt`, `/sitemap.xml`, `/en/admin/login`.
If any protected address shows content, stop and delete `.env` from the server until it is fixed.

## 7. First login

1. Open `/ar/admin/login` (or `/en/admin/login`) and sign in with the admin you created in step 2.
2. Open **My account** and change the password if you used a temporary one (10+ characters, upper and lower
   case, a digit and a symbol). Add the other admins from **Admin users**.
3. Fill in **Settings** (WhatsApp number, phone, email, social links, about text, footer text, the meta description
   shown in search results, hero slides). Add branches, brands, categories, collections and products.
4. Open the dashboard: the box "Upload limits of this server" shows the real numbers for this host.

## 8. After the first visits

- Look at `storage/logs/laravel-YYYY-MM-DD.log` on the server: it should be empty or hold only warnings. A line
  about a deprecated feature means PHP 8.4 found something the local PHP 8.2 did not; send it to the developer.
- The first request to each page compiles its template into `storage/framework/views`; the first visit is slower.
- Check the home page, the catalog, a product, the bullion page and the contact page in both languages.

## 9. SSL (https)

1. In the client area open **SSL Certificates** and request a free certificate for the domain. InfinityFree's
   certificates are issued by a partner and can take from minutes to hours; you install the certificate yourself in
   the same screen **(not tested on the host)**. A free subdomain may not be offered a certificate: use your own domain.
2. When `https://your-domain` opens without a warning, set `APP_URL=https://your-domain` in `.env`. From then on
   every link, the sitemap and the share images use https, and the site sends the HSTS header (6 months, no
   `includeSubDomains`). Test before changing: HSTS makes browsers refuse plain http afterwards.
3. Optionally redirect http to https in the root `.htaccess` (add above the other rules):
   `RewriteCond %{HTTPS} off` / `RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`.
   Keep that file under 10 kB.

## 10. Google Analytics (optional)

1. In Google Analytics create a property and a web data stream; copy the Measurement ID (`G-XXXXXXXXXX`).
2. In the admin, **Settings → Google Analytics**, paste it and save. A cookie notice appears on the public site;
   Google is loaded only after a visitor accepts. The security headers allow Google's hosts only while an ID is set.
3. Confirm in Google Analytics → Realtime after accepting the notice yourself. Remove the ID to turn everything off.
4. Have the privacy page reviewed: its Google section appears only while an ID is set (docs/COPY_REVIEW.md).

## 11. Search engines

- `/robots.txt` and `/sitemap.xml` are produced by the site. In production the robots file allows crawling and names the
  sitemap; on any other `APP_ENV` it blocks everything (so a test copy is never indexed).
- Add the site in Google Search Console and submit `https://your-domain/sitemap.xml`. Free hosts sometimes show
  automated visitors a JavaScript check page: if Search Console cannot read the sitemap, that is the cause.

## Routine tasks

| Task | How | How often |
|---|---|---|
| Delete old statistics | Dashboard → product insights → **Delete old statistics** (keeps 13 months). No cron is needed or used. | Monthly |
| Backup the database | phpMyAdmin → Export → Quick, SQL. Save it on your computer. | Weekly, and before every update |
| Backup the images | Download `public/uploads` by FTP. | After adding many products |
| Keep the secrets | Keep a copy of `.env` (especially `APP_KEY`) somewhere private, offline. | Once |

**Maintenance mode** (no shell for `artisan down`): upload a file `storage/framework/down` containing
`{"retry":60,"refresh":null,"secret":"pick-a-long-random-word","status":503,"template":null,"redirect":null}`;
visitors see the 503 page. Open `https://your-domain/pick-a-long-random-word` once in your browser: it sets a cookie
that lets **you** (and only you) use the site and the admin while everyone else still sees the 503 page. Delete the file
to reopen the site. Note that maintenance mode is read by the application itself: it only works while the code is
complete, so it does **not** cover the moments when `vendor/` or `app/` are being replaced.

## Updating later

Always take a database backup first (phpMyAdmin → Export).

### A code-only update (no new database migration)

1. On your computer: pull the new code, run the tests, rebuild: `php tools/deploy/build.php --out=D:\yaqot-release`
   (the build stops on any problem).
2. Choose a quiet hour: for a few minutes visitors can get an error page while code is being replaced (maintenance
   mode cannot help then, see above). Put the site in maintenance mode anyway, with your secret word, so that you can check
   the result before visitors see it.
3. On the host delete these folders, then upload the same folders from the new `htdocs`: `app`, `bootstrap`, `config`,
   `database`, `lang`, `Modules`, `resources`, `routes`, `vendor`, `public/assets`, and **overwrite** (do not delete first)
   the files `public/index.php`, `public/.htaccess`, `composer.json`, `composer.lock`, `modules_statuses.json` and the root
   `.htaccess` (deleting the root `.htaccess`, even briefly, leaves `.env` readable from the web).
   Deleting the folders first removes files that no longer exist (an old route cache or class would otherwise stay).
   **Never touch** `.env`, `public/uploads/` and `storage/`.
4. Make sure `bootstrap/cache/routes-v7.php`, `routes-v7_ar.php` and `routes-v7_en.php` came from the same build as the
   code (they list the site's URLs).
5. Remove the maintenance file and check the pages.

### An update with migrations (the new code changes the database)

Do steps 1–4 above, keep the site in maintenance, then:

1. Open your secret maintenance address once, sign in to the admin and open the dashboard (visitors still see the 503
   page, so nobody uses the new code on the old database).
2. A box **Database updates** names how many updates are waiting. Press **Apply updates** once. It runs the
   same migrations as `php artisan migrate --force`, in order, says how many it applied, and ignores a second press
   while the first is running. Then delete the maintenance file to reopen the site.
3. If it shows an error, do not press again: restore the database backup, upload the previous release (see rollback) and
   send the log (`storage/logs/`) to the developer. MySQL does not undo a half-applied table change by itself.

### Rollback

1. Keep the previous `D:\yaqot-release` (rename the folder, e.g. `yaqot-release-2026-11-02`) before each build.
2. Upload the previous release's folders as in a code-only update.
3. If the failed release applied migrations, import the database backup you took before updating (this removes the
   data entered since). A migration cannot be rolled back without a shell.

## Troubleshooting

| What you see | Likely cause |
|---|---|
| "400 Bad Request" on every page | `APP_URL` is not the address in the browser (`APP_URL=www.example.com` but visiting `example.com`, or another domain). |
| Login says "page expired" (419) over and over | `SESSION_SECURE_COOKIE=true` while the site is still on `http://`: set it to `false` until https works. |
| Every page is a 404 (even `/en`) | `modules_statuses.json` is missing, or the files are in the wrong folder (`htdocs/htdocs`). |
| A white page or the 500 page | Read `storage/logs/laravel-….log`. Usual causes: `.env` missing or wrong database details; a PHP file the host deleted (compare with `MANIFEST.txt`); a stale route cache from another build. |
| Every page is slow the first time | Normal: templates are compiled on first use. |
| Images do not show | `PUBLIC_DISK_ROOT=uploads` missing in `.env`, or the images were not uploaded to `public/uploads`. |
| Saving with photos fails | The photos exceed the host's per-request limit: add fewer per save (the admin tells you the limit). |
| Locked out of the admin | Another admin can set a new password (Admin users). If none can, ask the developer to run `yaqot:create-admin` against a copy of the database and import it. |

## Security of the layout

All the application's files sit in `htdocs`, so the root `.htaccess` makes sure only `public/` can be requested. Three
independent layers protect the rest, and each was tested with decoy secrets (`.env`, a log file) on a local Apache that
used the extracted zips as its web root:

1. A `FilesMatch` rule refuses `.env*`, `composer.*`, `modules_statuses.json` and similar names without any module.
2. A rewrite rule answers every request from `public/`, so `/vendor/…`, `/app/…` and `/storage/logs/…` become files
   that do not exist (404) and the real files are never opened.
3. A `.htaccess` with "deny all" in `app`, `bootstrap`, `config`, `database`, `lang`, `Modules`, `resources`, `routes`,
   `storage` and `vendor`. (Uploads are served as `/uploads`, not `/storage`, so this layer can stay on `storage`.)

With the rewrite rule deleted, layers 1 and 3 alone still returned 403 for all of those addresses. Uploads cannot run
scripts (`public/uploads/.htaccess`, and the front `.htaccess` refuses any PHP file except `index.php`). If the host
ever stopped honouring `.htaccess` files, none of this would hold: that is why step 6 exists.

## What was verified, and what was not

| Checked | How |
|---|---|
| Package builds; 8,527 entries; no PHP file over 1 MB; no `.htaccess` over 10 kB; no forbidden file | `tools/deploy/build.php` (it fails otherwise) |
| The zips contain exactly the tree (7,255 files) | extracted and compared |
| The packaged site runs from an `htdocs`-style folder with cached routes: pages, sitemap, robots, 404, maintenance page | local Apache 2.4 on another port |
| Protected files are not reachable, with and without the rewrite layer | decoy `.env` and log, 25 addresses |
| Uploads through the admin land in `public/uploads` and are served as `/uploads/…` with a one-year cache | puppeteer + Apache |
| The SQL import is repeatable and leaves nothing pending | re-imported into `yaqot_deploy` |
| **Not tested on the host:** PHP 8.4 behaviour, the real `post_max_size`, SSL issuing, the file manager's zip handling, whether the host's `.htaccess` honours `mod_deflate`/`mod_expires`/`mod_headers` (every block is optional: without them the site works, only slower), the anti-bot page | needs the real account |
