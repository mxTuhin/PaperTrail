<?php

use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a usage event and returns 204', function () {
    $response = $this->postJson('/track', [
        'event' => 'upload',
        'row_count' => 120,
        'col_count' => 6,
    ]);

    $response->assertNoContent();

    $this->assertDatabaseHas('usage_events', [
        'event' => 'upload',
        'row_count' => 120,
        'col_count' => 6,
    ]);
});

it('rejects an invalid event type', function () {
    $this->postJson('/track', ['event' => 'hacking'])
        ->assertStatus(422);

    expect(UsageEvent::count())->toBe(0);
});

it('only persists whitelisted metadata, never file contents', function () {
    $this->postJson('/track', [
        'event' => 'process',
        'contents' => 'secret sales data',
    ])->assertNoContent();

    expect(UsageEvent::first()->getAttributes())->not->toHaveKey('contents');
});

it('blocks the admin dashboard without basic auth', function () {
    $this->get('/admin')->assertStatus(401);
});

it('shows the admin dashboard to an authenticated user', function () {
    $user = User::factory()->create();

    $this->withBasicAuth($user->email, 'password')
        ->get('/admin')
        ->assertOk()
        ->assertSee('Usage Dashboard');
});
