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
            $items = $this->itemsForCustomLayout($univers, $document['items']);

            if ($items !== null) {
                return [
                    'mode' => 'custom',
                    'preset' => null,
                    'items' => $items,
                    'notice' => null,
                ];
            }
        }

        $order = collect($document['order'] ?? [])->map(fn (mixed $id): int => (int) $id)->all();
        $univers = $this->orderedUnivers($univers, $order);

        return [
            'mode' => 'generic',
            'preset' => null,
            'items' => $this->genericItems($univers),
            'notice' => match ($mode) {
                'preset' => 'This photo count has no matching preset. Custom layout is recommended.',
                'custom' => 'The saved custom layout is invalid. Images are shown in generic order instead.',
                default => null,
            },
        ];
    }

    /**
     * Build editor items for custom mode, restoring saved geometry when it is valid.
     *
     * @param  list<array<string, mixed>>  $savedItems
     * @return list<array<string, int|string>>
     */
    public function custom(Collection $univers, array $savedItems = []): array
    {
        if ($savedItems !== []) {
            $items = $this->itemsForCustomLayout($univers, $savedItems);

            if ($items !== null) {
                return $items;
            }
        }

        return $this->autoCompact($univers);
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

    /**
     * Compact every image into the first fitting slot, producing a clean
     * non-overlapping grid used when entering custom mode without a saved layout.
     *
     * @return list<array<string, int|string>>
     */
    public function autoCompact(Collection $univers): array
    {
        $items = $univers->values()->map(fn (Univers $item): array => UniversLayoutPresets::dimensions('standard'));
        $positions = UniversLayoutPresets::positions($items->all());

        return $univers->values()->map(function (Univers $item, int $index) use ($positions): array {
            $position = $positions[$index] ?? ['x' => 0, 'y' => $index];

            return $this->item($item, $index, UniversLayoutPresets::dimensions('standard'), $position['y'], $position['x']);
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

    /**
     * Merge saved custom geometry with the current Univers collection.
     *
     * Known images keep their saved tile geometry; unknown (new) images are
     * placed in the first free slot. Returns null when the result would be
     * an invalid layout, so callers can fall back to generic placement.
     *
     * @param  list<array<string, mixed>>  $savedItems
     * @return list<array<string, int|string>>|null
     */
    private function itemsForCustomLayout(Collection $univers, array $savedItems): ?array
    {
        $savedById = collect($savedItems)
            ->keyBy(fn (array $item): string => (string) ($item['univers_id'] ?? ''));
        $known = $univers->filter(fn (Univers $item): bool => $savedById->has((string) $item->id));
        $unknown = $univers->reject(fn (Univers $item): bool => $savedById->has((string) $item->id));

        $knownItems = $known->values()->map(function (Univers $item) use ($savedById): array {
            $saved = $savedById->get((string) $item->id, []);
            $width = (int) ($saved['width'] ?? 3);
            $height = (int) ($saved['height'] ?? 2);

            return $this->item($item, 0, [
                'width' => $width,
                'height' => $height,
            ], max((int) ($saved['y'] ?? 0), 0), min(max((int) ($saved['x'] ?? 0), 0), 11), [
                ...$saved,
                'width' => $width,
                'height' => $height,
            ]);
        });

        if ($unknown->isEmpty()) {
            $items = $knownItems->map(fn (array $item, int $index): array => [...$item, 'index' => $index])->values();

            return $this->isValidCustomLayout($items->all()) ? $items->sortBy(['y', 'x'])->values()->all() : null;
        }

        $items = $this->placeUnknownItems($unknown, $knownItems);

        $items = $items->map(fn (array $item, int $index): array => [...$item, 'index' => $index])->values();

        return $this->isValidCustomLayout($items->all()) ? $items->sortBy(['y', 'x'])->values()->all() : null;
    }

    /**
     * Position images without saved geometry in the first free slot that
     * accommodates a standard tile, keeping known tiles untouched.
     *
     * @param  Collection<int, Univers>  $unknown
     * @param  Collection<int, array<string, int|string>>  $knownItems
     * @return Collection<int, array<string, int|string>>
     */
    private function placeUnknownItems(Collection $unknown, Collection $knownItems): Collection
    {
        $footprints = [
            ...$knownItems->map(fn (array $item): array => [
                'width' => (int) $item['width'],
                'height' => (int) $item['height'],
            ])->values()->all(),
            ...$unknown->map(fn (): array => UniversLayoutPresets::dimensions('standard'))->values()->all(),
        ];

        $positions = UniversLayoutPresets::positions($footprints);
        $knownCount = $knownItems->count();

        return $knownItems
            ->values()
            ->map(fn (array $item, int $index): array => [...$item, 'x' => $positions[$index]['x'], 'y' => $positions[$index]['y']])
            ->merge($unknown->values()->map(function (Univers $item, int $index) use ($positions, $knownCount): array {
                $position = $positions[$knownCount + $index] ?? ['x' => 0, 'y' => 0];

                return $this->item($item, 0, UniversLayoutPresets::dimensions('standard'), $position['y'], $position['x']);
            }))
            ->values();
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
