<?php

namespace App\Console\Commands;

use App\Jobs\GenerateUniversDerivatives;
use App\Models\Univers;
use Illuminate\Console\Command;

class ProcessUniversImages extends Command
{
    protected $signature = 'univers:process-images
        {--all : Process every Univers image}
        {--reprocess : Regenerate existing derivatives}
        {univers?* : Specific Univers IDs to process}';

    protected $description = 'Queue Univers image derivative processing';

    public function handle(): int
    {
        $query = Univers::query();

        if (! $this->option('all') && ! $this->option('reprocess')) {
            $query->whereIn('processing_status', ['unprocessed', 'failed', 'partially_processed']);
        }

        if ($this->argument('univers')) {
            $query->whereKey($this->argument('univers'));
        }

        $count = 0;
        $query->eachById(function (Univers $univers) use (&$count): void {
            $univers->forceFill(['processing_status' => 'queued'])->saveQuietly();
            GenerateUniversDerivatives::dispatch($univers);
            $count++;
        });

        $this->info("Queued {$count} Univers image(s).");

        return self::SUCCESS;
    }
}
