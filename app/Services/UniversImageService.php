<?php

namespace App\Services;

use App\Models\Univers;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class UniversImageService
{
    public function __construct(private readonly ImageManager $images) {}

    /** @return array{status: string, derivatives: array<string, array<string, string>>} */
    public function generate(Univers $univers): array
    {
        $univers->forceFill(['processing_status' => 'processing'])->saveQuietly();
        $source = $this->sourceDisk($univers);

        if (! $source->exists($univers->source_path)) {
            throw new RuntimeException("Univers source file not found: {$univers->source_path}");
        }

        $generated = [];
        $failures = 0;
        $version = Str::of($univers->source_path . '|' . $univers->updated_at?->getTimestamp())->slug('-');

        foreach (config('gallery.univers_derivative_sizes', [300, 500, 800]) as $width) {
            foreach (['jpg' => Format::JPEG, 'webp' => Format::WEBP] as $extension => $format) {
                try {
                    $image = $this->images->decodeBinary($source->get($univers->source_path));
                    $image->scaleDown(width: (int) $width);
                    $path = "univers/{$univers->id}/{$version}/{$width}.{$extension}";

                    Storage::disk('public')->put(
                        $path,
                        $image->encodeUsingFormat($format, quality: config('gallery.univers_derivative_quality', 80))->toString(),
                    );

                    $generated[(string) $width][$extension] = $path;
                } catch (Throwable $exception) {
                    $failures++;
                    report($exception);
                }
            }
        }

        $expected = count(config('gallery.univers_derivative_sizes', [300, 500, 800])) * 2;
        $actual = collect($generated)->flatten()->count();
        $status = $actual === 0 ? 'failed' : ($actual < $expected ? 'partially_processed' : 'processed');

        if ($status === 'processed') {
            $oldDerivatives = $univers->derivatives ?? [];

            $univers->forceFill([
                'processing_status' => $status,
                'derivatives' => $generated,
            ])->saveQuietly();

            $this->deleteDerivatives($oldDerivatives, $generated);
        } else {
            $univers->forceFill([
                'processing_status' => $status,
            ])->saveQuietly();
        }

        if ($failures > 0 && $actual === 0) {
            throw new RuntimeException("Unable to generate Univers derivatives for {$univers->id}.");
        }

        return ['status' => $status, 'derivatives' => $univers->derivatives ?? $generated];
    }

    public function url(Univers $univers, int $width, string $format = 'jpg'): ?string
    {
        $path = $univers->derivatives[(string) $width][$format] ?? null;

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    private function sourceDisk(Univers $univers): FilesystemAdapter
    {
        if (Storage::disk('photo')->exists($univers->source_path)) {
            return Storage::disk('photo');
        }

        return Storage::disk('public');
    }

    /** @param array<string, mixed> $oldDerivatives */
    /** @param array<string, mixed> $newDerivatives */
    private function deleteDerivatives(array $oldDerivatives, array $newDerivatives): void
    {
        $oldPaths = collect($oldDerivatives)->flatten()->filter()->values();
        $newPaths = collect($newDerivatives)->flatten()->filter()->values();

        foreach ($oldPaths->diff($newPaths) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
