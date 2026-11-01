# Yaqot Jewelry Catalog — Project Brief

This is the master project prompt, rewritten to include every decision made
from Phase 0 through Phase 1c. A new session should be able to work from this
file and `docs/CONVENTIONS.md` alone.

**Precedence:** `docs/CONVENTIONS.md` holds the detailed coding rules. Where this
brief summarises a topic, it points to the CONVENTIONS section instead of
repeating it. **If the two ever conflict, CONVENTIONS.md wins.** Fix the brief
when you notice a conflict.

---

## 1. Project

- **Brand:** Yaqot / ياقوت, a gold and fine-jewellery retailer in Egypt. It sells
  its own pieces and products from established jewellery brands (e.g. L'azurde).
- **Audience:** women shopping for jewellery, shabka (Egyptian engagement gold),
  wedding pieces and gifts.
- **Positioning:** premium, elegant, trustworthy, timeless. Black and gold.
- **Goal:**
  1. A public catalog where visitors discover the brand, browse, search and
     filter jewellery, view product details and images, and contact the store on
     WhatsApp.
  2. A custom admin dashboard where staff manage products, images, categories,
     collections, brands, branches and site content.
- **Catalog only.** See §8 for what is out of scope.

You act as a senior Laravel engineer, frontend developer, security reviewer and
QA engineer. Deliver working, tested code, not plans or snippets.

---

## 2. Stack (confirmed — do not replace)

Details and versions: CONVENTIONS §1.

- Laravel 12 (12.70 at the time of writing), PHP 8.2+, MySQL locally (`.env`),
  in-memory SQLite for tests. Windows + XAMPP: every command you run or
  document must work there.
- `nwidart/laravel-modules` ^12, one module per business area (CONVENTIONS §2).
- `mcamara/laravel-localization` for URL locales; `astrotomic/laravel-translatable`
  for translated model fields.
- `laravel/ui` **only** for the `AuthenticatesUsers` trait. Its scaffold
  (`ui bootstrap --auth`) is never run.
- `intervention/image` ^3 (GD) for the brand assets and, from Phase 3, for
  upload resizing.
- `cviebrock/eloquent-sluggable` is installed in Phase 2. It is used in the
  reference projects, so it needs no approval; list it with its version in the
  phase report.
- **No npm, Vite, Sass, Tailwind, Livewire, Inertia, Filament or SPA framework.**
  All assets are static files under `public/assets/`:
  - Bootstrap 5.3.8 dist (RTL build for Arabic);
  - jQuery 3.7.1, self-hosted, used by both admin and public scripts;
  - self-hosted OFL fonts;
  - the Soft UI Dashboard 1.1.0 admin theme (free, MIT).

  See CONVENTIONS §8.
- Tests: PHPUnit 11; code style: Pint.

---

## 3. Execution rules

### 3.1 Inspect, preserve, protect
- Inspect the relevant files before implementing. Do not assume versions.
- Never overwrite or revert the owner's work.
- **Never** run `migrate:fresh`, `migrate:reset`, `db:wipe` or any other
  destructive command without explicit approval.
- Never commit `.env`, secrets or credentials.
- **Commit only when the owner says so.** Each approved phase is one commit,
  ending with the `Co-Authored-By` line.
- New Composer packages need approval with a one-line justification, unless a
  reference project already uses them. No npm.
- No animation library. If one is truly needed, ask first with a justification.

### 3.2 Throwaway servers: `php -S` only
Never use `php artisan serve` for smoke tests or screenshots. It ignores
environment overrides, so it reads `.env` and writes sessions to the owner's
MySQL database.

Run this from `public/`, with the scratch SQLite variables exported:

```
php -S 127.0.0.1:<port> ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Do not use port 8000; the owner runs their own server there. See CONVENTIONS §3.

### 3.3 Reference projects
The owner's projects in `D:\xampp\htdocs` (mainly `sni-website` and
`dentists-hmvc`) were surveyed in Phase 0. Their approved conventions are in
CONVENTIONS.md. **Do not re-survey them unless the owner asks.** Installed
versions always win over patterns copied from older projects.

### 3.4 Never invent business facts
Never invent phone numbers, addresses, branch names, history, certifications,
weights, karats or prices. Missing content comes from editable settings and
renders a graceful empty state. Copy that the owner must approve is marked
`// REVIEW:` in the lang files. Business info still missing: §11.

### 3.5 Phase workflow
For each phase:

1. State the scope briefly.
2. Inspect the relevant files.
3. Implement.
4. Add or update tests.
5. Run the full suite, plus Pint and `composer validate` when Composer files
   change.
6. Review the full Git diff.
7. Fix what the phase introduced.
8. Report the changed files and the **actual** command results. Include
   screenshots (before/after where the UI changed) taken with headless Chrome.
   Headless Chrome has a 500px minimum window width, so render narrower
   viewports (360px) inside iframes.
9. Mark each acceptance criterion as passed, failed or **unverified**.
10. **STOP and wait for approval** before the next phase.

Never claim that something passed unless it actually ran. Separate
pre-existing failures from new ones. Never weaken or delete tests to get a
pass.

### 3.6 Multi-agent rules
This is the pattern used since Phase 1b; the owner restates it per phase.

- Independent workstreams run as **parallel subagents**.
- **Only the lead edits shared files:**
  - layout masters (`layouts/*/master.blade.php`);
  - `LayoutComposer`;
  - routes and `config/*`;
  - `bootstrap/app.php`;
  - `docs/CONVENTIONS.md` and this brief;
  - `tests/TestCase.php`;
  - `phpunit.xml`.

  Subagents report the edits they need in those files instead of making them.
- A **reviewer subagent reviews the combined diff** before the report. Fix its
  confirmed findings and list them in the report.
- Subagents follow the same rules: `php -S` only, no destructive commands, no
  commits.

### 3.7 Editing source files: Edit/Write tools only
- Change code and docs **only with the Edit and Write tools.**
- Never rewrite source files with `sed`, inline `php -r` or heredoc PHP, or any
  other script.
- Why: in Phase 2, bulk edits through scripts caused repeated escaping bugs,
  including broken namespaces. One script emptied an untracked file
  (`Setting.php`) that git could not restore.
- Shell scripts are fine for reading files, running tests and running commands.
  They never modify source.

---

## 4. Localization

Details: CONVENTIONS §3 and §4.

- Arabic (RTL) and English (LTR). **Both are always prefixed** (`/ar/...`,
  `/en/...`).
- **English is the default locale.** Any unprefixed GET redirects to the same
  path under `/en` and keeps the raw query string. No browser-language or
  session guessing on the public site.
- hreflang `ar`, `en` and `x-default` (pointing to `/en`) on every public page.
- **The admin is bilingual:**
  - `/ar/admin/...` uses the RTL Soft UI build and `/en/admin/...` the LTR one.
  - An unprefixed `/admin` goes to the locale last used (session), defaulting
    to `ar`.
  - Route names are `admin.*`.
  - The navbar and the login page have a language switcher that keeps the same
    page.
- All UI strings live in `lang/{ar,en}` (or module `lang/`). No hardcoded text
  in Blade or JS.
- **Translatable fields** use the Astrotomic package with `{field}_{locale}`
  form names (`name_ar`, `name_en`). Arabic and English are **both required**
  for displayed names; long descriptions are optional. Astrotomic falls back to
  `en` only as a safety net.
- **Not translated:** slugs, SKU, karat, weight, flags, sort orders.
- Slugs are single, stable and shared by both locales: sourced from the English
  name, falling back to the SKU, never empty, and never changed on update.

---

## 5. Design system, brand and motion

- **Palette:** tokens are in `public/assets/css/front.css` §1 and `admin.css`.
  - `--yq-gold` #D4AF66 is warmed from the logo.
  - The dark backgrounds are #111111, #1A1A1A and #232323; light sections use
    ivory (#F2EBDD) with dark text.
  - Contrast is WCAG AA or better. Re-check it if the logo changes.
  - No heavy gradients, glows or shadows.

  See CONVENTIONS §8.
- **Fonts** (self-hosted, OFL):
  - "Yaqot Sans" (IBM Plex Sans Arabic + IBM Plex Sans) for body text;
  - "Yaqot Serif" (Amiri + Cormorant Garamond) for headings;
  - split by `unicode-range`.

  Never letter-space or uppercase Arabic text.
- **Logo:**
  - `public/assets/images/brand/logo.png` is the single source: 1254×1254,
    transparent background, gold calligraphy including "JEWELRY".
  - `php artisan yaqot:brand-assets` generates the WebP sizes at 1x/2x, the
    favicons and `manifest.json`. It never upscales.
  - `<x-logo size="header|offcanvas|sidebar|footer|login" glint />` renders the
    logo. The header logo is 72px tall (56px below 992px).
  - Replacing the logo is a single-file swap followed by the command; see
    CONVENTIONS §8 "Brand assets".
- **Layout:**
  - spacious and photography-led, with refined product cards and minimal
    navigation;
  - responsive from 360px to wide desktop;
  - filters in an offcanvas panel on mobile;
  - the custom design must not look like default Bootstrap.
- **Admin styling:** Soft UI with Yaqot's colours. Readability and efficiency
  come before mood.
- **Motion system:** CONVENTIONS §8 "Motion". **Use its patterns and tokens; do
  not invent new timings or effects.**
  - The signature is a gold glint on the logo, made with a CSS mask.
  - Patterns: scroll reveal with stagger, ornament draw-in, header shrink,
    button sweep, nav underline, image frame zoom, offcanvas stagger, hero
    load sequence.
  - Only `transform` and `opacity` are animated. Hidden states apply only under
    `html.js`, with a failsafe.
  - Motion respects `prefers-reduced-motion` and the reading direction.
  - The admin uses subtle motion only.
  - The Phase 4 mapping (cards, gallery, filters, headings) is at the end of
    that section.

---

## 6. Architecture summary

Details: CONVENTIONS §2 and §5–§7.

- **Modules today:** `Auth`, `Home` (public home + admin dashboard), `Product`
  (products + images), `Category`, `Collection`, `Brand`, `Branch`, `Setting`.
- **Modules planned:** `Page` (about, contact, privacy, sitemap).
- **Admin authentication:**
  - a separate `admins` table with the `admin` guard and model
    `Modules\Auth\Models\Admin`;
  - no registration and no password reset;
  - login throttled to 5 attempts per minute;
  - admins are created only with `php artisan yaqot:create-admin`;
  - no roles or permissions: every admin is a full admin.

  This replaces the original `users.is_admin` plan (decision D5).
- **Admin forms:**
  - submitted with AJAX through `admin.js`;
  - validated with Form Requests that return standard 422 JSON, with every field
    error shown under its field;
  - no `catch (\Throwable)` that hides errors;
  - multi-table writes run in `DB::transaction()`;
  - deletes go through the shared modal and end with a flash toast.
- **Images (D7):**
  - `public` disk at `products/{id}/{uuid}.{ext}`, with server-generated names;
  - images over ~1600px are scaled down;
  - files are deleted through `Storage` when their row or product goes.
- **Layout data** comes from `App\View\Composers\LayoutComposer`. There are no
  queries or `@php` blocks in Blade, and no `{!! !!}` on admin-edited content.
- **Route caching:** deploy with `php artisan route:trans:cache`, never plain
  `route:cache` or `optimize`. `trustHosts()` is on, so `APP_URL` must be the
  real URL.

---

## 7. Data model (Phase 2 target)

Conventions for migrations, models, slugs and images: CONVENTIONS §5.

**Allowlists** go in `config/catalog.php`, so they can change without code edits:
- karats: 18, 21, 24
- gold colors: yellow, white, rose, multi
- stone types: none, diamond, zircon, gemstone, pearl

**Tables:**

- **products**
  - Columns: id, sku (unique), slug (unique), brand_id (nullable FK; null means
    house or unbranded), karat, gold_color and stone_type (each nullable and
    allowlisted), weight_grams (`decimal(8,3)`, nullable), is_published,
    published_at (nullable), is_featured, timestamps, soft deletes.
  - Translations: name, description.
  - **No price columns.**
  - **Visibility:** one reusable scope (`visible`) covering published, at least
    one image, and a non-empty name in **both** locales. The owner approved
    checking both after Phase 2.
  - `karat`, `gold_color`, `stone_type` and `weight_grams` are catalog filters,
    and each one has an index.
- **categories** (product type: rings, bracelets, …)
  - Columns: slug, image_path (nullable), is_active, sort_order.
  - Translations: name, description.
  - Flat: no hierarchy unless the owner approves one.
  - Many-to-many with products.
- **collections** (curated or occasion: shabka, wedding, gifts, …)
  - Columns: slug, image_path, is_active, show_on_homepage, sort_order.
  - Translations: name, description.
  - Many-to-many with products.
  - No date scheduling.
- **brands**
  - Columns: slug, is_active, sort_order.
  - Translations: name.
  - Displayed as text. Add a logo field only if the owner confirms permission to
    use brand logos.
- **product_images**
  - Columns: product_id, path, sort_order.
  - The lowest sort_order is the primary image, deterministically.
  - Alt text defaults to the product name in the current locale.
- **branches**
  - Columns: phone, whatsapp (nullable), map_url (nullable, https only),
    is_active, sort_order.
  - Translations: name, address, working_hours.
- **settings**
  - A key–value table, read through a small cached Settings service that is
    invalidated on save. Translatable values are stored per locale.
  - Keys:
    - whatsapp_number, phone, email;
    - facebook_url, instagram_url, tiktok_url;
    - hero title, subtitle, image and CTA;
    - about content, footer text;
    - default meta description.
  - Long text is plain text with paragraphs; no HTML is stored or rendered.
  - No secrets in settings.

**Seed data:** factories, plus a clearly named, **local-only demo seeder** with
obviously fake data. The seeder refuses to run in production. Seed real
categories, collections or brands only after the owner supplies and approves
the lists.

---

## 8. Scope exclusions

Do **not** implement:
- cart, checkout, payments, orders or shipping;
- customer accounts or public registration;
- prices, price columns, live gold prices or price calculation;
- ERP or inventory sync, mobile apps, analytics, newsletters or inquiry records;
- unrequested third-party services, roles or permissions packages, an
  animation library.

Future work, not to be built now:
- a manual "today's gold price per gram" banner per karat, with a last-updated
  time;
- inquiry records, analytics, a newsletter.

---

## 9. Remaining phases

Each phase ends with a report and a STOP (§3.5). Acceptance criteria are marked
passed, failed or unverified.

### Phase 2 — Data layer (done; see §11)
**Scope:**
- Install `cviebrock/eloquent-sluggable`.
- Create the modules from §6 that own data. Delete the generator leftovers.
- Migrations for every table in §7, with translation tables and indexes on the
  real query patterns.
- Models with `$fillable`, casts, relationships and translations; scopes
  (`visible`, `active`, `ordered`); slugs as in §4.
- `config/catalog.php`.
- Product image model behaviour: primary image, and file cleanup on delete and
  on force-delete (through a model event).
- The Settings service, cached and invalidated on save.
- Factories for every model, and the local-only demo seeder.
- Do not build admin or public pages in this phase.

**Acceptance:**
- Migrations run on a scratch database.
  - Never `migrate:fresh` the owner's database.
  - Ask before running `migrate` against MySQL.
- Relationships, SKU and slug uniqueness, nullable attributes, translation
  fallback, image ordering and the primary image, and file cleanup are all
  tested, with `Storage::fake`.
- Slugs are never empty and stable on update; the SKU fallback is tested.
- The visibility scope is tested for each condition.
- A category, collection or brand with products attached cannot be deleted;
  deactivation is offered instead. Enforce this at the model or service level
  now; the UI comes in Phase 3.
- The Settings cache invalidates on save, and an empty settings table is safe.
- The demo seeder refuses to run in production (tested).
- `preventLazyLoading` stays on, and the full suite passes.

### Phase 3 — Admin
**Scope:**
- **Dashboard:** total, published and draft product counts; active categories;
  recently updated products.
- **Products:**
  - Index:
    - search by name or SKU;
    - filter by status, category, collection, brand, karat and featured;
    - sortable columns (allowlisted) and server pagination;
    - status badges in text, not colour alone.
  - Create and edit:
    - a sectioned form with ar/en tabs;
    - searchable category and collection multiselects (small jQuery code unless
      the owner approves a library);
    - publish and featured toggles;
    - a "view on site" link.
  - Images:
    - multi-upload with preview;
    - reorder by drag (SortableJS only with approval) or up/down buttons;
    - delete a single image.
  - Soft delete with restore.
- **Categories, collections, brands:** full CRUD. Deletion is blocked while
  products are attached, **including soft-deleted (trashed) products**; offer
  deactivation instead.
  - When a delete is refused, the admin must explain why.
  - If any of the blocking products are trashed, the message says so, with the
    count. For example: "linked to 5 products, 2 of them in the trash".
  - The model guard throws `App\Exceptions\ModelInUseException`. Extend the
    `admin.messages.in_use` string with counts.
- **Branches:** full CRUD.
- **Settings:** one grouped page. The WhatsApp number uses Egyptian
  normalisation, and every URL must be https.
- **Every write operation:** a Form Request, `auth:admin`, CSRF protection and
  explicit `$fillable`.
- **Menus** come from `config/navigation.php`. Both admin locales work for every
  page.

**Acceptance:**
- Guests are redirected to the localized login page. Users of the `web` guard
  cannot reach the admin. `/register` does not exist.
- CRUD is tested for every resource, in both admin locales where the output
  differs. Validation failures return 422 JSON with every error.
- Upload validation is tested:
  - jpg, jpeg, png and webp only;
  - at most 5 MB, within dimension limits;
  - non-images rejected;
  - `UploadedFile::fake()->image()`.

  Large images are resized, and files are cleaned up on delete.
- Publishing changes public visibility (tested through the scope).
- Settings writes require an admin.
- `yaqot:create-admin` is tested.
- Screenshots of each admin page in Arabic and English, with no console errors.
- Modals and toasts are checked with real content. This is an open item from
  Phase 1c. (Done in 3a.)

**Phase 3b additions (decided at the 3a review):**
- **Image minimums per upload type** (`config('catalog.images.min_pixels')`):
  products 800px per side; categories and collections 200px. Pass the type to
  `ImageUploader::rules('products')` and `<x-admin.image-input type="products">`.
- `php artisan view:cache` passes at the end of 3b: no module may lack the
  views folder it needs to compile.
- Branch and settings **`phone`** fields accept Egyptian landlines as well as
  mobiles. `App\Rules\EgyptianMobile` is for **WhatsApp numbers only**.

### Phase 4 — Public site
**Routes:** all under `/{ar|en}`, named `front.*`. Inactive or missing
resources return 404.

| URL | Page |
|---|---|
| `/` | home |
| `/catalog` | catalog |
| `/categories/{slug}` | category |
| `/collections/{slug}` | collection |
| `/brands/{slug}` | brand |
| `/products/{slug}` | product |
| `/about` | about |
| `/contact` | contact and branches |
| `/privacy-policy` | privacy policy |

The sitemap is added in Phase 5.

**Scope:**
- **Home:**
  - hero from settings;
  - active categories;
  - featured products;
  - homepage collections, the one chosen in Settings ("prominent_collection",
    optional) shown first and large;
  - brands strip;
  - latest additions;
  - about intro;
  - contact CTA.

  The header and footer already exist; the footer shows contact, social and
  branch info only when configured.
- **Catalog:**
  - `q` searches the name in the current locale plus the SKU;
  - filters: category, collection, brand, karat, gold_color, stone_type,
    weight_min, weight_max. Allowlisted values come from `config/catalog.php`;
    stone_type was added after Phase 2;
  - `sort` is allowlisted: newest, name, weight_asc, weight_desc;
  - every parameter is validated, and invalid values are ignored safely;
  - 24 per page, keeping the query string;
  - a clear-filters control, an empty state, and offcanvas filters on mobile;
  - only visible products are shown.
- **Category, collection and brand pages:** a header (name, description, image)
  and the same filters and pagination as the catalog.
- **Product page:**
  - a gallery with thumbnails, keyboard accessible (jQuery);
  - attributes, hiding empty ones;
  - links to its categories and collections, with breadcrumbs;
  - related products: published, same category, excluding this one;
  - the WhatsApp CTA;
  - a note that prices follow the daily gold price. Its wording is in the lang
    files, marked `// REVIEW:`.
- **WhatsApp helper (a reusable class):**
  - normalises `01…`, `+201…`, `00201…` and `201…` to `201XXXXXXXXX`;
  - builds `https://wa.me/{n}?text={rawurlencode(msg)}`;
  - the message is in the current locale: greeting, product name, SKU, URL and
    an availability question;
  - the CTA is hidden when no number is configured.
- **`setting()` helper:** add it in Phase 4, as an autoloaded function in
  `app/Helpers/helper.php` wrapping `Modules\Setting\Services\SettingService`.
  Views read it through the layout composer or controllers, not with queries in
  Blade.
- **About** comes from settings only.
- **Contact:** active branches with a `tel:` link, WhatsApp, hours and the map
  link.
- **Privacy policy:** describes only real practices, flagged for business review.
- **Motion:** use the CONVENTIONS "Motion" patterns, following its Phase 4
  mapping.

**Acceptance:**
- Every page returns 200 in both locales with the correct `lang` and `dir`.
- Search, combined filters, the sort allowlist and pagination with the query
  string are tested. Unpublished, missing and inactive resources return 404.
- The site renders with empty settings.
- WhatsApp normalisation and URL building have unit tests.
- No N+1 queries: translations, images and brand are eager-loaded.
- Responsive from 360px to wide desktop with no overflow; screenshots in both
  locales.

### Phase 5 — Quality
**Scope:**
- **SEO:**
  - per-page title, meta description, canonical, Open Graph and hreflang;
  - `/sitemap.xml` in both locales, listing visible content only;
  - `robots.txt`;
  - structured data: BreadcrumbList, plus JewelryStore/Organization **only**
    from real settings, with no offers, prices or ratings.
- **Reviews:** security, queries and N+1, responsive layout, and accessibility:
  - semantic HTML and labelled inputs;
  - visible focus states;
  - keyboard-operable menu, gallery and offcanvas;
  - AA contrast;
  - status never shown by colour alone.

  Fix the defects that are in scope.
- **Open items:** the Safari and Firefox glint check, and the possible dead
  strip under the scrolled header (§11).

**Acceptance:**
- The sitemap excludes drafts.
- Every external URL is https-only.
- No `{!! !!}` on content.
- Sort and filter fields are allowlisted.
- Unbounded queries are gone.
- Each review's findings are listed as fixed or deferred, with a reason.

### Phase 5b — Hero redesign (deferred; after Phase 5, before Phase 6)
Requested by the owner on 2026-11-01; **not to be started before Phase 5 is done.**
- The brief is `docs/design/hero-prompt.md` and the mockup is
  `docs/design/hero-mockup.png`. **Both still have to be added to the
  repository**: they were not on disk when this phase was recorded.
- Redesign the homepage hero to match them, keeping its content source
  (Settings: title, subtitle, image, button, with the 4a fallbacks), the motion
  rules (load sequence, reduced motion), RTL/LTR and the 360px minimum width.
- Same verification as the other public phases: tests, screenshots of both
  locales at 1440/768/390/360 with demo data and with empty settings, no
  overflow, no console errors.

### Phase 6 — Release
**Scope:**
- the final full test run;
- a check that every asset path resolves (`AssetPathsTest`; this replaces
  `npm run build`);
- a production config checklist: `APP_DEBUG=false`, the real `APP_URL`,
  `route:trans:cache`;
- PHP upload limits (decided at the 3b review): **`post_max_size` ≥ 64M** and
  **`upload_max_filesize` ≥ 8M**, so a full 12-image product gallery (12 × 5 MB
  images) can be saved in one request. With lower limits the save is refused
  with the "too large" message and nothing is stored;
- a README deployment guide covering:
  - env setup;
  - `migrate --force`;
  - `storage:link`;
  - `route:trans:cache`;
  - `yaqot:create-admin`;
  - backup and restore of the database and `storage/app/public`;
- the final report: what was built and what remains, the key decisions,
  migrations and dependencies, the exact commands with their results, security
  and performance checks, deployment, and the open business questions.

This phase needs the hosting target (§11).

### Overall acceptance (all phases)
1. A premium black-and-gold custom design that does not look like default
   Bootstrap.
2. Arabic RTL and English LTR both correct, with fallback working.
3. Responsive from small mobile to wide desktop.
4. Products, images, categories, collections, brands, branches and settings are
   all manageable in the admin.
5. Images upload, validate, reorder and clean up.
6. Search, filters, sorting and pagination work and are validated.
7. Only visible products are public; drafts return 404 and are absent from the
   sitemap.
8. Stable, shareable URLs.
9. WhatsApp links are correct when configured and hidden when not.
10. Missing settings never break pages.
11. The admin is locked down: no registration, admin-only access, every write
    authorized.
12. No N+1 or unbounded queries.
13. Tests cover critical behaviour and pass.
14. Every asset path resolves (there is no build step).
15. No secrets, invented facts or excluded features.
16. Deployment, backup and recovery are documented.

---

## 10. Decision log (summary)

| # | Decision | Where |
|---|---|---|
| Stack | No npm, Vite or laravel/ui scaffold; static assets; Bootstrap dist; OFL fonts | CONVENTIONS §1, §8 |
| D1 | nwidart modules, stubs removed | §2 |
| D2 → 1b | mcamara; `/ar` and `/en` always prefixed; English default; x-default `/en` | §3 |
| D3 | `{field}_{locale}` form names → Astrotomic; both languages required (1b) | §4 |
| D4 | Sluggable: English name → SKU fallback, never empty, `onUpdate=false` | §5 |
| D5 | `admins` table + `admin` guard, `yaqot:create-admin`, no roles | §7 |
| D6 | AJAX forms, 422 JSON with every error, no swallowed exceptions | §6 |
| D7 | ImageTrait pattern fixed (Storage deletes, UUID names, resize ~1600px) | §5 |
| D8 | Soft UI Dashboard 1.1.0 (MIT) for the admin, not Metronic; public site fully custom | §1, §8 |
| D9 | Migration conventions as proposed | §5 |
| 1b | Official logo + generated assets; gold from the logo; jQuery 3.7.1 | §8 |
| 1c | Bilingual admin; 72px header logo; motion system | §3, §8 "Motion" |
| 2 | Visibility needs both names; trashed products block taxonomy deletes; stone_type filterable; `setting()` in Phase 4 | §5; brief §7, §9 |
| 2 | Source edits with Edit/Write only, never scripts | brief §3.7 |
| 3a | Admin building blocks; uploads re-encoded to WebP (EXIF stripped); refused deletes report product + trash counts | CONVENTIONS §6 |
| 3b | Gallery saved with the form (order tokens, staged removal, primary = first, max 12); phone = landline or mobile (national form), WhatsApp = mobile (`201…`); settings translations filled in both languages or neither; damaged images rejected by decoding; per-process fake disk in tests | CONVENTIONS §6, §9 |

---

## 11. Status

_Updated at the end of Phase 4b, 2026-11-01 (approved and committed)._

**Phases done**

| Phase | Commit |
|---|---|
| Baseline (fresh Laravel 12 + ui, translatable, intervention) | `e570e03` |
| 0 — Repository audit and conventions survey | — (no code changes) |
| 1 — Foundation: modules, localization, static assets, admin auth | `cd3db23` |
| 1b — Official logo, jQuery admin JS, `/ar` and `/en` prefixed locales | `8fd1c68` |
| 1c — Bilingual admin, 72px header logo, motion system | `788e910` |
| Handoff — this brief | `2a00f78` |
| 2 — Data layer: modules Product, Category, Collection, Brand, Branch, Setting; migrations, models, Settings service, factories, demo seeder | `e5c7a92` |
| 3a — Shared admin building blocks + Categories CRUD | `9a97ed2` |
| 3b — Products, Collections, Brands, Branches, Settings, Dashboard | the "Phase 3b" commit (`git log --grep "Phase 3b"`) |
| 4a — Public building blocks, home, catalog, footer | `9d08288` |
| 4b — Product, category, collection, brand, About, Contact, Privacy pages | the "Phase 4b" commit (`git log --grep "Phase 4b"`) |

**Phase 3a (approved 2026-10-28, with the review changes applied):**
- Shared building blocks, listed in CONVENTIONS §6 "Admin building blocks":
  `AdminListing`, `components/admin/*`, `ImageUploader`, `TranslatedFields`,
  `EgyptianPhone` + `EgyptianMobile`, toggle and save responses, and the named
  delete modal. `ModelInUseException` now carries product and trashed counts.
- Categories: index, create, edit, the active switch, delete with the guard, and
  the image, in both admin locales.
- Fixed along the way: Soft UI's invisible `.btn-close` (a corrupted icon in the
  vendor CSS); the Soft UI ripple error on AJAX submit; `refreshApplicationWithLocale()`
  skipping the test class's own `setUp()`, which let `Storage::fake` lapse.
- Review changes: Arabic refusal wording (no number after «بـ»: «مرتبط بمنتجات
  (5 منتجات، منها منتجان في سلة المحذوفات)»); per-type image minimums. WebP
  re-encoding and the PATCH toggle without a Form Request were approved.

**Phase 3b (approved 2026-10-28, with the approval additions applied):**
- Shared layer first (lead), then four parallel module agents, then a reviewer
  on the combined 3a+3b diff. New blocks (CONVENTIONS §6): `<x-admin.select>`,
  `<x-admin.multiselect>`, `<x-admin.image-gallery>`, `<x-admin.restore-button>`,
  `<x-admin.badge>`, `<x-admin.stat-card>`; `ImageUploader::storeDuring()`;
  `EgyptianPhone::normalizePhone()` + `App\Rules\EgyptianPhoneNumber`;
  `App\Rules\DecodableImage`; group error placement (`data-field-errors`); 413
  handling; `translatable-fields :values`; optional `name-cell` url and
  `empty-state` listing; `row-actions :confirm`.
- **Products:** index with search (name/SKU), filters (status, category,
  collection, brand incl. "none", karat, featured, trash only/with), sorting;
  publish/featured switches; soft delete ("move to trash" wording) and restore;
  sectioned form with ar/en tabs, selects, searchable category/collection
  multiselects, the gallery (drag + arrow reorder, staged removal with undo,
  previews, primary = first, at most 12, 800px minimum, files in
  `products/{id}/`); "View on site" disabled until `front.products.show` exists.
- **Collections** (image, homepage switch + filter) and **Brands** (text only).
- **Branches** (phone landline or mobile, stored national; WhatsApp optional,
  mobile only, stored `201…`; map link https only; search also matches a phone
  typed in another form).
- **Settings:** one grouped page (contact, social, hero with image, about,
  footer, SEO); every URL https; WhatsApp normalized; translated values in both
  languages or neither; `SettingService::formValues()`.
- **Dashboard:** total/published/draft products, active categories, the 8 most
  recently updated products.
- Review fixes: `updated_at` now moves on translation/relation/image-only
  edits; damaged images are a 422 instead of a 500; 413 explained; trash
  wording; empty-type `.webp` files and desktop file drops in the gallery;
  branch phone search. Responsive fixes found by the width sweep: table cells
  wrap, switch badges stack below 1400px, action labels from 1400px, the
  featured column from 768px, collection order from 1400px.
- `php artisan view:cache` passes.
- Approval additions: **"Delete permanently"** for trashed products
  (`DELETE /products/{id}/force`, refused unless trashed; rows, links, image
  rows and files removed, files after commit); **hotlines** (5 digits starting
  with 1) accepted in phone fields, never in WhatsApp fields;
  **`docs/COPY_REVIEW.md`** lists every `// REVIEW:` string (key, Arabic,
  English) — «أكثر من لون» became «متعدد الألوان»; the PHP upload limits are in
  §9 Phase 6.
- No MySQL changes were made in 3b (no new migrations).

**Phase 4a (approved 2026-11-01 with the review changes below; committed as
"Phase 4a", `git log --grep "Phase 4a"`):**
- Shared public building blocks, listed in CONVENTIONS §8 "Public building
  blocks": `setting()`, `WhatsApp`, `SiteContact`, `PlainText`,
  `EgyptianPhone::telUri()`, `onSite()` on categories/collections/brands,
  `Product::forCard()` + `weight_label`, `<x-front.product-card|product-grid|tile|
  section-heading|breadcrumbs|empty-state|filter-offcanvas|pagination>`,
  `CatalogFilters` + `CatalogOptions` + the shared filter form, `FooterComposer`.
- **Home:** hero from Settings (title falls back to the brand name, subtitle and
  image hidden when empty, button falls back to the catalog), active categories,
  featured, homepage collections (the "prominent_collection" setting, see
  below, comes first and large), brands strip, latest additions
  (not repeating featured), about intro (first paragraph), contact CTA. Every
  section is hidden when empty. Until 4b, tiles and brands link to the catalog
  filtered by their slug, and product cards are not links.
- **Footer:** text, phone, WhatsApp, email, https social links and up to four
  active branches, each only when configured.
- **Catalog** `/catalog`: search (name in either locale + SKU), category,
  collection, brand (slugs), karat, gold colour, stones, weight range; sort
  newest/name/weight; 24 per page with the query string; clear filters (keeps
  the sort); two empty states; offcanvas filters below 992px.
- Motion: the stagger index now counts the items revealed together (CONVENTIONS
  "Motion"), so lower grid rows don't wait.
- Cards hide a deactivated brand's name. Card images have `alt=""` because the
  product name is the link text beside them.
- Palette retune (gold `#d4af66`, black `#111111`) across front.css, admin.css,
  the generated icons and the demo seeder colours.
- No MySQL changes (no migrations).
- **Review changes (2026-11-01):**
  - Catalog search matches the name in **both** locales (plus the SKU) on either
    page language; a piece matching both names is listed once.
  - The prominent homepage collection is the **"prominent_collection" setting**
    (Settings → Homepage, a select of active collections, stored as the
    collection id; empty = no prominent tile). It is shown large only when it is
    also on the homepage and has visible products. `config('catalog.front.
    prominent_collection')` was **removed**: SettingService treats an empty value
    as unset, so a config fallback would bring `shabka` back whenever the owner
    cleared the select.
  - `phpunit.xml` pins `APP_URL=http://localhost`, so tests no longer depend on
    the developer's `.env` (it now has `http://127.0.0.1:8000`).
  - The hero redesign was recorded as the deferred **Phase 5b** (§9).
- **Next:** Phase 5, in a fresh session. Then the **deferred hero redesign
  (Phase 5b)**, then Phase 6.

**Phase 4b (approved and committed 2026-11-01):**
- Approval decision: the homepage contact CTA rule stays as built (shown only when
  WhatsApp or a phone number is configured).
- Building blocks and conventions: CONVENTIONS §8 "Public building blocks", the
  "Added in Phase 4b" table (`TaxonomyPage`, product page and gallery, `Modules/Page`).
- **Pages:** product (`/products/{slug}`), category, collection, brand, About,
  Contact (+ active branches), Privacy policy. New module `Page`. Cards, tiles and
  the brand strip link to the real pages; the admin "View on site" uses the slug.
- Shared results block extracted from the catalog (`partials/results.blade.php`).
- Changes to existing behaviour: the homepage contact CTA shows only when WhatsApp
  or a phone number is configured (the Contact page link is just an extra there);
  `front.about` / `front.contact` now exist, so the header and footer list them;
  `LocalizationTest` was updated for that. Admin string `view_on_site_soon` became
  `view_on_site_unsaved` ("Available once the product is saved").
- Review (read-only subagent, combined 4a+4b): no security or correctness defects.
  Fixed: COPY_REVIEW.md now lists all 4b copy (#39–65); dead `Route::has`
  branches removed; privacy "other sites" wording widened. Deferred (minor): the
  main gallery image keeps `alt` = product name when the thumbnail changes (the
  caption announces "Image n of N"); thumbnails carry both `aria-pressed` and
  `aria-current`; a stray own-filter parameter (`?category=x` on a category page)
  is ignored but kept in the pagination links.
- Tests: **848 passed (4881 assertions)**; Pint clean; `view:cache` passes. 112
  renders (7 pages × 2 locales × 1440/768/390/360 × demo data / empty settings):
  no horizontal overflow, no console errors, correct `lang`/`dir`.
- No MySQL changes (no migrations).

**Phase 4b scope (the owner, 2026-11-01):** reuse the 4a public building blocks
only (CONVENTIONS "Public building blocks" + "Motion"); no new patterns unless
the lead adds them to the shared layer first.
- Category, collection and brand pages: header (name, description, image), the
  shared filter form without their own filter, pagination, empty state,
  breadcrumbs. Only `onSite()` records; anything else is 404.
- Product page: gallery (main image + thumbnails, keyboard accessible, alt =
  product name, no reveal on the main image); attributes (hide empty ones, hide
  a deactivated brand); links to its active categories/collections and
  breadcrumbs; related products (visible, same category, excluding this one);
  the WhatsApp CTA with the product message (hidden when no number); the
  gold-price note, marked REVIEW; drafts, trashed and missing products are 404.
- About (from settings, plain-text paragraphs), Contact + branches (tel links,
  WhatsApp, hours, https map links), Privacy policy (real practices only,
  marked REVIEW).
- Switch the temporary links: product cards → product pages, tiles →
  category/collection pages, brand strip → brand pages. Enable the admin "View
  on site" button.
- Every new REVIEW string goes into docs/COPY_REVIEW.md.
- Multi-agent: at most 2 parallel subagents (e.g. product page / taxonomy pages
  + static pages); the lead owns shared files; a reviewer subagent reviews the
  combined 4a+4b diff, and its confirmed findings are fixed and listed.
- Verification: full suite, Pint, view:cache; screenshots of every new page in
  both languages at 1440/768/390/360 with demo data and with empty settings; no
  overflow, no console errors. Same rules (Edit/Write only, `php -S` with the
  storage path set in the router, real storage and MySQL untouched, no commit
  without approval). Report, then stop.

**Tests:** `php artisan test` reported **781 passed (4253 assertions)** at the end
of 4a and 848 at the end of 4b. Pint is fully clean; `php artisan view:cache` passes.

**Local MySQL** (`yaqot_jewelry`), as of the end of Phase 2:
- All 16 Phase 2 migrations have run, additive only.
- The demo seeder has run: 24 products (21 visible), 48 images, 4 categories,
  3 collections, 3 brands and 2 branches, all with obviously fake data
  (`DEMO-` SKUs).
- `public/storage` is a junction to `storage/app/public`.

**Open items**
- **Glint in Safari and Firefox:** the CSS-mask glint was verified only in
  Chrome (headless).
- **Strip under the scrolled header:** in headless Chrome, a narrow strip just
  below the shrunken header also catches clicks. That is safe (nothing clicks
  through), but it may be a small dead area. Check it in a real browser.
- ~~Modals and toasts with real content~~: checked in Phase 3a (delete modal,
  refused delete, success, validation and status toasts, in both locales).
- ~~`php artisan view:cache` fails~~: passes since 3b. Re-check before Phase 6.
- Real mouse-hover sequences (glint replay, button sweep) were checked only in
  the code, not captured as frames.
- ~~Emptying the trash~~ and ~~hotlines~~: done at the 3b approval.
- **Deployment / local php.ini:** `post_max_size` ≥ 64M, `upload_max_filesize`
  ≥ 8M (§9 Phase 6). The owner updates the local XAMPP php.ini (was 40M).
- English products index at exactly 360px scrolls its table by 5px (all
  controls visible); 390px and up have no overflow.
- `AdminListing`: `?sort=x` without `dir` uses the page's default direction
  (only hand-written URLs; the UI always sends `dir`).
- Gallery drag-and-drop and file drops were checked in code and with scripted
  DOM events in headless Chrome, not with a real mouse.

**Business info still missing (ask; never invent)**
- Real lists of **categories, collections and brands**. Until then, use
  factories and the demo seeder only.
- **Branches:** names, addresses, working hours, phones, map links.
- **Contact info:** WhatsApp number, phone, email; social links (Facebook,
  Instagram, TikTok).
- About text and hero copy; review of the `// REVIEW:` strings, collected in
  `docs/COPY_REVIEW.md` (keep it current when adding or approving copy), the
  gold-price note and the privacy policy.
- Whether brand logos and brand product photos may be used.
- **Hosting target** (shared hosting or VPS), needed for Phase 6.
