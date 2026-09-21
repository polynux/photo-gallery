<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;

class UserChangeEmail extends Command
{
    protected $signature = 'user:change-email {email : Current email of the user} {new_email? : New email (omit to be prompted)}';

    protected $description = 'Change a user email address';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User with email ' . $this->argument('email') . ' not found.');

            return self::FAILURE;
        }

        $newEmail = $this->argument('new_email') ?? $this->ask('New email');

        $validated = validator(
            ['email' => $newEmail],
            ['email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)]],
        );

        if ($validated->fails()) {
            $this->error($validated->errors()->first());

            return self::FAILURE;
        }

        $user->update([
            'email' => $validated->valid()['email'],
        ]);

        $this->info("Email updated for user ID {$user->id}: {$newEmail}");

        return self::SUCCESS;
    }
}
