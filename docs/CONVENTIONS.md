# Yaqot — Project Conventions

These rules come from the Phase 0 survey of the owner's reference projects in
`D:\xampp\htdocs` (mainly `sni-website` and `dentists-hmvc`), adjusted where
those projects had weak practices. They were approved before Phase 1 and are
the single source of truth: follow this file rather than re-surveying the
reference projects.

---

## 1. Stack

| Concern | Choice |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Modules | `nwidart/laravel-modules` ^12 |
| Localization (URLs) | `mcamara/laravel-localization` ^2.3 |
| Translatable models | `astrotomic/laravel-translatable` ^11 |
| Slugs | `cviebrock/eloquent-sluggable` (from Phase 2) |
| Images | `intervention/image` ^3 (GD driver) |
| Admin auth | `laravel/ui` traits only (`AuthenticatesUsers`); no scaffolded views |
| Frontend | Blade + Bootstrap 5.3 dist files + jQuery 3.7.1 (self-hosted) |
| Admin theme | Soft UI Dashboard 3 v1.1.0 (free, MIT) with its RTL stylesheet |
| Tests | PHPUnit 11 |

**Not used:** npm, Vite, Sass, Tailwind, Livewire, Inertia, Filament,
SPA frameworks, roles/permissions packages.

New Composer packages need the owner's approval unless they already appear in a
reference project. Third-party CSS/JS/fonts are committed as files (see §8).

---

## 2. Modules (D1)

One module per business area, generated with `php artisan module:make Name`.
The generator config (`config/modules.php`) matches `sni-website`:
`app_folder` is empty, so code sits directly in the module folder, and the
unused stubs (package.json, vite.config.js, assets, sample views, api routes,
.gitkeep files) are not generated.

```
Modules/{Name}/
├── Console/                     artisan commands (register in the provider's $commands)
├── Http/
│   ├── Controllers/
│   │   ├── Dashboard/           admin controllers
│   │   └── Front/               public controllers
│   └── Requests/                Form Requests
├── Models/                      Eloquent models (+ {Model}Translation)
├── Providers/                   {Name}ServiceProvider, RouteServiceProvider, EventServiceProvider
├── config/config.php
├── database/
│   ├── factories/               namespace Modules\{Name}\Database\Factories
│   ├── migrations/
│   └── seeders/
├── lang/{ar,en}/{file}.php      used as __('{alias}::{file}.key')
├── resources/views/
│   ├── dashboard/...            view('{alias}::dashboard.x')
│   └── front/...                view('{alias}::front.x')
├── routes/web.php
├── composer.json                merged by wikimedia/composer-merge-plugin
└── module.json
```

- Planned modules: `Auth`, `Home`, `Product` (products + images), `Category`,
  `Collection`, `Brand`, `Branch`, `Setting`, `Page` (about, contact, privacy, sitemap).
- Module service providers extend `App\Providers\ModuleServiceProvider`, which
  registers module translations **under the module namespace**. nwidart v12
  would otherwise merge them into the global files, so a module's `auth.php`
  would overwrite Laravel's.
- After generating a module, remove anything unused: empty resource
  controllers, the generated `{Name}Controller.php`, unused providers.
- Shared, non-module code stays in `app/`: base providers, middleware,
  view composers, `app/Helpers/helper.php` (autoloaded functions).
- Shared layouts and components live in `resources/views/layouts/{front,admin}`
  and `resources/views/components`.
- Tests live in the root `tests/` folder, grouped by module
  (`tests/Feature/{Area}/...`, `tests/Unit/...`).

---

## 3. Routing & localization (D2)

- Both languages are always prefixed: `/en/...` (English, LTR) and `/ar/...`
  (Arabic, RTL). **English is the default locale** (`APP_LOCALE=en`).
- `config/laravellocalization.php`: `supportedLocales` = ar, en;
  `hideDefaultLocaleInURL = false`; `useAcceptLanguageHeader = false` (URLs are
  stable and shareable, with no redirect based on the browser language);
  `localesOrder = [ar, en]`.
- Any GET without a locale prefix (`/`, `/catalog?q=x`) redirects to the same
  path and query under `/en` (`App\Http\Middleware\RedirectToLocalizedUrl`,
  alias `localeRedirect`). This is deterministic: there is no session, cookie or
  browser-language guessing. mcamara's `LocaleSessionRedirect` is deliberately
  not used, because it would send `/` to whichever language the visitor last used.
- Every public page outputs `<link rel="alternate" hreflang>` for `ar` and `en`,
  plus `x-default` pointing to the English URL (`hreflangLinks` from `LayoutComposer`).
- Public routes in every module use the same group:

```php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
});
```

- **Admin routes are not localized:** prefix `/admin`, names `admin.*`,
  middleware `['admin.locale', 'auth:admin']`. The dashboard is always Arabic RTL:
  `SetAdminLocale` sets `ar` explicitly, independent of `APP_LOCALE`. Layout
  direction follows the **application** locale (`app()->getLocale()`), not
  mcamara's URL locale.

```php
Route::prefix('admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{product}/edit', 'edit')->name('edit');
        Route::put('/{product}', 'update')->name('update');
        Route::delete('/{product}', 'destroy')->name('destroy');
    });
});
```

- Route names: `front.{page}` / `front.{resources}.{action}`, and
  `admin.{resources}.{action}`. Always link with `route()`.
- Menus come from `config/navigation.php`. An entry appears only when its route
  exists, so new modules show up automatically.
- Language switcher: `LaravelLocalization::getLocalizedURL($code)`, computed
  in `App\View\Composers\LayoutComposer`.
- Tests: mcamara chooses the route prefix when the app boots. Call
  `$this->refreshApplicationWithLocale('ar')` or `('en')` (in `Tests\TestCase`)
  before requesting `/ar/...` or `/en/...`. Without it, the app boots
  unprefixed, which is what the redirect tests for `/` use. `phpunit.xml`
  pins `APP_LOCALE=en`.
- Deployment: cache routes with `php artisan route:trans:cache`, never plain
  `route:cache` or `optimize`, which would make every `/ar` and `/en` URL return
  404. `AppServiceProvider` uses mcamara's `LoadsTranslatedCachedRoutes` so the
  per-locale cache files are loaded. Clear them with `route:trans:clear`.
- Trusted hosts: `bootstrap/app.php` calls `$middleware->trustHosts()`. Outside
  local and testing, only `APP_URL`'s host is accepted, so `APP_URL` must be
  the real production URL.
- Local smoke servers: `php artisan serve` does **not** pass environment
  variables such as `DB_CONNECTION` to the server it spawns, and reads `.env`
  instead. To run against another database, start
  `php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`
  from `public/`, with the variables exported.

---

## 4. Translations

- Every user-facing string goes in lang files; there is no hardcoded text in Blade or JS.
  - Shared layout strings: `lang/{ar,en}/front.php` and `lang/{ar,en}/admin.php`.
  - Module strings: `Modules/{Name}/lang/{ar,en}/{file}.php`, used as `__('{alias}::{file}.key')`.
  - Keys are `snake_case` and grouped by section (`hero.title`, `nav.home`).
  - JS strings are passed in a JSON `<script>` from the layout composer.
- `lang/ar/{validation,auth,pagination,passwords}.php` are full Arabic
  translations of Laravel's files.
- Copy that the owner must approve is marked `// REVIEW:` in the lang file.

### Translatable models (D3)

- Astrotomic: the model uses `Translatable`, `$translatedAttributes`, and a
  `{Model}Translation` model in the same namespace.
- `config/translatable.php`: `locales = [ar, en]`, `fallback_locale = en`,
  `use_fallback = true`, `use_property_fallback = true`. Both languages are
  required in admin forms, so the fallback is only a safety net for legacy or
  imported rows.
- **Form field names are `{field}_{locale}`** (`name_ar`, `name_en`,
  `description_ar`, ...) in every module. Form Requests build the rules in a loop:

```php
foreach (config('translatable.locales') as $locale) {
    $rules["name_$locale"] = ['required', 'string', 'max:255'];
}
```

- Controllers map them onto translations inside a transaction:

```php
DB::transaction(function () use ($request, $product) {
    $product->fill($request->safe()->except([...translated fields...]))->save();

    foreach (config('translatable.locales') as $locale) {
        $translation = $product->translateOrNew($locale);
        $translation->name = $request->validated("name_$locale");
        $translation->save();
    }
});
```

- **Arabic and English are both required** for every translatable field the
  public site displays (name, branch address, ...). Neither language's pages
  silently show the other language. Long optional texts (descriptions) are
  `nullable` in both. Admin forms show the two locales as tabs (Arabic first),
  and a validation error switches to the tab that contains it.
- Slugs, SKU, karat, weight, flags and sort orders are **not** translatable.

---

## 5. Data layer

### Migrations (D9)

- Anonymous migration classes, one table per migration, file names
  `create_{table}_table`.
- Tables are plural `snake_case`. Pivots use singular names in alphabetical
  order (`category_product`).
- Foreign keys use `foreignId('x_id')->constrained()->cascadeOnDelete()`, or
  `nullOnDelete()` / `restrictOnDelete()` as the data policy requires.
- Translation tables (`{singular}_translations`):

```php
$table->id();
$table->foreignId('product_id')->constrained()->cascadeOnDelete();
$table->string('locale', 5)->index();
$table->string('name');
$table->text('description')->nullable();
$table->unique(['product_id', 'locale']);
// no timestamps on translation tables
```

- Use real types: `decimal(8,3)` for weights, `boolean` flags, `timestamp`
  dates, and `unsignedInteger('sort_order')->default(0)`.
- Index the real query patterns: flags plus sort order, foreign keys, and pivot keys.
- Never edit a migration that may have run. Add a new one instead.
  Never run `migrate:fresh`, `migrate:reset` or `db:wipe` without approval.

### Models

- Explicit `$fillable`; never `$guarded = []`, and never `$request->all()`.
- `casts()` for booleans, decimals and dates.
- Reusable query rules are scopes (`scopeVisible`, `scopeActive`, `scopeOrdered`).
- `Model::preventLazyLoading()` is on outside production. Eager-load
  translations and relations.

### Slugs (D4)

- `cviebrock/eloquent-sluggable`. The source is the English name, falling back
  to the SKU (or another stable non-empty field) when the English name is
  missing. A slug is **never empty**.
- `onUpdate = false`: slugs never change after creation, so public URLs stay
  stable. Both locales share the same slug.

### Images (D7)

The `ImageTrait` pattern from the reference projects, with these fixes:

- Store on the `public` disk. The DB stores the path relative to the disk
  (e.g. `products/{id}/{uuid}.webp`).
- File names are server-generated (UUID). The client file name is never used.
- Uploads larger than ~1600px on the long edge are scaled down with
  intervention/image v3 (`ImageManager` + GD).
- Delete files through `Storage::disk('public')->delete()` (never `@unlink`),
  and only the files that belong to the record.
- Deleting an image row deletes its file. Force-deleting a product deletes all
  its image files, through a model event, not only the FK cascade.
- Validate as `image`, `mimes:jpg,jpeg,png,webp`, `max:5120`, plus dimension limits.

### Settings

- A key–value `settings` table, read through a small cached service and
  invalidated on save.
- Translatable values are stored per locale.
- No secrets in settings.

---

## 6. Controllers, requests, responses (D6)

- Controllers are thin and resource-like (`index`, `create`, `store`, `edit`,
  `update`, `destroy`). Logic that is reused or complex goes in a small class
  (service or helper) inside the module.
- **One `{Entity}Request` per entity** for store and update. Use
  `$this->isMethod('post')` for create-only rules, `Rule::unique(...)->ignore($model)`
  on update, `exists:` on every foreign key, and an `attributes()` method with
  translated labels.
- `authorize()` returns `true`, because the `auth:admin` middleware already
  restricts every admin route.
- **Admin forms are submitted with AJAX** (`<form class="ajax-form">`, handled by
  `public/assets/admin/js/admin.js`):
  - Validation failures use Laravel's standard **422 JSON**
    (`{message, errors}`). Do **not** override `failedValidation()`.
    admin.js shows **every** message under its field and switches to the tab
    containing the first error.
  - Success returns `response()->json(['message' => __('admin.messages.saved'), 'redirect' => route(...)])`.
  - **No `try { } catch (\Throwable)`** that hides errors. Unexpected exceptions
    go to Laravel's handler, which logs them. admin.js shows a generic
    translated message, and with `APP_DEBUG=false` nothing internal leaks.
  - Multi-table writes use `DB::transaction()`.
- Deletes are a normal form POST from the shared modal (`#delete-modal`,
  `data-url`) and redirect back with `->with('success', __('admin.messages.deleted'))`.
  The layout shows the flash as a toast.

---

## 7. Admin authentication (D5)

- Separate `admins` table, model `Modules\Auth\Models\Admin`, guard `admin`
  (session) with provider `admins`.
- Login and logout use `AuthenticatesUsers` (laravel/ui): throttled to
  5 attempts per minute, session regenerated, `/admin` after login.
- No registration and no password reset routes. `Auth::routes()` is not used.
- Admins are created **only** with `php artisan yaqot:create-admin`, which
  prompts for name, email and password (with confirmation, min. 8 characters).
- No roles or permissions for now: every row in `admins` is a full admin.

---

## 8. Frontend & assets

- Everything is static under `public/assets/`, with no build step:

```
public/assets/
├── vendor/bootstrap-5.3.8/{css/bootstrap.min.css, css/bootstrap.rtl.min.css, js/bootstrap.bundle.min.js, LICENSE}
├── vendor/jquery-3.7.1/{jquery.min.js, LICENSE.txt}
├── fonts/{family}/*.woff2 + OFL.txt
├── images/brand/logo.png    official logo: the ONLY brand source file
├── images/brand/generated/  output of yaqot:brand-assets (committed)
├── css/fonts.css            @font-face for "Yaqot Sans" and "Yaqot Serif"
├── css/front.css            public site
├── js/                      public scripts (jQuery allowed)
└── admin/
    ├── vendor/soft-ui-dashboard-1.1.0/{css, js, LICENSE}
    ├── css/admin.css        Yaqot skin over Soft UI
    └── js/admin.js          AJAX forms, delete modal, toasts
```

- Vendor folders carry their **version in the name**. To upgrade, add a new
  folder, switch the references, and delete the old one.
- Link assets with `versioned_asset('assets/...')`, which appends `?v=filemtime`.
- Arabic pages load `bootstrap.rtl.min.css` and English pages `bootstrap.min.css`.
  The admin loads `soft-ui-dashboard-rtl.min.css`. `<html lang dir>` is set per locale.
- **One custom stylesheet serves both directions:** use logical properties
  (`margin-inline-start`, `inset-inline-end`, `padding-block`, ...) and
  Bootstrap's `ms-*` / `me-*` / `text-start` utilities. Never write `left` or `right`.
- Restyle Bootstrap through its CSS variables (`--bs-*`) and component variables
  (`--bs-btn-*`). Custom classes use the `yq-` prefix (BEM-ish:
  `yq-header__inner`, `yq-logo--lg`).
- Public site: dark theme (`data-bs-theme="dark"`) with the tokens in
  `front.css` §1. Gold text on dark, dark text on gold buttons. Contrast is
  checked to WCAG AA or better.
- Fonts (OFL, self-hosted):
  - "Yaqot Sans" = IBM Plex Sans Arabic + IBM Plex Sans (body)
  - "Yaqot Serif" = Amiri + Cormorant Garamond (headings)

  They are split by `unicode-range`, so one family works in both directions.
- Never letter-space or uppercase Arabic text: scope tracking and
  `text-transform` to `:lang(en)`.
- Icons are inline SVG through `<x-icon name="..." />`, decorative by default.
  Mirror directional icons with `flip-rtl`.
- Brand assets:
  - `public/assets/images/brand/logo.png` is the single source: 1254×1254,
    transparent background, gold calligraphy.
  - **To replace the logo:**
    1. Overwrite `logo.png`.
    2. Run `php artisan yaqot:brand-assets`.
    3. Commit `public/assets/images/brand/generated/*` and `public/favicon.ico`.

    Deploys do not run the command. `AssetPathsTest` fails while the
    generated files are stale, because `manifest.json` records the source's sha1.
  - The command trims transparent padding and writes WebP logos at 1x/2x for
    `Logo::FILE_HEIGHTS`. It never upscales. It also writes `favicon-16/32.png`
    and `apple-touch-icon.png` (the calligraphy mark without "JEWELRY",
    centred on #101010), `/favicon.ico` (16/32/48), and `manifest.json`
    with every file's dimensions.
  - `<x-logo size="header|offcanvas|sidebar|footer|login" />`
    (`App\View\Components\Logo`) outputs `<img src srcset width height alt>`.
    Display heights are in `Logo::SIZES`. Only below-the-fold placements
    (the footer) are `loading="lazy"`.
  - `--yq-gold` is the median colour of the logo's opaque pixels (#D2A249).
    `--yq-gold-light` is for hover and emphasis. `--yq-gold-deep` (#7A5E2A)
    is gold used as text on light surfaces, at 4.5:1 or better. Re-check
    contrast if the logo changes.
- JS uses **jQuery 3.7.1** (self-hosted, loaded before Bootstrap and the page
  scripts), like the reference projects. Bootstrap 5's own JS API
  (`bootstrap.Modal`, `bootstrap.Toast`, `bootstrap.Tab`) does not need jQuery
  and is used as is.
  - Set `$.ajaxSetup` headers (CSRF, `Accept: application/json`) once, before
    any request.
  - Insert server strings with `.text()`, never `.html()`.
  - No inline handlers (`onclick`): attach behaviour from the asset files with
    delegated `$(document).on(...)`.
  - Load jQuery on public pages only when a public script needs it.
- Respect `prefers-reduced-motion`. Every interactive element has a visible
  `:focus-visible` style.

### Blade

- Layouts: `layouts.front.master` and `layouts.admin.master` (with partials).
  Pages `@extends` them and use `@section('title')` and `@section('content')`.
- Layout data (direction, menus, language links, JS messages) comes from
  `LayoutComposer`. **No queries or `@php` blocks in Blade.**
- Escape everything with `{{ }}`. Never use `{!! !!}` on content entered by
  users or admins. Long text is stored as plain text and rendered as paragraphs.
- Use `@json(...)` on a single line (multi-line arguments break the Blade compiler).

---

## 9. Testing

- PHPUnit feature tests with `RefreshDatabase` and factories; in-memory SQLite (`phpunit.xml`).
- Every phase adds tests for its behaviour, and the full suite runs after each phase.
  Never weaken or delete a test to make the suite pass.
- Public pages are tested in both locales (`refreshApplicationWithLocale('ar')` / `('en')`).
- `tests/Feature/Assets/AssetPathsTest.php` checks that every asset referenced by
  a page or stylesheet exists. Extend its page list when new layouts appear.
- Tests use `UploadedFile::fake()` and `Storage::fake('public')` for files.

---

## 10. Things we deliberately do not copy from the reference projects

- A `catch (\Throwable)` that hides errors, or JSON responses that return only the first validation error.
- `env()` calls outside `config/`.
- `$request->all()` and incomplete `$fillable` arrays.
- `unique` rules without `ignore()` on update.
- Translation writes without a transaction.
- Queries and `@php` logic inside Blade; N+1 queries in table rows.
- Client-side DataTables over `->get()`. Use server pagination.
- `{!! !!}` on admin-edited content.
- `@unlink`; leaving gallery files behind on delete.
- Committing `.env`, or an `APP_KEY` in `.env.example`.
- Commented-out blocks of dead code, generator leftovers, and debug scripts in the repo root.
- Hardcoded UI strings.
