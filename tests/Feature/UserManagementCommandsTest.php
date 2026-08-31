<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user list shows all users', function () {
    $users = User::factory()->count(2)->create();

    $this->artisan('user:list')
        ->expectsTable(
            ['ID', 'Name', 'Email', 'Created at'],
            $users->map(fn (User $user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->created_at->format('Y-m-d H:i'),
            ])->all(),
        )
        ->assertExitCode(0);
});

test('user list filters users by name or email', function () {
    $matching = User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    $this->artisan('user:list', ['--filter' => 'alice'])
        ->expectsOutputToContain($matching->email)
        ->assertExitCode(0);
});

test('user change password updates the user password', function () {
    $user = User::factory()->create();

    $this->artisan('user:change-password', [
        'email' => $user->email,
        '--password' => 'new-secure-password-123',
    ])->assertExitCode(0);

    expect(Hash::check('new-secure-password-123', $user->refresh()->password))->toBeTrue();
});

test('user change password validates password strength', function () {
    $user = User::factory()->create();

    $this->artisan('user:change-password', [
        'email' => $user->email,
        '--password' => 'abc',
    ])->assertExitCode(1);

    expect(Hash::check($user->password, $user->refresh()->password))->toBeFalse();
});

test('user change password fails for unknown user', function () {
    $this->artisan('user:change-password', [
        'email' => 'nobody@example.com',
        '--password' => 'new-secure-password-123',
    ])->assertExitCode(1);
});

test('user change email updates the user email', function () {
    $user = User::factory()->create();

    $this->artisan('user:change-email', [
        'email' => $user->email,
        'new_email' => 'new@example.com',
    ])->assertExitCode(0);

    expect($user->refresh()->email)->toBe('new@example.com');
});

test('user change email rejects an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->artisan('user:change-email', [
        'email' => $user->email,
        'new_email' => 'taken@example.com',
    ])->assertExitCode(1);

    expect($user->refresh()->email)->not->toBe('taken@example.com');
});

test('user change email fails for unknown user', function () {
    $this->artisan('user:change-email', [
        'email' => 'nobody@example.com',
        'new_email' => 'new@example.com',
    ])->assertExitCode(1);
});
