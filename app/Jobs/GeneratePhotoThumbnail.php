<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Services\ThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GeneratePhotoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    public array $backoff = [30, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Photo $photo
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ThumbnailService $thumbnails): void
    {
        $thumbnails->generate($this->photo);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
