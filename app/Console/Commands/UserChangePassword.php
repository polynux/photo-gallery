<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserChangePassword extends Command
{
    protected $signature = 'user:change-password {email? : Email of the user} {--password= : New password (omit to be prompted)}';

    protected $description = 'Change a user password';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email of the user');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?? $this->secret('New password');

        $validated = validator(
            ['password' => $password],
            ['password' => ['required', 'string', Password::defaults()]],
        );

        if ($validated->fails()) {
            $this->error($validated->errors()->first());

            return self::FAILURE;
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        $this->info("Password updated for {$user->email}.");

        return self::SUCCESS;
    }
}
