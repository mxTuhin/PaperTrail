# Step 07 — Backend: Usage Counter & Admin Panel

## Goal
Build the only server-side feature: the `POST /track` endpoint that logs usage metadata to SQLite, and the `GET /admin` panel that shows totals and a recent event log.

## Architecture Summary
```
Client JS  ──POST /track──►  TrackController  ──►  UsageEvent (Eloquent)  ──►  SQLite
Admin      ──GET /admin──►   AdminController   ◄──  UsageEvent::stats()
```

## Migration (`database/migrations/xxxx_create_usage_events_table.php`)

Create with:
```bash
php artisan make:migration create_usage_events_table --no-interaction
```

```php
Schema::create('usage_events', function (Blueprint $table): void {
    $table->id();
    $table->string('event', 20);        // 'upload' | 'process' | 'print'
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 512)->nullable();
    $table->unsignedInteger('row_count')->nullable();
    $table->unsignedInteger('col_count')->nullable();
    $table->string('filename_hash', 64)->nullable(); // md5 of original filename only
    $table->timestamps();
});
```

## Model (`app/Models/UsageEvent.php`)

```bash
php artisan make:model UsageEvent --no-interaction
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event', 'ip_address', 'user_agent', 'row_count', 'col_count', 'filename_hash'])]
class UsageEvent extends Model
{
    /**
     * Get usage totals grouped by event type.
     *
     * @return array<string, int>
     */
    public static function totals(): array
    {
        return static::selectRaw('event, count(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();
    }
}
```

## TrackController (`app/Http/Controllers/TrackController.php`)

```bash
php artisan make:controller TrackController --no-interaction
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\UsageEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * Store a usage event from the client.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event'         => ['required', 'in:upload,process,print'],
            'row_count'     => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'col_count'     => ['nullable', 'integer', 'min:0', 'max:500'],
            'filename_hash' => ['nullable', 'string', 'max:64'],
        ]);

        UsageEvent::create([
            ...$validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(null, 204);
    }
}
```

## AdminController (`app/Http/Controllers/AdminController.php`)

```bash
php artisan make:controller AdminController --no-interaction
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\UsageEvent;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display the usage dashboard.
     */
    public function index(): View
    {
        $totals = UsageEvent::totals();
        $recent = UsageEvent::latest()->limit(50)->get();

        return view('admin.index', compact('totals', 'recent'));
    }
}
```

## Routes (`routes/web.php`)

```php
use App\Http\Controllers\TrackController;
use App\Http\Controllers\AdminController;

Route::post('/track', [TrackController::class, 'store'])->name('track');

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('auth.basic')
    ->name('admin');
```

## Admin View (`resources/views/admin/index.blade.php`)

Minimal but functional — a clean table of recent events:

```html
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-8">PaperTrail — Usage Dashboard</h1>

    <!-- Totals -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        @foreach(['upload', 'process', 'print'] as $eventType)
            <div class="bg-white rounded-lg p-4 border border-[--border] text-center">
                <div class="text-3xl font-bold text-[--accent]">{{ $totals[$eventType] ?? 0 }}</div>
                <div class="text-sm text-[--ink-2] uppercase tracking-wide mt-1">{{ ucfirst($eventType) }}s</div>
            </div>
        @endforeach
    </div>

    <!-- Recent events -->
    <div class="bg-white rounded-lg border border-[--border] overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[--surface-2]">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Time</th>
                    <th class="text-right px-4 py-3">Rows</th>
                    <th class="text-right px-4 py-3">Cols</th>
                    <th class="text-left px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent as $event)
                    <tr class="border-t border-[--border]">
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $event->event === 'print' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $event->event }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-[--ink-2]">{{ $event->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-2 text-right">{{ $event->row_count ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $event->col_count ?? '—' }}</td>
                        <td class="px-4 py-2 text-[--ink-muted] font-mono text-xs">{{ $event->ip_address }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
```

## Client-Side `trackEvent()` Helper

```js
/**
 * Fire a usage event to the server (non-blocking, best-effort).
 * @param {'upload'|'process'|'print'} event
 * @param {{ rows?: number, cols?: number, filenameHash?: string }} [meta]
 */
async function trackEvent(event, meta = {}) {
    try {
        await fetch('/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                event,
                row_count:     meta.rows     ?? null,
                col_count:     meta.cols     ?? null,
                filename_hash: meta.filename ? md5(meta.filename) : null,
            }),
        });
    } catch {
        // Silently fail — tracking is non-critical
    }
}
```

> Note: `filename_hash` is an md5 of the *filename string only* (not contents). You can use a tiny md5 library or just `crypto.subtle.digest` — or simply skip the hash.

## Basic Auth Setup

The `/admin` route uses Laravel's built-in `auth.basic` middleware. Set credentials in `.env`:
```
ADMIN_USER=admin
ADMIN_PASS=your-secure-password
```

And in `config/auth.php` or via a custom middleware that reads from `.env` — or just use `auth.basic` which reads from the `users` table. For a simple tool, creating one admin User record via tinker is sufficient:

```bash
php artisan tinker --execute 'App\Models\User::create(["name"=>"Admin","email"=>"admin@papertrail.test","password"=>bcrypt("yourpassword")]);'
```

## Checklist
- [ ] Migration `create_usage_events_table` created and run
- [ ] `UsageEvent` model with `#[Fillable]` attributes
- [ ] `UsageEvent::totals()` static method
- [ ] `TrackController@store` with validation (never logs file contents)
- [ ] `AdminController@index` with totals + recent 50 events
- [ ] Route for `POST /track` (CSRF protected, no auth needed)
- [ ] Route for `GET /admin` (auth.basic middleware)
- [ ] `admin.index` view with stat cards + event table
- [ ] `trackEvent()` JS helper function in shared layout
- [ ] Called on: file parse (`upload`), type detection done (`process`), print click (`print`)
- [ ] `vendor/bin/pint --dirty` run on all PHP files
- [ ] Feature test: `POST /track` stores event (Pest)
- [ ] Feature test: `GET /admin` requires auth (Pest)
