# Step 10 — Testing Strategy

## Goal
Write the Pest tests that cover server-side behavior. The client-side logic is tested manually (print output, type detection accuracy). Server tests focus on the two PHP endpoints.

## Test Setup

All tests use Pest v4 with the Laravel plugin. Pest is already configured in `tests/Pest.php`.

```bash
# Create tests
php artisan make:test TrackEventTest --pest --no-interaction
php artisan make:test AdminPanelTest --pest --no-interaction
php artisan make:test UsageEventTest --pest --unit --no-interaction
```

## Feature Test: `TrackEventTest`

`tests/Feature/TrackEventTest.php`

```php
<?php

use App\Models\UsageEvent;

it('stores an upload event', function (): void {
    $response = $this->postJson('/track', [
        'event'     => 'upload',
        'row_count' => 150,
        'col_count' => 8,
    ]);

    $response->assertStatus(204);

    expect(UsageEvent::where('event', 'upload')->count())->toBe(1);
    expect(UsageEvent::first())
        ->row_count->toBe(150)
        ->col_count->toBe(8);
});

it('stores a process event', function (): void {
    $this->postJson('/track', ['event' => 'process'])
        ->assertStatus(204);

    expect(UsageEvent::where('event', 'process')->exists())->toBeTrue();
});

it('stores a print event', function (): void {
    $this->postJson('/track', ['event' => 'print'])
        ->assertStatus(204);

    expect(UsageEvent::where('event', 'print')->exists())->toBeTrue();
});

it('rejects an invalid event type', function (): void {
    $this->postJson('/track', ['event' => 'delete'])
        ->assertStatus(422);
});

it('rejects missing event field', function (): void {
    $this->postJson('/track', [])
        ->assertStatus(422);
});

it('accepts null row_count and col_count', function (): void {
    $this->postJson('/track', ['event' => 'upload'])
        ->assertStatus(204);

    expect(UsageEvent::first())
        ->row_count->toBeNull()
        ->col_count->toBeNull();
});

it('never stores file contents', function (): void {
    $this->postJson('/track', [
        'event'    => 'upload',
        'contents' => 'CONFIDENTIAL_DATA', // extra field, should be ignored
    ])->assertStatus(204);

    // Only whitelisted fields stored
    $event = UsageEvent::first();
    expect($event->getAttributes())->not->toHaveKey('contents');
});

it('records ip address and user agent', function (): void {
    $this->postJson('/track', ['event' => 'print'])
        ->assertStatus(204);

    expect(UsageEvent::first()->ip_address)->not->toBeNull();
});
```

## Feature Test: `AdminPanelTest`

`tests/Feature/AdminPanelTest.php`

```php
<?php

use App\Models\User;
use App\Models\UsageEvent;

it('requires authentication to view admin panel', function (): void {
    $this->get('/admin')
        ->assertStatus(401);
});

it('allows authenticated user to view admin panel', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(200)
        ->assertViewIs('admin.index');
});

it('shows correct event totals', function (): void {
    UsageEvent::create(['event' => 'upload', 'ip_address' => '127.0.0.1']);
    UsageEvent::create(['event' => 'upload', 'ip_address' => '127.0.0.1']);
    UsageEvent::create(['event' => 'print',  'ip_address' => '127.0.0.1']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertViewHas('totals', function (array $totals): bool {
            return ($totals['upload'] ?? 0) === 2
                && ($totals['print'] ?? 0) === 1;
        });
});

it('shows recent events limited to 50', function (): void {
    UsageEvent::factory()->count(60)->create(['event' => 'upload']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertViewHas('recent', function ($recent): bool {
            return $recent->count() === 50;
        });
});
```

## Unit Test: `UsageEventTest`

`tests/Unit/UsageEventTest.php`

```php
<?php

use App\Models\UsageEvent;

it('calculates correct totals', function (): void {
    UsageEvent::create(['event' => 'upload',  'ip_address' => '127.0.0.1']);
    UsageEvent::create(['event' => 'upload',  'ip_address' => '127.0.0.1']);
    UsageEvent::create(['event' => 'process', 'ip_address' => '127.0.0.1']);
    UsageEvent::create(['event' => 'print',   'ip_address' => '127.0.0.1']);

    $totals = UsageEvent::totals();

    expect($totals['upload'])->toBe(2)
        ->and($totals['process'])->toBe(1)
        ->and($totals['print'])->toBe(1);
});
```

## UsageEvent Factory

```bash
php artisan make:factory UsageEventFactory --model=UsageEvent --no-interaction
```

`database/factories/UsageEventFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\UsageEvent>
 */
class UsageEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event'      => fake()->randomElement(['upload', 'process', 'print']),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'row_count'  => fake()->optional()->numberBetween(1, 10000),
            'col_count'  => fake()->optional()->numberBetween(1, 50),
        ];
    }
}
```

## Running Tests

```bash
# All tests
php artisan test --compact

# Specific test
php artisan test --compact --filter=TrackEventTest

# With coverage (if configured)
php artisan test --compact --coverage
```

## Checklist
- [ ] `TrackEventTest` — 8 test cases covering all validation and storage scenarios
- [ ] `AdminPanelTest` — 4 test cases covering auth and view data
- [ ] `UsageEventTest` — unit test for `totals()` method
- [ ] `UsageEventFactory` created for test seeding
- [ ] All tests pass: `php artisan test --compact`
- [ ] No test deletes others' data (use `RefreshDatabase` or transactions)
- [ ] `vendor/bin/pint --dirty` on all test files
