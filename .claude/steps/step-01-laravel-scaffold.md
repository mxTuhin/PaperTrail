# Step 01 — Laravel Scaffold & Route Structure

## Goal
Replace the default Laravel welcome page with the two-page architecture (`/` landing + `/app` tool) and wire up all routes, including the `/track` counter and `/admin` panel.

## What to Build

### Routes (`routes/web.php`)
```php
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/app', [AppController::class, 'index'])->name('app');
Route::post('/track', [TrackController::class, 'store'])->name('track');
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('auth.basic')
    ->name('admin');
```

### Controllers to Create
```bash
php artisan make:controller LandingController --no-interaction
php artisan make:controller AppController --no-interaction
php artisan make:controller TrackController --no-interaction
php artisan make:controller AdminController --no-interaction
```

### Blade Views to Create
- `resources/views/landing/index.blade.php`
- `resources/views/app/index.blade.php`
- `resources/views/admin/index.blade.php`
- `resources/views/layouts/app.blade.php` (shared layout)
- `resources/views/layouts/print.blade.php` (print-only layout)

### Layouts
The shared `layouts/app.blade.php` should:
- Load `@vite(['resources/css/app.css', 'resources/js/app.js'])`
- Include CDN links for Alpine.js, SheetJS, SortableJS
- Have `@yield('content')` / `@stack('scripts')` slots
- Include `<meta name="csrf-token">` for AJAX

## Migration: UsageEvent Table
```bash
php artisan make:migration create_usage_events_table --no-interaction
```

Fields:
```php
$table->id();
$table->string('event'); // 'upload' | 'process' | 'print'
$table->string('ip_address', 45)->nullable();
$table->string('user_agent')->nullable();
$table->integer('row_count')->nullable();
$table->integer('col_count')->nullable();
$table->string('filename_hash')->nullable(); // md5 of filename only, not contents
$table->timestamps();
```

## Model
```bash
php artisan make:model UsageEvent --no-interaction
```

## Checklist
- [ ] `routes/web.php` updated with all 4 routes
- [ ] `LandingController` created and returns `landing.index` view
- [ ] `AppController` created and returns `app.index` view
- [ ] `TrackController` created (store method — see Step 06)
- [ ] `AdminController` created (index method — see Step 06)
- [ ] Migration for `usage_events` created
- [ ] `UsageEvent` model created
- [ ] Shared layout `layouts/app.blade.php` created
- [ ] CDN scripts (Alpine.js, SheetJS, SortableJS) in layout head
- [ ] `php artisan migrate` run
- [ ] `vendor/bin/pint --dirty` run

## Conventions
- All controllers in `App\Http\Controllers`
- Named routes everywhere
- No auth required for `/`, `/app`, or `/track`
- `/admin` uses HTTP Basic Auth (Laravel `auth.basic` middleware)
