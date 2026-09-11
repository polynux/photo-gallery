<?php

namespace App;

class UniversLayoutPresets
{
    public static function legacyClasses(int $count): array
    {
        return [
            9 => ['big', 'wide', 'tall', 'standard', 'standard', 'wide', 'standard', 'tall', 'standard'],
            10 => ['big', 'wide', 'tall', 'standard', 'standard', 'wide', 'standard', 'tall', 'standard', 'wide'],
            11 => ['big', 'wide', 'tall', 'standard', 'standard', 'wide', 'standard', 'tall', 'standard', 'wide', 'standard'],
            12 => ['big', 'wide', 'tall', 'standard', 'standard', 'wide', 'standard', 'tall', 'standard', 'wide', 'standard', 'wide'],
            13 => ['big', 'wide', 'tall', 'standard', 'standard', 'wide', 'standard', 'tall', 'standard', 'big', 'standard', 'wide', 'standard'],
        ][$count] ?? ['big', 'wide', 'tall', 'standard'];
    }

    public static function classToType(string $class): string
    {
        return match ($class) {
            'wide' => 'wide',
            'tall' => 'tall',
            'big' => 'large',
            default => 'standard',
        };
    }

    /**
     * @return array<string, array{label: string, items: list<array{width: int, height: int}>}>
     */
    public static function all(): array
    {
        $presets = [];

        foreach (range(1, 13) as $count) {
            if ($count >= 9) {
                $presets["legacy-{$count}"] = [
                    'label' => "Legacy {$count} images",
                    'items' => array_map(
                        fn (string $type): array => self::legacyDimensions($type),
                        self::legacyClasses($count),
                    ),
                ];
            }

            $presets["variation-{$count}"] = [
                'label' => "Variation {$count} images",
                'items' => self::explicitVariation($count),
            ];

            $presets["square-{$count}"] = [
                'label' => "Square variation {$count} images",
                'items' => self::squareVariation($count),
            ];
        }

        return $presets;
    }

    /**
     * @param  list<array{width: int, height: int}>  $items
     * @return list<array{x: int, y: int}>
     */
    public static function positions(array $items, int $xStep = 1, int $yStep = 1): array
    {
        $positions = [];
        $occupied = [];

        foreach ($items as $index => $item) {
            $position = null;

            for ($y = 0; $position === null; $y += $yStep) {
                for ($x = 0; $x <= 12 - $item['width']; $x += $xStep) {
                    if (self::fits($occupied, $x, $y, $item['width'], $item['height'])) {
                        $position = ['x' => $x, 'y' => $y];
                        break;
                    }
                }
            }

            $positions[$index] = $position;

            for ($row = $position['y']; $row < $position['y'] + $item['height']; $row++) {
                for ($column = $position['x']; $column < $position['x'] + $item['width']; $column++) {
                    $occupied[$row][$column] = true;
                }
            }
        }

        return $positions;
    }

    /**
     * @param  list<array{width: int, height: int}>  $items
     * @return list<array{x: int, y: int}>
     */
    public static function positionsFor(string $preset, array $items): array
    {
        if ($preset === 'legacy-9') {
            return [
                ['x' => 0, 'y' => 0],
                ['x' => 6, 'y' => 0],
                ['x' => 0, 'y' => 4],
                ['x' => 6, 'y' => 4],
                ['x' => 9, 'y' => 4],
                ['x' => 6, 'y' => 2],
                ['x' => 6, 'y' => 6],
                ['x' => 3, 'y' => 4],
                ['x' => 9, 'y' => 6],
            ];
        }

        if ($preset === 'legacy-13') {
            return [
                ['x' => 0, 'y' => 0],
                ['x' => 0, 'y' => 4],
                ['x' => 6, 'y' => 6],
                ['x' => 0, 'y' => 8],
                ['x' => 3, 'y' => 8],
                ['x' => 6, 'y' => 4],
                ['x' => 0, 'y' => 10],
                ['x' => 9, 'y' => 6],
                ['x' => 3, 'y' => 10],
                ['x' => 6, 'y' => 0],
                ['x' => 6, 'y' => 10],
                ['x' => 0, 'y' => 6],
                ['x' => 9, 'y' => 10],
            ];
        }

        if ($preset === 'variation-13') {
            return [
                ['x' => 0, 'y' => 0],
                ['x' => 6, 'y' => 0],
                ['x' => 0, 'y' => 4],
                ['x' => 6, 'y' => 4],
                ['x' => 0, 'y' => 6],
                ['x' => 9, 'y' => 0],
                ['x' => 9, 'y' => 2],
                ['x' => 6, 'y' => 6],
                ['x' => 9, 'y' => 6],
                ['x' => 0, 'y' => 8],
                ['x' => 3, 'y' => 8],
                ['x' => 6, 'y' => 8],
                ['x' => 9, 'y' => 8],
            ];
        }

        if (str_starts_with($preset, 'square-')) {
            return self::positions($items, 3, 3);
        }

        return str_starts_with($preset, 'legacy-')
            ? self::legacyPositions($items)
            : self::positions($items);
    }

    /** @return list<array{width: int, height: int}> */
    public static function explicitVariation(int $count): array
    {
        return match ($count) {
            13 => [
                ...array_fill(0, 1, self::dimensions('large')),
                ...array_fill(0, 1, self::dimensions('tall')),
                ...array_fill(0, 3, self::dimensions('wide')),
                ...array_fill(0, 8, self::dimensions('standard')),
            ],
            default => self::variation($count),
        };
    }

    /** @param list<array{width: int, height: int}> $items */
    public static function legacyPositions(array $items): array
    {
        return self::positions($items, 3, 1);
    }

    /** @return array{width: int, height: int} */
    public static function dimensions(string $type): array
    {
        return match ($type) {
            'wide' => ['width' => 6, 'height' => 2],
            'tall' => ['width' => 3, 'height' => 4],
            'large' => ['width' => 6, 'height' => 4],
            'square' => ['width' => 3, 'height' => 3],
            default => ['width' => 3, 'height' => 2],
        };
    }

    /** @return array{width: int, height: int} */
    public static function legacyDimensions(string $type): array
    {
        return match ($type) {
            'wide' => ['width' => 6, 'height' => 2],
            'tall' => ['width' => 3, 'height' => 4],
            'big' => ['width' => 6, 'height' => 4],
            default => ['width' => 3, 'height' => 2],
        };
    }

    /**
     * @return list<array{width: int, height: int}>
     */
    public static function variation(int $count): array
    {
        $types = match ($count) {
            13 => ['large', 'tall', 'wide', 'standard', 'standard', 'wide', 'standard', 'standard', 'wide', 'standard', 'standard', 'standard', 'standard'],
            default => array_fill(0, $count, 'standard'),
        };

        if ($count > 0) {
            $types[0] = 'large';
        }

        if ($count > 1) {
            $types[1] = 'tall';
        }

        if ($count > 2) {
            $types[2] = 'wide';
        }

        return array_map(
            fn (string $type): array => self::dimensions($type),
            $types,
        );
    }

    /** @return list<array{width: int, height: int}> */
    public static function squareVariation(int $count): array
    {
        return array_fill(0, $count, self::dimensions('square'));
    }

    /**
     * @param  array<int, array<int, bool>>  $occupied
     */
    private static function fits(array $occupied, int $x, int $y, int $width, int $height): bool
    {
        for ($row = $y; $row < $y + $height; $row++) {
            for ($column = $x; $column < $x + $width; $column++) {
                if ($occupied[$row][$column] ?? false) {
                    return false;
                }
            }
        }

        return true;
    }
}
