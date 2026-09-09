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

        foreach (range(9, 13) as $count) {
            $presets["legacy-{$count}"] = [
                'label' => "Legacy {$count} images",
                'items' => array_map(
                    fn (string $type): array => self::dimensions($type),
                    self::legacyClasses($count),
                ),
            ];
        }

        return $presets;
    }

    /** @return array{width: int, height: int} */
    public static function dimensions(string $type): array
    {
        return match ($type) {
            'wide' => ['width' => 6, 'height' => 3],
            'tall' => ['width' => 3, 'height' => 6],
            'large' => ['width' => 6, 'height' => 6],
            default => ['width' => 3, 'height' => 3],
        };
    }
}
