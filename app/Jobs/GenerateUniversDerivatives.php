<?php

namespace App\Jobs;

use App\Models\Univers;
use App\Services\UniversImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateUniversDerivatives implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    public array $backoff = [30, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(public Univers $univers) {}

    /**
     * Execute the job.
     */
    public function handle(UniversImageService $images): void
    {
        $images->generate($this->univers);
    }

    public function failed(Throwable $exception): void
    {
        $this->univers->forceFill(['processing_status' => 'failed'])->saveQuietly();
        report($exception);
    }
}
