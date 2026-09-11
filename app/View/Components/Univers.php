<?php

namespace App\View\Components;

use App\Models\Univers as UniversModel;
use App\Services\UniversImageService;
use App\Services\UniversLayoutService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Component;

class Univers extends Component
{
    public function __construct() {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $images = UniversModel::orderBy('position')->get();
        $layout = app(UniversLayoutService::class)->resolve($images);

        $images = $this->withSources($images, $layout['items']);

        return view('components.univers', [
            'univers' => $images,
            'layout' => $layout,
        ]);
    }

    /**
     * Resolve derivative and source URLs once per image, in layout order.
     *
     * @param  Collection<int, UniversModel>  $univers
     * @param  list<array<string, int|string>>  $items
     * @return Collection<int, UniversModel>
     */
    private function withSources(Collection $univers, array $items): Collection
    {
        $imageService = app(UniversImageService::class);
        $order = collect($items)->pluck('univers_id')->all();

        return $univers
            ->sortBy(fn (UniversModel $image): int => array_search($image->id, $order, true) ?: PHP_INT_MAX)
            ->each(fn (UniversModel $image): UniversModel => $image->setRelation(
                'gallerySources',
                $this->sources($imageService, $image),
            ));
    }

    /** @return array{webp: bool, jpeg: string, widths: array<string, array{webp: ?string, jpg: string}>} */
    private function sources(UniversImageService $images, UniversModel $image): array
    {
        $sizes = config('gallery.univers_derivative_sizes', [300, 500, 800]);
        $widths = collect($sizes)
            ->mapWithKeys(fn (int $width): array => [
                (string) $width => [
                    'webp' => $images->url($image, $width, 'webp'),
                    'jpg' => $images->url($image, $width, 'jpg') ?? URL::temporarySignedRoute('univers.source', now()->addMinutes(10), $image),
                ],
            ])
            ->all();

        return [
            'webp' => collect($widths)->contains(fn (array $formats): bool => $formats['webp'] !== null),
            'jpeg' => $widths[(string) end($sizes)]['jpg'],
            'widths' => $widths,
        ];
    }
}
