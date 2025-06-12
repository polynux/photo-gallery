<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;

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
    public function handle()
    {
        Photo::chunk(100, function ($photos) {
            foreach ($photos as $photo) {
                $thunbnailPath = 'thumbnails/' . $photo->path;
                if (!file_exists($thunbnailPath)) {
                    $photo->generateThumbnail();
                    $photo->save();
                }
            }
        });
        $this->info('Thumbnails generated successfully!');
    }
}
