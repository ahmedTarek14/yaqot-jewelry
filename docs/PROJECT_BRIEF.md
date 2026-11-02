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
  commits. **Subagents and reviewers run only `php artisan test` and read-only commands**
  (never `migrate`, `db:seed`, `tinker` or any writing artisan command unless the lead assigns
  it); only the lead touches the local MySQL, after a backup (CLAUDE.md, CONVENTIONS §11).

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
- product types (Phase 4c): jewelry, bar, coin
- ~~gold colors and stone types~~: **since Phase 4c these are owner-managed rows of
  `product_options`** (admin: Gold colours, Stone types), not config. Products point at
  them with `gold_color_id` / `stone_type_id`.

**Tables:**

- **products**
  - Columns: id, sku (unique), slug (unique), type (jewelry | bar | coin, default
    jewelry; Phase 4c), brand_id (nullable FK; null means
    house or unbranded), karat, gold_color_id and stone_type_id (nullable FKs to
    `product_options`, jewelry only; Phase 4c, replacing the old `gold_color` /
    `stone_type` strings), weight_grams (`decimal(8,3)`, nullable), is_published,
    published_at (nullable), is_featured, timestamps, soft deletes.
  - Translations: name, description.
  - **No price columns.**
  - **Visibility:** one reusable scope (`visible`) covering published, at least
    one image, and a non-empty name in **both** locales. The owner approved
    checking both after Phase 2.
  - `karat`, `gold_color_id`, `stone_type_id`, `type` and `weight_grams` are catalog
    filters, and each one has an index.
- **product_options** (Phase 4c): one table for gold colours and stone types.
  Columns: group (`gold_color` | `stone_type`), slug (unique, stable, used in URLs),
  is_active, sort_order; translations: name (ar and en, both required). Guarded delete
  like brands (any product, trashed ones included, blocks it; deactivate instead).
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

### Phase 4c — Owner-managed options, gold bars and coins (done; see §11)
Requested by the owner on 2026-11-01. **Order from here: 4c → 5b hero → 5 + 6 together.**
- **Part A:** gold colours and stone types move from `config/catalog.php` to the
  database (one `product_options` table with a `group` column), with admin CRUD built
  like Categories/Brands (list, search, toggle, sort, guarded delete). Data migration
  from the old config values and lang labels, then the old string columns are dropped;
  karats stay in config. Inactive options: hidden from public filters and the product
  page, still shown (marked) in the admin form for products that use them.
- **Part B:** `products.type` (jewelry | bar | coin). Admin type select and list
  filter; gold colour and stones are jewelry-only. Public `/bullion` ("سبايك وجنيهات" /
  "Gold bars & coins", REVIEW) with type, weight, karat, brand (the manufacturer),
  search and sort (weight first) and the gold-price note (REVIEW); `/catalog` and the
  taxonomy pages list jewelry only; nav item; product page adapts by type; optional
  homepage section; demo bars and coins. No prices, no live gold rates, no external APIs.
- **Part C:** InfinityFree feasibility check (report only): findings are in Phase 6.

### Phase 4d — Admin users and analytics (before 5b)
Requested by the owner on 2026-11-02. **Order: 4d → 5b hero → 5 + 6 together.**
- **Part A — Admin users:** CRUD for `admins` (list: name, email, active, last login; add; edit;
  activate/deactivate; delete), all admins equal (no roles). Server-side safeguards (tested): no
  self-delete/self-deactivate, the last active admin is never deleted or deactivated; a deactivated
  admin cannot log in and their open sessions stop working. "My account" (own name, email, password;
  current password required). An admin can set another admin's password (no email reset).
  `last_login_at`. `Password::defaults()` = min 10, mixed case, digit, symbol. Login throttling and
  `yaqot:create-admin` keep working.
- **Part B — Analytics:** (1) optional `ga_measurement_id` setting; GA4 on public pages only, only
  after the visitor accepts a cookie notice, no third-party request when off; privacy copy switches
  with the setting (REVIEW). (2) In-house per-product daily counters (views, WhatsApp clicks via
  `/go/whatsapp/{slug}`), atomic, bots skipped, no personal data; dashboard "Most viewed" / "Most asked
  on WhatsApp" (7/30 days, top 10) and a 30-day SVG line chart; 13-month retention, scheduled
  `yaqot:prune-stats` plus a dashboard button because the host has no cron.
- Additive migrations only (`admins.is_active`, `admins.last_login_at`, `product_daily_stats`).
  Details: CONVENTIONS §1 (third-party exception), §7, §8 "Analytics".

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

### Phase 5b — Hero redesign (deferred; after Phase 4c, before Phases 5 + 6)
Requested by the owner on 2026-11-01. **Order (updated after 4c): 4c → 5b → Phases 5 and 6 together.**
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

**InfinityFree feasibility (checked in Phase 4c, 2026-11-01; nothing was changed).**
The target limits are: no SSH, no Composer on the server, about 30,000 files and folders,
10 MB per file, and PHP files over 1 MB silently rejected. A scratch copy outside the repo
(no `.git`, no uploaded images, no `.env`) got `composer install --no-dev`:
- **File count: 8,849 entries** (7,525 files + 1,324 folders), 32.9 MB. `vendor/` is 8,117 of
  them; the project itself is 731 (Modules 359, public 116, tests 61, resources 50, app 47).
  That leaves about 21,000 entries of the 30,000 for uploaded images and anything else
  (uploads go to `products/{id}/`: one folder plus its images per product; the demo has 48
  images). At the 12-image maximum that is roughly 1,600 products; at the usual 3–4 images,
  several thousand. Dropping `tests/` and `docs/` from the upload saves ~100.
- **PHP files over 1 MB: none**, with or without `--optimize-autoloader` (the repo's
  composer.json already sets `optimize-autoloader: true`; the scratch copy was also built with
  it turned off). The largest are `vendor/composer/autoload_static.php` (756 KB optimized,
  37 KB not) and `autoload_classmap.php` (682 KB optimized, 10 KB not), then Carbon's
  `CarbonInterface.php` (447 KB). **The two optimized autoload files are at ~75% of the 1 MB
  limit and grow with every class** (167 classes in Modules today; 5,247 classes in the map),
  so re-run this check after adding packages; if one ever crosses 1 MB, build without
  `-o` (the PSR-4 fallback is slower but only a few classes load per request).
- No file over 10 MB: the biggest is a 713 KB vendor script, then Carbon's 447 KB
  `CarbonInterface.php` and the 438 KB `logo.png`.
- Items below marked _(confirm)_ come from general knowledge of the host, not from a test:
  check them in the InfinityFree panel or docs before relying on them.
- **What would break or need a decision on InfinityFree:**
  - **No SSH:** `migrate --force`, `route:trans:cache`, `yaqot:create-admin`, `storage:link`
    and `view:cache` cannot be run. Plan: run `composer install --no-dev -o` locally and upload
    `vendor/`; `route:trans:cache` and `view:cache` could be run locally and uploaded in
    `bootstrap/cache/` and `storage/framework/views`, but compiled views and cached routes may embed
    absolute paths of the machine that built them _(not verified: test it)_; the uncached app works,
    just slower; import the database as SQL (phpMyAdmin) from a local `migrate` + a real admin row
    (create the admin locally and export it; the password hash travels with the dump).
  - **`modules_statuses.json`** is committed and must be uploaded: without it no module is enabled
    (nwidart's file activator) and every route 404s.
  - **`storage:link` / the `public/storage` junction:** `php artisan storage:link` needs SSH, and
    symlinks are probably not available on the host _(confirm)_. Options: copy `storage/app/public` into `public/storage` and set the `public`
    disk's root/url to it (a config change, then uploads go straight to `public/storage`), or
    serve images through a route. Decide in Phase 6; **no config was changed now.**
  - **Document root is `htdocs`** (stated by the owner): the site cannot point at `public/`.
    Either put the Laravel app one level up, outside `htdocs`, if the account allows files above
    it _(confirm: free accounts often do not)_, or upload the project into `htdocs/` with the
    contents of `public/` at its root and a root `.htaccess` that blocks `app/`, `config/`,
    `storage/`, `vendor/`, `.env` and `modules_statuses.json`, plus an edited `index.php`
    (`__DIR__` paths). Secrets in `.env` must never be web-reachable.
  - **Upload sizes:** a full 12-image gallery needs `post_max_size` ≥ 64M and
    `upload_max_filesize` ≥ 8M (§9), and the owner's host states a 10 MB per-file limit.
    The request limit of the host's PHP is unknown _(confirm)_: if it is near 10 MB, expect the
    admin's "too large" message with 2 or more 5 MB photos. The local XAMPP has
    `post_max_size=40M`, so this was never exercised against the host. Mitigations:
    add the gallery in several saves, or resize photos to ~1–2 MB before uploading (the server
    re-encodes to WebP ≤ 1600 px anyway).
  - **Database:** MySQL only (no SQLite file); `SESSION_DRIVER`, `CACHE_STORE` and
    `QUEUE_CONNECTION` are `database` in `.env.example`: fine, but keep `LOG_CHANNEL=daily`/`single`
    in a writable `storage/logs`.
  - **PHP version:** the project needs PHP 8.2+ with `gd` and `pdo_mysql`; check what the host offers _(confirm)_.
  - **Anti-bot interstitial** _(confirm)_: free hosts like this one are known to serve a JavaScript
    challenge page to some clients (crawlers, previews); check the sitemap and social previews
    after launch.

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
| 4c | Gold colours and stone types are owner data (one `product_options` table, `Modules/Option`), not config; `products.type` jewelry/bar/coin; /catalog and taxonomy pages list jewelry only, /bullion lists bars and coins; order 4c → 5b → 5+6 | CONVENTIONS §6, §8; brief §9, §11 |
| 3b | Gallery saved with the form (order tokens, staged removal, primary = first, max 12); phone = landline or mobile (national form), WhatsApp = mobile (`201…`); settings translations filled in both languages or neither; damaged images rejected by decoding; per-process fake disk in tests | CONVENTIONS §6, §9 |

---

## 11. Status

_Updated at the end of Phase 5b, 2026-11-02 (committed on the owner's instruction). Phases 5 + 6 start in a new session._

**Phase 5b (implemented and approved 2026-11-02, committed as "Phase 5b"; `git log --grep "Phase 5b"`):**
- Homepage hero is a three-slide Bootstrap crossfade carousel (ring, necklace, bangles: fixed images in
  `public/assets/images/hero/`, PNG sources kept, transparent WebP 1x/2x beside them; shared silk background
  as a `<picture>` with 2k, 4k (≥2200px only) and portrait-phone files). Settings per slide: title, subtitle,
  button label (ar + en, both or none), link (https or a site path), "show" switch; fallback texts in the lang
  files. The old `hero_*` values moved into slide 1 by migration `2026_11_02_200001` (the `hero_image` setting
  is no longer registered; its row and file stay). Transparent header over the hero on the homepage only.
  Owner revision: the hero's text entrance and crossfade run 30% faster through new tokens
  (`--yq-dur-hero` etc.); autoplay stays 7s, the float stays slow.
- Brands strip is now an endless CSS-only marquee (two equal halves, copies `aria-hidden`, paused only on
  hover or keyboard focus, static scrollable row under reduced motion). No pause button by the owner's
  decision: **if the Phase 5 accessibility audit flags WCAG 2.2.2 for it, report it there.**
- Conventions: CONVENTIONS "Motion" (tokens, hero ambient motion, marquee) and "Homepage hero slider".
- The migration ran on the local MySQL after a backup (`D:\backups\yaqot\yaqot_jewelry_pre-5b_20261102_160112.sql`);
  that database had no settings rows, so there were no old hero values to move.
- Tests: 1237 passed; Pint clean; `view:cache` passes. New REVIEW strings are in COPY_REVIEW (hero rows 4a–4e).
- **Next:** Phases 5 + 6 together, in a new session.

**Phase 4d (implemented 2026-11-02, approved and committed as "Phase 4d"; `git log --grep "Phase 4d"`):**
- Part A (Modules/Auth) and Part B (new Modules/Analytics, plus Setting, Page, Home dashboard,
  Product front show) as described in the Phase 4d section; conventions in CONVENTIONS §1, §7, §8.
- Review: no high findings. One confirmed (a password change did not end the admin's other
  sessions) fixed with `EndStaleAdminSession` (web middleware, ordered before `auth:admin`).
- Migrations `2026_11_03_100001` (admins.is_active, last_login_at) and `100002`
  (product_daily_stats) ran on the local MySQL after a backup. The compiled-view cache must be
  cleared on deploy (`php artisan view:clear`).
- Tests: 1204 passed; Pint clean; `view:cache` passes. New REVIEW strings: COPY_REVIEW #87–100.
- **Next:** Phase 5b (hero), then Phases 5 + 6 together.

**Phase 4c (implemented 2026-11-01, committed 2026-11-02 as "Phase 4c"; `git log --grep "Phase 4c"`):**
- **Design choice:** gold colours and stone types share every column and rule, so they are
  **one table**, `product_options` (`group`, unique `slug`, `is_active`, `sort_order`) plus
  `product_option_translations` (name ar/en, both required), instead of two tables with
  duplicated code. New module `Modules/Option` (admin only; listed in `modules_statuses.json`).
  Slugs are unique across both groups; the migration keeps the old values as slugs, so old
  `?gold_color=rose` URLs still work. `docs/CONVENTIONS.md` §6 and §8 document the pattern.
- **Migrations (all reversible, tested on SQLite by `OptionMigrationTest`):**
  `2026_11_02_100001` creates the option tables; `100002` adds `type`, `gold_color_id`,
  `stone_type_id` (FKs `restrictOnDelete`, indexed); `100003` moves the data (the legacy lists and
  their ar/en labels are embedded in the migration; a stored value outside the lists still gets an
  option; trashed products are linked too); `100004` drops the old string columns.
  `down()` restores the strings from the option slugs (an option the owner added later has no old
  value: slugs over 20 characters become null; a slug that had to be prefixed because it clashed
  with the other group is restored prefixed).
- **Admin:** Gold colours and Stone types (list, search, status filter, sort, toggle, create/edit
  with ar/en names, guarded delete: any product including trashed ones blocks it, deactivate
  instead). Product form: type select; gold colour and stones are jewelry-only (hidden and
  disabled for bars/coins by `admin.js`, `prohibited` in the request, cleared by the controller);
  an inactive option a product already uses stays selectable and is marked "(inactive)"; products
  list: type filter and a type label under the SKU.
- **Public:** `/bullion` (type, weight range, karat, manufacturer = brand, search, sorts with weight
  first, the gold-price note, empty states); `/catalog`, category, collection and brand pages are
  jewelry only; `onSite()` now means "visible jewelry", `onBullion()` is the bullion version; the nav
  item; product page by type (type row instead of gold colour/stones, brand linked to
  `/bullion?brand=`, related = other bars/coins, no categories/collections for bars/coins); cards
  show the type for bars/coins; homepage bullion section (hidden when empty; featured/latest are
  jewelry only); demo seeder adds 4 bars + 3 coins (`DEMO-BAR-`/`DEMO-COIN-`, one draft) and can add
  them to an already-seeded catalog.
- **Review (read-only subagent):** no high or medium findings. Two low findings fixed: the data
  migration's option lookup is now scoped to its group (a clashing slug is prefixed), and the
  type toggle no longer clears the chosen colour/stones when hiding them. Found by the lead:
  an agent edit had commented out `home::home.sections.about` in both languages (fixed);
  `LangParityTest` (same keys in ar and en, no key hidden behind a trailing comment) now guards this.
- **Test change to note:** the product page "no prices" regex used to reject «جنيه» (the currency);
  it now allows «جنيهات» (the nav) and «جنيه ذهب» (the gold coin).
- Tests: **1020 passed (5987 assertions)** (848 before); Pint clean; `view:cache` passes.
  Screenshots (scratch SQLite, demo data): the admin gold colours / stone types / create form /
  product forms (jewelry with an inactive stone, bar) / products filtered by type, `/bullion`
  (plain and filtered), the product page (jewelry and bar), the catalog with the colour filter and
  the home page, in en and ar at 1440/768/390/360, plus the filter offcanvas at 390/360: no
  horizontal overflow, no table scroll, no console errors, correct `lang`/`dir`.
- **Local MySQL:** reset and reseeded on 2026-11-02; see "Local MySQL, Phase 4c" below.
- **Open for the owner:** review the new strings (COPY_REVIEW #66–86) and the Arabic names of the
  migrated options (admin → Gold colours / Stone types).
- **Next:** Phase 5b (hero), then Phases 5 + 6 together.

**Phases done**

| Phase | Commit |
|---|---|
| Baseline (fresh Laravel 12 + ui, translatable, intervention) | `e570e03` |
| 0 — Repository audit and conventions survey | — (no code changes) |
| 1 — Foundation: modules, localization, static assets, admin auth | `d37f5fe` |
| 1b — Official logo, jQuery admin JS, `/ar` and `/en` prefixed locales | `e209a71` |
| 1c — Bilingual admin, 72px header logo, motion system | `8749109` |
| Handoff — this brief | `5168c65` |
| 2 — Data layer: modules Product, Category, Collection, Brand, Branch, Setting; migrations, models, Settings service, factories, demo seeder | `eb8850a` |
| 3a — Shared admin building blocks + Categories CRUD | `0846140` |
| 3b — Products, Collections, Brands, Branches, Settings, Dashboard | the "Phase 3b" commit (`git log --grep "Phase 3b"`) |
| 4a — Public building blocks, home, catalog, footer | `e3f5207` |
| 4b — Product, category, collection, brand, About, Contact, Privacy pages | the "Phase 4b" commit (`git log --grep "Phase 4b"`) |
| 4c — Owner-managed gold colours and stone types, gold bars and coins, InfinityFree check | the "Phase 4c" commit (`git log --grep "Phase 4c"`) |
| 4d — Admin users management, Google Analytics (consent-gated), in-house product insights | the "Phase 4d" commit (`git log --grep "Phase 4d"`) |
| 5b — Hero slider (3 slides), admin-editable slide text, brands marquee | the "Phase 5b" commit (`git log --grep "Phase 5b"`) |
| 5 — Quality: cleanup, social icons, accessibility, SEO, security headers, error pages, performance | `050bbd0` (one commit with Phase 6) |
| 6 — Release for InfinityFree: layout, storage, builder, database export, docs/DEPLOY.md | `050bbd0` |

**Phases 5 and 6 (done, commit `050bbd0`: 1304 tests pass on PHP 8.2 and on 8.4.27, no 8.4 deprecations; details in CONVENTIONS "SEO, security, errors and release" and docs/DEPLOY.md):**
- History rewritten once to drop AI attribution trailers (trees, authors and dates unchanged); the hashes
  in the table above are the new ones. `backup/pre-attribution-cleanup` keeps the old commits until the owner deletes it.
- Cleanup: unused lang keys, the hero PNG sources moved to `docs/design/hero-sources/`, the stock README replaced,
  leftover agent files and 8 orphaned uploads removed. Not removed (kept on purpose, ask): the stock `User` model and
  factory (the tests sign in with it), the documented but unused `.yq-rule` pattern.
- Open items closed: the gallery uses `aria-pressed` only; taxonomy pagination drops the own-filter parameter; the
  logo glint declares both `-webkit-mask` and `mask` (not run in Firefox/Safari: owner check); the "dead strip" under
  the scrolled header does not exist (content scrolls flush under the bar, clicks in the gap reach it).
- Lighthouse (mobile / desktop, local Apache): home 67→83 / 97→99, catalog 75→93 / 98→100, product 74→94 / 98→100,
  bullion 75→94 / 98→100; SEO 83–92→100. axe: 0 violations on every public and admin page, both languages.
- Corrections to the hosting facts above: InfinityFree's own page gives a **20 MB** PHP upload limit (older posts say
  10 MB), 10 kB for `.htaccess`, 1 MB for HTML/PHP/JS files; PHP is fixed at 8.4 on free hosting; cron jobs exist
  (2022 announcement) but are not relied on; the `htdocs` layout and the no-symlink storage are solved in docs/DEPLOY.md.

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

**Local MySQL, Phase 4c (reset on 2026-11-02, at the owner's request):** the four `2026_11_02_*`
migrations had appeared in `yaqot_jewelry` (batch 3, 2026-11-01 about 20:09:44 local) **before the
lead's backup, from an unidentified source** (the test suite was shown not to reach MySQL; the lead's
own migrate runs used scratch SQLite). The database held 24 products that were **not** the `DEMO-`
seed (real-style names and SKUs such as «Classic Solitaire Ring» / `LZ-RG-18-1021`, brands L'azurde,
Karmina and King Ball, 15 settings, 1 admin), which the owner treated as demo-only. Reset steps:
mysqldump (`yaqot_jewelry_before_reset.sql`, scratchpad), a copy of `storage/app/public` (35 files),
the 24 `products/{id}/` folders deleted, `migrate:fresh`, then `DemoCatalogSeeder`. Result: 24 jewelry
(6 per gold colour; stones 4–5 each), 4 bars, 3 coins, 4 gold colours + 5 stone types, 3 brands, 4
categories, 3 collections, 2 branches; 58 image rows = 58 files; settings and admins are empty (the
owner recreates the admin with `yaqot:create-admin`). Eight old category/collection/settings image
files remain on disk as orphans (not part of the requested deletion). The earlier content (and its
images) can be restored from the dump and the copy.

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
