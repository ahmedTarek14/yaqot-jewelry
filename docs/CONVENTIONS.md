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
| Admin theme | Soft UI Dashboard 3 v1.1.0 (free, MIT): RTL stylesheet for Arabic, LTR for English |
| Tests | PHPUnit 11 |

**Not used:** npm, Vite, Sass, Tailwind, Livewire, Inertia, Filament,
SPA frameworks, roles/permissions packages.

New Composer packages need the owner's approval unless they already appear in a
reference project. Third-party CSS/JS/fonts are committed as files (see §8).

**The one exception (Phase 4d): Google Analytics 4.** Its script cannot be self-hosted, so it is
loaded from `googletagmanager.com`, and only when **all** of these hold: the page is public (never
the admin), the owner has filled the `ga_measurement_id` setting, and the visitor accepted the
cookie notice. With no ID there is no tracking, no notice, no cookie and no third-party request at
all. Nothing else may load third-party scripts, fonts or embeds. Everything else about analytics
(product page views, WhatsApp clicks) is in-house; see §8 "Analytics".

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

- **The admin is localized like the reference projects:** `/ar/admin/...` (RTL,
  `soft-ui-dashboard-rtl.min.css`) and `/en/admin/...` (LTR,
  `soft-ui-dashboard.min.css`). Route names stay `admin.*`, and every admin
  group uses `LaravelLocalization::setLocale().'/admin'` as its prefix with
  `admin.locale` (`App\Http\Middleware\AdminLocale`), which runs before `auth`:
  - On a localized admin URL it remembers the locale in the session
    (`admin_locale`).
  - An unprefixed `GET /admin/...` redirects to that remembered locale,
    defaulting to **ar**, and keeps the query string.
  - Unprefixed non-GET requests are rejected.
  - Guests are redirected to `route('admin.login')`, which is in the current
    locale. Login and logout also stay in the current locale.
  - The navbar and the login page have a language switcher (`$languageSwitch`)
    that keeps the same admin page. "View site" opens the public site in the
    same language (`$adminSiteUrl`).
  - Layout direction follows the application locale (`app()->getLocale()`).

```php
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
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
- Language switcher (`$languageSwitch`, from `App\View\Composers\LayoutComposer`):
  - It links to the same page in the other locale:
    `getLocalizedURL($code, request()->url())` plus the raw `QUERY_STRING`, so
    parameters keep their order and repeated keys survive.
  - Always pass the URL explicitly: LaravelLocalization keeps the request it was
    built with.
  - The composer rebuilds its data for every request.
  - After login, the intended URL is moved into the locale the admin logged in
    with (`AuthController::authenticated()`).
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
  from `public/`, with the variables exported. To keep uploads, sessions and
  compiled views out of the real `storage/`, point `LARAVEL_STORAGE_PATH` to a
  scratch folder **from the scratch router script**:
  `$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = '<scratch>/storage';`.
  Exporting it is not enough: the `php -S` web SAPI does not copy the process
  environment into `$_ENV`/`$_SERVER`, which is where Laravel reads it (the DB
  variables still work because Laravel's env reader falls back to `getenv()`).
  The router also serves `/storage/*` from the scratch folder before requiring
  `server.php`, since `public/storage` is a junction to the real files.

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

- Controllers map them onto translations inside a transaction, then save **once**.
  Astrotomic writes the translations in the same `save()`. Set the translations
  before that first save, because the slug is fixed at creation from the English
  name: creating a sluggable model without translations throws a `LogicException`.

```php
DB::transaction(function () use ($request, $product) {
    $product->fill($request->safe()->except([...translated fields...]));

    foreach (config('translatable.locales') as $locale) {
        $product->translateOrNew($locale)->name = $request->validated("name_$locale");
    }

    $product->save();
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
- Delete through model instances (`$product->forceDelete()`, `$category->delete()`),
  never query builders (`Product::query()->forceDelete()`): the file cleanup and
  the "in use" guards are model events. The category and collection pivot
  foreign keys are `restrictOnDelete` as a backstop. A brand's is too.
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
  `data-url`, `data-name`) and redirect back with `->with('success', __('admin.messages.deleted'))`.
  The layout shows the flash as a toast.
- A guarded delete (category, collection, brand) throws `ModelInUseException`
  with the product and trashed-product counts. The exception renders itself:
  a redirect back with an `error` flash (409 JSON when JSON is expected), and it
  is never logged. Controllers do not catch it.

### Admin building blocks (Phase 3a)

Every admin resource is built from these; `Modules/Category` is the reference
implementation. Do not add new patterns; extend these instead.

| Need | Use |
|---|---|
| Index query params | `App\Support\AdminListing::fromRequest($request, $sortable, $defaultSort, $dir, $filters)`: allowlisted `sort`/`dir`, filters, `q` (trimmed, 100 chars). Search with `whereRaw("name like ? escape '!'", [$listing->likePattern()])`. Sort translated names with `orderByTranslation()`, other columns through a `SORTABLE` column map. `paginate(config('catalog.admin.per_page'))->withQueryString()`; a page past the end redirects to the last page. |
| Index view | `<x-admin.card-header>`, `<x-admin.listing-toolbar>` + `<x-admin.filter-select>`, `<x-admin.sort-header>`, `<x-admin.name-cell>` (both locales; without `url` the name is plain text, e.g. a trashed row), `<x-admin.thumbnail>`, `<x-admin.status-toggle>` (text badge), `<x-admin.row-actions>` (`confirm` replaces the delete question, e.g. "Move … to the trash?"), `<x-admin.empty-state>` (`listing` optional), `.yq-pagination`. Hide secondary columns with `d-none d-md-table-cell`; `.yq-table__thumb` hides below 576px. Table cells wrap, each switch's badge stacks under it below 1400px, and the action buttons show their text only from 1400px, so the actions column stays in view. **Check every index at 360, 576, 768, 992, 1200, 1366, 1440 and 1920px**: overflow inside `.table-responsive` hides the actions without any page-level overflow. At most two switch columns; anything more goes behind `d-xxl-table-cell`. |
| Form view | `<form class="ajax-form" novalidate enctype="multipart/form-data">`, `<x-admin.form-section>`, `<x-admin.translatable-fields :model :fields>` (ar/en tabs), `<x-admin.field>`, `<x-admin.switch>` (hidden 0 + checkbox 1), `<x-admin.image-input>`, `<x-admin.form-actions>` (sticky). One `form.blade.php` serves create and edit. |
| Form Request | `use App\Http\Requests\Concerns\TranslatedFields`: `translatedRules([...])` and `translatedAttributes([...])` give `name_ar` the label "Name (Arabic)". |
| Images | `ImageUploader::rules('categories')` for validation, with the upload type (also the storage folder) that sets the minimum size: `config('catalog.images.min_pixels')`, products 800px, categories/collections 200px (the hero has no upload since Phase 5b). The rules end with `App\Rules\DecodableImage`, which really decodes the file, so a damaged image (valid header, broken data) is a 422 under its field, never a 500. Pass the same type to `<x-admin.image-input type="...">` so its hint matches; `$images->storeWhile($file, 'dir', fn ($path) => DB::transaction(...))` stores a UUID `.webp` (≤ `max_edge` px, EXIF stripped) and removes it again if the write throws. Old files go through `HasPublicImage` after commit. Limits: `config('catalog.images')`. |
| Switches on the index | `PATCH /{model}/{flag}` returning `$this->toggledResponse($value, $onLabel, $offLabel)` (base `Controller`). |
| Save response | `$this->savedResponse(route('admin.x.index'))`. |
| `updated_at` | Astrotomic saves only the translations when no column changed, and `sync()` or image rows never touch the parent. After `save()`, inside the transaction: `$model->wasRecentlyCreated \|\| $model->wasChanged() \|\| $model->touch();` so "recently updated" lists and `updated_at` sorts see every edit. |
| Large requests | A request above PHP's `post_max_size` is a 413; admin.js shows `admin.messages.too_large` (nothing saved). Deployment needs `post_max_size` ≥ 64M and `upload_max_filesize` ≥ 8M for a full 12-image gallery (brief §9, Phase 6). |
| Egyptian mobile numbers | `App\Support\EgyptianPhone::normalizeMobile()` (→ `201XXXXXXXXX` or null) and the `App\Rules\EgyptianMobile` rule, **for WhatsApp numbers only**. Store the normalized value. |
| Egyptian phone numbers | `phone` fields (branches, settings) accept a landline, a mobile **or** a 5-digit hotline starting with 1 (19xxx, 16xxx): `App\Rules\EgyptianPhoneNumber`, then store `EgyptianPhone::normalizePhone()` (national form, `0223456789` / `01012345678`, or the hotline's 5 digits). Hotlines are never WhatsApp numbers. Area codes and lengths: `EgyptianPhone::LANDLINE_LENGTHS`. |
| Select (one value) | `<x-admin.select name :label :options :value placeholder="...">`; `placeholder` adds an empty first option (e.g. "None"). |
| Several related records | `<x-admin.multiselect name="categories" :label :options="[id => name]" :selected="[ids]">`: a checkbox list with a jQuery search filter and a live count; read it with `$request->validated('categories', [])`. Errors for `categories` and `categories.N` show under the group. |
| Several images | `<x-admin.image-gallery type="products" :images="$product->images">`: drag or arrow-button reorder, mark-for-removal with undo, new uploads with previews; first image = primary; nothing changes until the form is saved. It submits `images[]` (new files), `images_order[]` (`id:{id}` / `new:{n}` tokens, the final order) and `remove_images[]`. Store with `$images->storeDuring(fn (Closure $store) => DB::transaction(...))`: `$store($file, "products/{$product->id}")` works once the id exists, and every file stored that way is removed if the write throws. Limit: `config('catalog.images.max_per_product')`. |
| Group errors | Any wrapper with `data-field-errors="base other_base"` receives the 422 messages for those names and their `.N` keys (admin.js). |
| Soft-deleted rows | `<x-admin.restore-button :url :name>` (a PATCH form) inside `<x-admin.row-actions>`; the controller redirects back with `__('admin.messages.restored')`. "Delete permanently" on trashed rows only: `<x-admin.row-actions :permanent="true" :delete-url :confirm>` with a `DELETE /{model}/force` route (`withTrashed()`, 404 unless trashed) that calls `forceDelete()` on the model inside a transaction, so file cleanup runs after commit. Read-only status: `<x-admin.badge :on :label>`. |
| Translated fields without a model | `<x-admin.translatable-fields :model="null" :values="['key_ar' => ..., 'key_en' => ...]" :fields>` (Settings). |
| Dashboard figures | `<x-admin.stat-card :label :value icon :url link-label>`. |
| Owner-managed product options (Phase 4c) | `Modules/Option`: gold colours and stone types are rows of **one** table, `product_options` (`group` = `gold_color` \| `stone_type`, unique `slug`, `is_active`, `sort_order`, translated `name`), because both share every column and rule. Admin: `admin.gold-colors.*` and `admin.stone-types.*`, one abstract `OptionController` with a tiny subclass per group, shared views, `{option}` resolved with `inGroup(group)->findOrFail` (an id from the other group is a 404). Built like Brands: guarded delete (`HasProductsGuard`; `ProductOption::products()` picks gold_color_id/stone_type_id from the instance's `group`, so never `withCount('products')` on a query-level model), the index counts with a subquery on the group's own column. Products point at them with `gold_color_id` / `stone_type_id` (nullable, `restrictOnDelete`). The old config lists were removed; karats stay in `config/catalog.php`. |
| Inactive options | Public: only active options are filterable (`CatalogOptions::attributes()`) and shown on the product page. Admin product form: `ProductController::optionChoices()` lists the active ones plus the one the product already has, marked "(inactive)"; `ProductRequest::optionRules()` accepts an active option **or the product's current one**, so re-saving never fails on it. |
| Product type | `products.type` = jewelry \| bar \| coin (`config('catalog.types')`; `catalog.bullion` = bar, coin). Scopes `Product::jewelry()` / `bullion()`. Gold colour and stones are jewelry-only: the form's type select carries `data-jewelry-toggle="jewelry"` and every `[data-jewelry-only]` field is hidden and disabled by `admin.js` for other types; the request marks both `prohibited` for them and the controller clears the columns. |

Toasts: success toasts hide after 4s; a flashed `error` stays until closed.

---

## 7. Admin authentication (D5)

- Separate `admins` table, model `Modules\Auth\Models\Admin`, guard `admin`
  (session) with provider `admins`.
- Login and logout use `AuthenticatesUsers` (laravel/ui): throttled to
  5 attempts per minute, session regenerated, and a redirect to the dashboard
  in the current locale after login.
- No registration and no password reset routes (mail may not work on the host).
  `Auth::routes()` is not used.
- **Admin users (Phase 4d)** are managed in the dashboard (`admin.admins.*`: list, add, edit,
  activate/deactivate, delete) and each admin edits their own name, email and password under
  `admin.account.*` ("My account"). One admin can set a new password for another on the edit
  form. `php artisan yaqot:create-admin` still works (first admin, or recovery from a shell).
- No roles or permissions: every row in `admins` is a full admin.
- **Password policy:** `Password::defaults()` (set in `AppServiceProvider`): min. 10 characters,
  letters, mixed case, a digit and a symbol. Not `uncompromised()`, which would call a third-party
  service. Use `Password::defaults()` in every password rule.
- **Safeguards (server-side):** an admin cannot delete or deactivate themselves; the last active
  admin can never be deleted or deactivated (checked inside a transaction, also through the edit
  form). `admins.is_active = false` blocks login, and the custom user provider
  (`Modules\Auth\Support\ActiveAdminProvider`, driver `active-admin` in `config/auth.php`) resolves
  active admins only, so an open session or remember cookie of a deactivated admin stops working on
  the next request. `last_login_at` is recorded on login. Login throttling (5 per minute) is unchanged.

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
    ├── css/admin.css        Yaqot skin over Soft UI (+ subtle motion tokens, see "Motion")
    └── js/admin.js          AJAX forms, delete modal, toasts
```

- Vendor folders carry their **version in the name**. To upgrade, add a new
  folder, switch the references, and delete the old one.
- Link assets with `versioned_asset('assets/...')`, which appends `?v=filemtime`.
- Arabic pages load `bootstrap.rtl.min.css` and English pages `bootstrap.min.css`.
  The admin loads `soft-ui-dashboard-rtl.min.css` (Arabic) or
  `soft-ui-dashboard.min.css` (English). `<html lang dir>` is set per locale.
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
    centred on #111111), `/favicon.ico` (16/32/48), and `manifest.json`
    with every file's dimensions.
  - `<x-logo size="header|offcanvas|sidebar|footer|login" />`
    (`App\View\Components\Logo`) outputs `<img src srcset width height alt>`.
    Display heights are in `Logo::SIZES`. Only below-the-fold placements
    (the footer) are `loading="lazy"`.
  - `--yq-gold` (#D4AF66) is warmed from the logo's sampled median.
    `--yq-gold-light` is for hover and emphasis. `--yq-gold-deep` (#7B653B)
    is gold used as text on light surfaces, at 4.5:1 or better. Re-check
    contrast if the logo or palette changes.
- JS uses **jQuery 3.7.1** (self-hosted, loaded before Bootstrap and the page
  scripts), like the reference projects. Bootstrap 5's own JS API
  (`bootstrap.Modal`, `bootstrap.Toast`, `bootstrap.Tab`) does not need jQuery
  and is used as is.
  - Set `$.ajaxSetup` headers (CSRF, `Accept: application/json`) once, before
    any request.
  - Insert server strings with `.text()`, never `.html()`.
  - No inline handlers (`onclick`): attach behaviour from the asset files with
    delegated `$(document).on(...)`.
  - jQuery is loaded on public pages (deferred) for `front.js`.
- Respect `prefers-reduced-motion`. Every interactive element has a visible
  `:focus-visible` style.

### Motion

Elegant and calm, never flashy. **Use these patterns; do not invent new
timings or effects.** The implementation lives in `front.css` §7 (the API
comment block) and §8 (reduced motion), and in `public/assets/js/front.js`.

**Rules**
- Animate only `transform` and `opacity`. Hover states may also transition
  paint-only properties (colour, background, border colour, box-shadow) with
  the tokens. Never animate layout properties (width, height, margin, padding,
  top/left).
- **Tokens only.** `front.css` §1 defines the full set. `admin.css` defines only
  `--yq-dur-fast`, `--yq-dur-base` and `--yq-ease-out`, with the same values:

  | Token | Value |
  |---|---|
  | `--yq-dur-fast` | 150ms |
  | `--yq-dur-base` | 250ms |
  | `--yq-dur-slow` | 700ms |
  | `--yq-dur-glint` | 1500ms |
  | `--yq-ease-out` | `cubic-bezier(.2,.7,.2,1)` |
  | `--yq-ease-in-out` | `cubic-bezier(.65,0,.35,1)` |
  | `--yq-stagger` | 90ms |
  | `--yq-enter-delay` / `--yq-enter-step` | 120ms / 140ms |
  | `--yq-reveal-distance` | 16px |
  | `--yq-dur-hero` / `--yq-hero-enter-delay` / `--yq-hero-enter-step` | 490ms / 84ms / 98ms: 70% of slow / enter-delay / enter-step. `.yq-hero` re-points `--yq-dur-slow`, `--yq-enter-delay` and `--yq-enter-step` at them, so the hero's text entrance, jewelry entrance and slide crossfade are 30% faster than the site's reveals (Phase 5b revision) |
  | `--yq-dur-marquee-item` | 4s per brand name sliding past (~45px/s), so the marquee's speed is constant whatever the number of names |
  | `--yq-dur-float` / `--yq-float-distance` / `--yq-float-tilt` | 8s / 12px / 1deg (hero jewelry float, Phase 5b) |
  | `--yq-dur-light` / `--yq-dur-particle` | 24s / 14s (hero light drift; default particle rise, each particle sets its own) |
  | `--yq-hero-deep` (+ `-rgb`) | #080808, the one new colour: the near-black the hero photos fade into, and the scrims over them |

  Horizontal motion multiplies by `--yq-dir`, which is 1, or -1 under `[dir=rtl]`.
- **Progressive enhancement:**
  - Every "hidden until revealed" state is scoped under `html.js`, which the
    inline script in `layouts/front/master.blade.php` adds.
  - `front.js` sets `window.yqMotionReady`. If that hasn't happened after 3s
    (script blocked or failed), the layout removes `js`, so content is never
    stuck hidden.
  - Without JS, everything is visible.
- **Reduced motion:** `prefers-reduced-motion: reduce` shows everything
  immediately and disables the glint, reveals, stagger, hero sequence, image
  zoom and header transition. `front.js` also checks `matchMedia`.
- **Checks:** no layout shift, no horizontal overflow at 360px in `/ar` and `/en`,
  and directional motion follows the reading direction (logical properties;
  `[dir=rtl]` overrides where CSS has no logical form, e.g. `transform-origin`).
- **Admin:** subtle only. Cards fade in, modals and toasts use the token
  durations, and buttons get hover states. No scroll reveals and no decorative
  motion.

**Patterns (public site)**

| Pattern | Markup | Behaviour |
|---|---|---|
| Scroll reveal | `<div data-reveal>` (`data-reveal="fade"` = no movement) | Fades in and rises 16px when in view (IntersectionObserver), then `.is-revealed`; observed once. |
| Staggered group | `<ul data-reveal-group><li data-reveal>…` | Item delay = index × `--yq-stagger`, where index counts the group's items that come into view **together** (Phase 4a), so a lower row of a 24-card grid starts at once instead of waiting ~2s behind the rows above it. Use for product cards and category tiles. |
| Load sequence | `<div data-enter-group><p data-enter>…` | CSS only, on load. Delay = enter-delay + n × enter-step, for up to 8 children. Used by the hero. |
| Marquee (brands strip) | `<div class="yq-marquee" style="--yq-marquee-items: N"><div class="yq-marquee__track">` + **two equal** `.yq-marquee__half` (the second `aria-hidden="true"`), each holding the list repeated until it is wider than any screen (`HomeController::MARQUEE_MIN_ITEMS` = 24 names per half). Only the first `<ul class="yq-marquee__list">` is in the accessibility tree; every copy is `aria-hidden` and its links `tabindex="-1"` | CSS only: the track slides by one half (`translateX(calc(var(--yq-dir) * -50%))`, toward the inline start, the other way in RTL), at `--yq-dur-marquee-item` × N, linear, endless. Paused only while hovered or while a link has keyboard focus (`:has(a:focus-visible)`); no pause button (owner's decision: if the Phase 5 audit flags WCAG 2.2.2, report it there). Soft edge fade by mask. Reduced motion: no animation, no mask, hidden copies removed, the first list is a horizontally scrollable row. Without JS it works the same (no JS involved). |
| Hero ambient motion (Phase 5b) | `.yq-hero__media` (entrance: fade + 0.96 → 1), `.yq-hero__float` (float 12px / 1° over 8s, alternating), `.yq-hero__light` (drifting gold glow), `.yq-hero__particles > i` ×12 (rising specks; ≤ 4 on phones), `.yq-hero__scroll-mouse span` (wheel) | CSS only, under `html.js`, transform/opacity. The slide's text uses the normal load sequence (`data-enter`); inactive carousel items are `display:none`, so entrance and float restart each time a slide is shown. `.is-paused` on `.yq-hero` (the visitor's pause button) pauses the ambient motion. None of it runs under reduced motion (particles stay hidden). |
| Ornament draw-in | `<div class="yq-ornament" aria-hidden="true" data-reveal><span></span></div>` | Lines grow outwards from the diamond (also with `data-enter`). |
| One-sided rule | `<hr class="yq-rule" data-reveal>` | Draws from the inline-start side. |
| Underline link | `<a class="yq-underline">` | Underline grows from inline-start on hover, focus or `aria-current`. Nav links already use it. |
| Buttons | `.btn-gold`, `.btn-outline-gold` | Sweep from inline-start (`::before` `scaleX`). Text stays at 4.5:1 or better throughout. |
| Image frame | `<a class="yq-frame-link"><span class="yq-frame yq-frame--square"><img … width height></span></a>` | Fixed ratio (4/5 by default; `--square`, `--landscape`, or `style="--yq-frame-ratio: 3 / 2"`). The image zooms to 1.04 over `--yq-dur-slow` on hover or focus. Use for product cards and the gallery. |
| Logo glint (signature) | `<a data-glint-trigger><x-logo size="header" glint /></a>` | CSS mask of the logo itself. A soft light sweep in the reading direction, once after load and on hover or focus; never restarted mid-sweep. Header and offcanvas only. Mask images load with CORS: if `ASSET_URL` ever points to a CDN, it must send `Access-Control-Allow-Origin`, otherwise the glint silently disappears. |
| Header | `<header data-header>` | `.is-scrolled` past 24px. The bar and logo scale down (transform) and a backdrop fades in (opacity); layout height stays constant. |
| Offcanvas | `.yq-offcanvas` + `.yq-mobile-nav > li` | Token-timed slide. Items stagger in from the inline-end side while open. |

Phase 4 mapping:
- **Product grid / category tiles:** `data-reveal-group` + `data-reveal` on each card, with `.yq-frame` for the image.
- **Gallery:** `.yq-frame` (no reveal on the main image).
- **Filters (offcanvas):** reuse `.yq-offcanvas`.
- **Section headings:** `.yq-ornament` with `data-reveal`.

### Public building blocks (Phase 4a)

Every public page is built from these; `Modules/Home` and the catalog
(`Modules/Product/.../Front/CatalogController`) are the reference. Extend them
rather than adding new patterns.

| Need | Use |
|---|---|
| A setting in a controller or composer | `setting('key', $default)` (wraps `SettingService::get()`; empty counts as unset). Never in Blade. |
| Contact links | `App\Support\SiteContact`: `whatsappUrl()`, `phone()` / `email()` (`['label', 'url']` or null), `socials()` (https only). Each is null/empty when not configured, and the view hides it. |
| WhatsApp | `App\Support\WhatsApp`: `normalize()` (via `EgyptianPhone::normalizeMobile()`, → `201…`), `link($number, $message)` (`https://wa.me/{n}?text=` + `rawurlencode`), `chatUrl()`, `productUrl($name, $sku, $url)`; null when no valid number is configured, so the CTA is hidden. Messages: `front.whatsapp.*`. |
| Phone links | `EgyptianPhone::telUri()` (`tel:+20…`, hotlines as is); `Branch::phone_url`. |
| Long admin text | `App\Support\PlainText::paragraphs()` → one `<p class="yq-text">{{ }}</p>` each (single line breaks kept by CSS). |
| Listing taxonomies | `Category/Collection/Brand::onSite()`: active and with at least one `Product::visible()` product. |
| Product cards | Query with `Product::visible()->forCard()` (translations, primary image, brand translations); render `<x-front.product-grid :products>` (reveal stagger) / `<x-front.product-card :product>`. Cards show brand (active brands only), name (`<h3>`, so put the grid under an `<h2>`), karat and `weight_label`; never prices. The card links to `front.products.show` (by slug). |
| Category / collection tile | `<x-front.tile :url :title :image ratio="square|landscape">` in a `<ul class="yq-tile-grid" data-reveal-group>`; gold placeholder without an image. |
| Section heading | `<x-front.section-heading id :title :eyebrow :link :link-label>` + `aria-labelledby` on the section. |
| Page header | `.yq-page-header` with `<x-front.breadcrumbs :items>` (Home is added) and `h1.yq-page-header__title`. |
| Filters | `CatalogFilters::fromRequest($request, CatalogOptions::taxonomies($except))` → `apply($query)`; the form is `product::front.partials.filters` inside `<x-front.filter-offcanvas>` (offcanvas below 992px, sidebar above). Every parameter is allowlisted and invalid values are ignored. `form="catalog-filters"` ties the sort select to it; front.js drops empty fields from the URL. Category/collection/brand pages pass `$except` for their own filter. |
| Pagination | `->paginate(config('catalog.front.per_page'))->withQueryString()`, `<x-front.pagination :paginator>`; a page past the end redirects to the last page. |
| Empty state | `<x-front.empty-state :title :text icon>` with optional actions in the slot. |
| Sections | `.yq-section`, `--alt` (charcoal), `--light` (ivory; `.btn-outline-gold` darkens for contrast). Each homepage section renders only when it has data. |

Added in Phase 4b:

| Need | Use |
|---|---|
| Category / collection / brand page | A thin Front controller per module (`firstOrFail` on `Model::onSite()->where('slug', …)`, so inactive, missing or product-less = 404) calling `Modules\Product\Support\TaxonomyPage::show($request, 'category'\|'collection'\|'brand', $record)`. It builds the filters without the page's own filter (`CatalogOptions::taxonomies([$own])`), scopes the products, paginates, redirects a page past the end, and renders `product::front.taxonomy` (header + `product::front.partials.results`, the same results block the catalog uses). |
| Product page | `Product::visible()->where('slug', …)->firstOrFail()` (draft, trashed, no image, a name missing in either locale, unknown slug = 404). Related products: visible, sharing an **active** category, excluding itself, `config('catalog.front.related')`. Brand, categories and collections are shown/linked only when active. Gallery: `product::front.partials.gallery`, `[data-gallery]` in `front.js` §6 (thumbnails are buttons with roving tabindex, arrow keys follow the reading direction, aria-live caption; `html:not(.js)` falls back to `<noscript>` links; the main image has no reveal). WhatsApp CTA: `WhatsApp::productUrl()`, hidden when no number is set. |
| Static pages | `Modules/Page` (`front.about`, `front.contact`, `front.privacy`): text from Settings and active branches only; every block hides when unconfigured, and an empty About/Contact shows `<x-front.empty-state>` with a 200. Privacy copy describes only what the code does and is marked `// REVIEW:`. |
| Links between pages | Always by slug: `route('front.products.show', $product->slug)` (also in the admin "View on site"), `front.categories.show`, `front.collections.show`, `front.brands.show`. There is no collections index page, so the `front.collections.index` nav entry stays hidden. |

Added in Phase 4c:

| Need | Use |
|---|---|
| Jewelry vs bullion | **/catalog and the category, collection and brand pages list jewelry only; /bullion lists bars and coins only.** `CatalogFilters::fromRequest($request, $taxonomies, $attributes, bullion: false\|true)` is the single place that applies the type scope (`apply()` adds `jewelry()` or `bullion()`/the chosen `type`), so every page built on it follows the rule. Anything else that lists products (homepage featured/latest, related products) adds `jewelry()` or `bullion()` itself. |
| Filter profiles | Jewelry: q, category, collection, brand, karat, `gold_color`, `stone_type` (slugs of **active** options), weight range; sorts newest (default), name, weight_asc, weight_desc. Bullion: q, brand (the manufacturer), `type` (bar \| coin), karat, weight range; sorts weight_asc (default), weight_desc, newest, name. `CatalogOptions::attributes($bullion)` supplies the chips (`attribute => [value => label]`); an attribute without choices is left out of the form. `$filters->availableSorts()` / `defaultSort()` replace the old constants. |
| Taxonomy visibility | `onSite()` (category, collection, brand) = active with at least one visible **jewelry** piece; `onBullion()` = active with a visible bar or coin (the bullion page's brand filter). |
| Bullion page | `Front/BullionController` + `product::front.bullion`, built from the results/filters partials (`$emptyLang` overrides the empty-state strings). Shows the gold-price note (REVIEW); no prices, no live rates. A bar's or coin's product page links its brand to `/bullion?brand=slug` (a brand page lists jewelry only), shows its type instead of gold colour/stones, and lists other bars and coins as related. |
| Data move | `2026_11_02_100002…100004`: add `type` and the option FKs, move the data (the legacy lists and their ar/en labels are written out in the migration, so it outlives config and lang), drop the old string columns. Reversible; `OptionMigrationTest` runs it on SQLite. |
| Lang guard | `LangParityTest`: every lang file has the same keys in ar and en, and no key is hidden behind a trailing `// comment` on the same line. |

### Homepage hero slider (Phase 5b)

`Modules/Home` (+ Settings). Three fixed slides, Bootstrap's own carousel (`carousel-fade`), no new library.

| Need | Use |
|---|---|
| Images | Fixed files in `public/assets/images/hero/`: PNG cutouts `ring|necklace|bangles.png` (sources, kept) and their transparent WebP `-1x` / `-2x` (widths in `HeroSlides::IMAGES`, made with a scratch GD script, alpha checked by `AssetPathsTest`). Shared still background: `silk-2k.webp` (default), `silk-4k.webp` (`min-width: 2200px` only), `silk-mobile.webp` (portrait phones), in one decorative `<picture>`. Only slide 1's jewelry is eager (`fetchpriority=high`); the others are `loading=lazy` and `front.js` §7 makes them eager after load. To change a picture, replace the files and the widths. |
| Text | Settings, per slide `n` = 1..3: `hero_{n}_title`, `_subtitle`, `_cta_label` (translatable, both languages or none), `hero_{n}_cta_url` (https or a site path starting with `/`, `Modules\Setting\Rules\SiteLink`; a site path gets the visitor's locale prefix unless it already has one), `hero_{n}_enabled` ('0' = off; unset = on). `Modules\Home\Support\HeroSlides` resolves them; empty text falls back to `home::home.hero.slides.{n}.*` (REVIEW), an empty or unsafe link to the catalog. A disabled slide is skipped; with one enabled slide there are no controls and no autoplay; with none, slide 1 shows with its fallback text. The old `hero_title/subtitle/cta_label/cta_url` rows were renamed into slide 1 by a migration; the `hero_image` setting is no longer registered (row and file stay, unread). |
| Behaviour | `front.js` §7: autoplay 7s, paused by the pause/play button, a mouse over the hero, keyboard focus inside it, a hidden tab, the hero out of view; with `prefers-reduced-motion` it starts paused. Swipe and arrow keys are Bootstrap's (they follow `dir`). Controls (pause button, line + dots, 24px targets) need JS; without it the first slide shows alone. |
| Layout | Fixed height `clamp(36rem, 100svh, 56rem)`, so a slide change never shifts the page. Text at the inline start, jewelry at the inline end from 768px; below that the text first, the jewelry in the bottom 44%, and the controls on the line between them, so the cookie notice (fixed to the bottom) never covers the button or the controls. The Latin eyebrow is `lang="en" dir="ltr"`. |
| Header | `@section('header_class', 'yq-header--overlay')` on the homepage: transparent over the hero (the hero pulls up by one header height), solid once `.is-scrolled`. |

### Analytics (Phase 4d)

`Modules/Analytics` (+ the Settings key and the privacy page). No third parties except the
optional GA4 exception in §1.

| Need | Use |
|---|---|
| Google Analytics | Setting `ga_measurement_id` (`G-XXXXXXX`, validated, empty = off). `<x-front.analytics-consent />` sits in `layouts/front/master.blade.php` (public pages only) and renders nothing when the setting is empty. With an ID it renders a cookie notice (hidden until `analytics.js` reveals it, so no JS = no notice and no tracking). `public/assets/js/analytics.js` loads gtag **only after** the visitor accepts and remembers the choice in the first-party cookie `yq_consent` (`granted` \| `denied`, 180 days). The server HTML never contains the Google script. |
| Privacy policy | The sections list switches with the setting: the Google Analytics section (cookies, Google as processor, consent, withdrawal) appears only when it is enabled. The in-house counters are always described. All of it is `// REVIEW:` copy. |
| Product counters | `product_daily_stats`: one row per product per day (`views`, `whatsapp_clicks`), incremented atomically with a single upsert (`ProductStats`). No IP, user agent, cookie or any personal data is stored. Obvious bots (user-agent list in the module config), HEAD and prefetch requests are skipped. A view is counted only after the product resolved through `Product::visible()`, so 404s, drafts and trashed products never count. |
| WhatsApp CTA | The product page links to `front.products.whatsapp` (`/{locale}/go/whatsapp/{slug}`), which counts the click and redirects (302, `no-store`, `noindex`) to the `wa.me` URL built server-side (`WhatsApp::productUrl()`). It works without JS; the link keeps `target="_blank" rel="noopener"`. 404, and nothing counted, when the product is not visible or no valid number is configured. |
| Dashboard | "Most viewed" / "Most asked on WhatsApp" (top 10, 7 or 30 days, linking to the product edit page) and a 30-day inline-SVG line chart with a text alternative. No chart library. |
| Retention | Rows are kept 13 months. `php artisan yaqot:prune-stats [--months=13]` is scheduled daily (`routes/console.php`), but the host may have no cron: the dashboard's "Delete old statistics" button (`admin.analytics.prune`) does the same, so it is the manual path. |

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
- Public page tests reboot once in `setUp()` with
  `if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) $this->refreshApplicationWithLocale('en');`
  (see `HomePageTest`), and check N+1 by comparing query counts for few and many rows.
- `tests/Feature/Assets/AssetPathsTest.php` checks that every asset referenced by
  a page or stylesheet exists. Extend its page list when new layouts appear.
- Tests use `UploadedFile::fake()` and `Storage::fake('public')` for files.
  `Tests\TestCase::setUp()` fakes the public disk for every test, and
  `refreshApplicationWithLocale()` re-runs the test class's own `setUp()`, so
  nothing a test does can reach the real `storage/app/public`. The fake disk is
  per process (`storage/framework/testing/disks/public_test_{pid}`, removed in
  `tearDown()`), so test runs started at the same time (parallel agents) never
  clean or see each other's files.

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

---

## 11. Subagents, reviewers and the local database

- Subagents and reviewers may run **only `php artisan test` and read-only commands** (reading
  files, `git status/diff/log`, `migrate:status`). They **never** run `migrate`, `migrate:*`,
  `db:seed`, `tinker`, or any artisan command that writes, unless the lead explicitly assigns it
  in the prompt.
- **Only the lead touches the local MySQL** (`yaqot_jewelry`), and only after a `mysqldump`
  backup whose path is reported to the owner. Throwaway checks use a scratch SQLite file and a
  `php -S` server (see §3), never the real database or `storage/app/public`.
- The lead puts this rule in every subagent prompt. (Phase 4c: the four migrations were found
  applied to the local MySQL before the lead's backup, from an unidentified source; the tests were
  proven not to reach MySQL.)
