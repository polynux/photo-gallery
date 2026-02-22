<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Fiber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-thumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $fibers = [];
        $photos = Photo::all();

        $this->info('Generating thumbnails for all photos...');

        $galleryId = 0;
        foreach ($photos as $photo) {
            $thumbnailPath = Storage::disk('private')->path('thumbnails/'.$photo->path);
            if (! file_exists($thumbnailPath)) {
                if ($photo->photo_gallery_id !== $galleryId) {
                    $galleryId = $photo->photo_gallery_id;
                    $this->info("Processing gallery ID: {$galleryId}");
                }
                $fibers[] = new Fiber(function () use ($photo) {
                    $photo->generateThumbnail();
                    $photo->save();
                });
            }
        }

        foreach ($fibers as $fiber) {
            $fiber->start();
        }

        while ($fibers) {
            foreach ($fibers as $index => $fiber) {
                if ($fiber->isTerminated()) {
                    unset($fibers[$index]);
                } elseif (! $fiber->isStarted() || $fiber->isSuspended()) {
                    $fiber->resume();
                }
            }
        }

        $this->info('Thumbnails generated successfully!');
    }
}
