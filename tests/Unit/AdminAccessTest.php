<?php

use App\Models\User;
use Filament\Panel;

test('any authenticated user can access the panel', function () {
    $user = User::factory()->make([
        'email' => 'photographer@example.com',
    ]);

    expect($user->canAccessPanel(Mockery::mock(Panel::class)))->toBeTrue();
});
