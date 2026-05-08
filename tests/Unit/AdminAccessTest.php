<?php

use App\Models\User;
use Filament\Panel;

test('panel access stays open in testing when no admin allowlist is configured', function () {
    config()->set('admin.emails', []);

    $user = User::factory()->make([
        'email' => 'photographer@example.com',
    ]);

    expect($user->canAccessPanel(Mockery::mock(Panel::class)))->toBeTrue();
});

test('panel access is limited to configured admin emails', function () {
    config()->set('admin.emails', ['admin@example.com']);

    $adminUser = User::factory()->make([
        'email' => 'admin@example.com',
    ]);

    $regularUser = User::factory()->make([
        'email' => 'client@example.com',
    ]);

    expect($adminUser->canAccessPanel(Mockery::mock(Panel::class)))->toBeTrue();
    expect($regularUser->canAccessPanel(Mockery::mock(Panel::class)))->toBeFalse();
});
