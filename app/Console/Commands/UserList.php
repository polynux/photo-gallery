<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserList extends Command
{
    protected $signature = 'user:list {--filter= : Filter users by name or email}';

    protected $description = 'List all users';

    public function handle(): int
    {
        $filter = $this->option('filter');

        $users = User::query()
            ->when($filter, fn ($query) => $query
                ->where('name', 'like', "%{$filter}%")
                ->orWhere('email', 'like', "%{$filter}%"))
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'created_at']);

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Created at'],
            $users->map(fn (User $user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->created_at->format('Y-m-d H:i'),
            ]),
        );

        return self::SUCCESS;
    }
}
