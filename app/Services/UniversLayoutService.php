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
            $assignments = collect($document['assignments'] ?? [])->map(fn (mixed $id): int => (int) $id)->all();
            $univers = $this->orderedUnivers($univers, $assignments);

            return [
                'mode' => 'preset',
                'preset' => $preset,
                'items' => $this->itemsForPreset($univers, $preset, $presetItems),
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

        $order = collect($document['order'] ?? [])->map(fn (mixed $id): int => (int) $id)->all();
        $univers = $this->orderedUnivers($univers, $order);

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
    public function generic(Collection $univers, array $order = []): array
    {
        return $this->genericItems($this->orderedUnivers($univers, $order));
    }

    /** @param list<array{width: int, height: int}> $presetItems */
    public function itemsForPreset(Collection $univers, string $preset, array $presetItems): array
    {
        $positions = UniversLayoutPresets::positionsFor($preset, $presetItems);

        return $univers->values()->map(function (Univers $item, int $index) use ($presetItems, $positions): array {
            $dimensions = $presetItems[$index] ?? UniversLayoutPresets::dimensions('standard');
            $position = $positions[$index] ?? ['x' => 0, 'y' => $index];

            return $this->item($item, $index, $dimensions, $position['y'], $position['x']);
        })->all();
    }

    /** @param list<int> $order */
    private function orderedUnivers(Collection $univers, array $order): Collection
    {
        if ($order === []) {
            return $univers;
        }

        $byId = $univers->keyBy('id');

        return collect($order)->map(fn (int $id): ?Univers => $byId->get($id))->filter()->values()->merge(
            $univers->reject(fn (Univers $item): bool => in_array($item->id, $order, true)),
        );
    }

    /** @param list<array<string, mixed>> $savedItems */
    private function itemsForCustomLayout(Collection $univers, array $savedItems): array
    {
        $savedById = collect($savedItems)->keyBy(fn (array $item): string => (string) ($item['univers_id'] ?? ''));

        $items = $univers->values()->map(function (Univers $item, int $index) use ($savedById): array {
            $saved = $savedById->get((string) $item->id, []);
            $width = (int) ($saved['width'] ?? 4);
            $height = (int) ($saved['height'] ?? 3);

            return $this->item($item, $index, [
                'width' => $width,
                'height' => $height,
            ], max((int) ($saved['y'] ?? $index), 0), min(max((int) ($saved['x'] ?? 0), 0), 11), [
                ...$saved,
                'width' => $width,
                'height' => $height,
            ]);
        })->sortBy(['y', 'x'])->values()->all();

        return $this->isValidCustomLayout($items) ? $items : $this->genericItems($univers);
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
        return preg_match('/(?:legacy|variation|square)-(\d+)$/', $preset, $matches) === 1
            && (int) $matches[1] === $count
            && isset(UniversLayoutPresets::all()[$preset]);
    }

    /** @param array{width: int, height: int} $dimensions */
    private function classForDimensions(array $dimensions): string
    {
        return match ([$dimensions['width'], $dimensions['height']]) {
            [6, 2] => 'wide',
            [3, 4] => 'tall',
            [6, 4] => 'big',
            [3, 3] => 'square',
            default => 'small',
        };
    }

    /** @param list<array<string, int|string>> $items */
    private function isValidCustomLayout(array $items): bool
    {
        foreach ($items as $index => $item) {
            if ($item['x'] + $item['width'] > 12 || $item['y'] < 0) {
                return false;
            }

            foreach (array_slice($items, $index + 1) as $other) {
                if ($item['x'] < $other['x'] + $other['width']
                    && $other['x'] < $item['x'] + $item['width']
                    && $item['y'] < $other['y'] + $other['height']
                    && $other['y'] < $item['y'] + $item['height']) {
                    return false;
                }
            }
        }

        return true;
    }
}
