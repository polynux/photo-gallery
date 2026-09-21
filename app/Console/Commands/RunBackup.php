<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class RunBackup extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Run the borg backup (SQLite snapshot + storage + prune)';

    public function handle(): int
    {
        $process = Process::run('/usr/local/bin/backup');

        if (! $process->successful()) {
            $this->error($process->errorOutput() ?: $process->output());

            return self::FAILURE;
        }

        $this->line($process->output());

        return self::SUCCESS;
    }
}
