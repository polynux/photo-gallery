<?php

namespace App\Services;

use App\Models\Univers;
use App\Models\UniversLayout;
use App\UniversLayoutPresets;
use Illuminate\Support\Collection;

class UniversLayoutService
{
    /**
     * @return array{mode: string, preset: ?string, items: list<array<string, int|string>>, notice: ?string}
     */
    public function resolve(Collection $univers, ?UniversLayout $layout = null): array
    {
        $layout ??= UniversLayout::singleton();
        $mode = $layout->mode;
        $document = $layout->layout ?? [];
        $preset = $document['preset'] ?? null;

        if ($mode === 'preset' && $preset && $this->presetMatches($preset, $univers->count())) {
            $presetItems = UniversLayoutPresets::all()[$preset]['items'];

            return [
                'mode' => 'preset',
                'preset' => $preset,
                'items' => $this->itemsForPreset($univers, $presetItems),
                'notice' => null,
            ];
        }

        if ($mode === 'custom' && ! empty($document['items'])) {
            return [
                'mode' => 'custom',
                'preset' => null,
                'items' => $this->itemsForCustomLayout($univers, $document['items']),
                'notice' => null,
            ];
        }

        return [
            'mode' => 'generic',
            'preset' => null,
            'items' => $this->genericItems($univers),
            'notice' => $mode === 'preset'
                ? 'This photo count has no matching preset. Custom layout is recommended.'
                : null,
        ];
    }

    /** @return list<array<string, int|string>> */
    public function generic(Collection $univers): array
    {
        return $this->genericItems($univers);
    }

    /** @param list<array{width: int, height: int}> $presetItems */
    private function itemsForPreset(Collection $univers, array $presetItems): array
    {
        return $univers->values()->map(function (Univers $item, int $index) use ($presetItems): array {
            $dimensions = $presetItems[$index] ?? UniversLayoutPresets::dimensions('standard');

            return $this->item($item, $index, $dimensions, $index);
        })->all();
    }

    /** @param list<array<string, mixed>> $savedItems */
    private function itemsForCustomLayout(Collection $univers, array $savedItems): array
    {
        $savedById = collect($savedItems)->keyBy(fn (array $item): string => (string) ($item['univers_id'] ?? ''));

        return $univers->values()->map(function (Univers $item, int $index) use ($savedById): array {
            $saved = $savedById->get((string) $item->id, []);

            return $this->item($item, $index, [
                'width' => (int) ($saved['width'] ?? 3),
                'height' => (int) ($saved['height'] ?? 3),
            ], (int) ($saved['y'] ?? $index), (int) ($saved['x'] ?? 0), $saved);
        })->sortBy(['y', 'x'])->values()->all();
    }

    private function genericItems(Collection $univers): array
    {
        return $univers->values()->map(fn (Univers $item, int $index): array => $this->item(
            $item,
            $index,
            UniversLayoutPresets::dimensions('standard'),
            $index,
        ))->all();
    }

    /** @param array{width: int, height: int} $dimensions */
    private function item(Univers $item, int $index, array $dimensions, int $y, int $x = 0, array $extra = []): array
    {
        return array_merge([
            'univers_id' => $item->id,
            'index' => $index,
            'x' => $x,
            'y' => $y,
            'width' => min(max($dimensions['width'], 1), 12),
            'height' => min(max($dimensions['height'], 1), 18),
            'class' => $this->classForDimensions($dimensions),
        ], $extra);
    }

    private function presetMatches(string $preset, int $count): bool
    {
        return preg_match('/(?:legacy|variation)-(\d+)$/', $preset, $matches) === 1
            && (int) $matches[1] === $count
            && isset(UniversLayoutPresets::all()[$preset]);
    }

    /** @param array{width: int, height: int} $dimensions */
    private function classForDimensions(array $dimensions): string
    {
        return match ([$dimensions['width'], $dimensions['height']]) {
            [6, 3] => 'wide',
            [3, 6] => 'tall',
            [6, 6] => 'big',
            default => 'standard',
        };
    }
}
