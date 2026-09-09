<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateUniversDerivatives;
use App\Models\Univers;
use App\Models\UniversLayout as UniversLayoutModel;
use App\Services\UniversLayoutService;
use App\UniversLayoutPresets;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class UniversLayout extends Page
{
    protected string $view = 'filament.pages.univers-layout';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Univers Layout';

    protected static ?string $title = 'Univers Layout';

    protected static ?int $navigationSort = 2;

    public string $mode = 'generic';

    public ?string $preset = null;

    /** @var list<array<string, int|string>> */
    public array $layoutItems = [];

    public string $preview = 'desktop';

    public ?int $focalPointUniversId = null;

    public float $focalX = 0.5;

    public float $focalY = 0.5;

    public function mount(UniversLayoutService $layouts): void
    {
        $univers = Univers::query()->orderBy('position')->get();
        $saved = UniversLayoutModel::singleton();
        $resolved = $layouts->resolve($univers, $saved);

        $this->mode = $saved->mode;
        $this->preset = $saved->layout['preset'] ?? null;
        $this->layoutItems = $resolved['items'];
    }

    public function getUniversItemsProperty(): array
    {
        return Univers::query()->orderBy('position')->get()->all();
    }

    public function universItemsCount(): int
    {
        return count($this->universItems);
    }

    /** @return array<string, string> */
    public function presets(): array
    {
        return collect(UniversLayoutPresets::all())
            ->filter(fn (array $preset): bool => count($preset['items']) === Univers::count())
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['label']])
            ->all();
    }

    public function chooseMode(string $mode): void
    {
        $this->mode = in_array($mode, ['preset', 'custom', 'generic'], true) ? $mode : 'generic';

        if ($this->mode === 'preset') {
            $this->applyPreset($this->preset ?: $this->compatiblePreset());
        } else {
            if ($this->mode === 'generic') {
                $this->layoutItems = app(UniversLayoutService::class)->generic(
                    Univers::query()->orderBy('position')->get(),
                );
            }

            $this->dispatch('univers-layout-updated', items: $this->layoutItems, mode: $this->mode);
        }
    }

    public function applyPreset(?string $preset): void
    {
        if (! $preset || ! isset(UniversLayoutPresets::all()[$preset])) {
            return;
        }

        $univers = Univers::query()->orderBy('position')->get();
        $presetItems = UniversLayoutPresets::all()[$preset]['items'];

        if (count($univers) !== count($presetItems)) {
            Notification::make()->title('This preset needs a different number of images.')->warning()->send();

            return;
        }

        $this->mode = 'preset';
        $this->preset = $preset;
        $this->layoutItems = $univers->values()->map(function (Univers $item, int $index) use ($presetItems): array {
            $dimensions = $presetItems[$index];

            return [
                'univers_id' => $item->id,
                'index' => $index,
                'x' => 0,
                'y' => $index,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
            ];
        })->all();

        $this->dispatch('univers-layout-updated', items: $this->layoutItems, mode: $this->mode);
    }

    private function compatiblePreset(): ?string
    {
        $count = count($this->universItems);

        return collect(UniversLayoutPresets::all())
            ->filter(fn (array $preset): bool => count($preset['items']) === $count)
            ->keys()
            ->first();
    }

    /** @param list<array<string, int|string>> $items */
    public function setLayoutItems(array $items): void
    {
        $items = collect($items)->map(fn (array $item): array => [
            'univers_id' => (int) ($item['univers_id'] ?? $item['id'] ?? 0),
            'x' => (int) ($item['x'] ?? 0),
            'y' => (int) ($item['y'] ?? 0),
            'width' => (int) ($item['width'] ?? $item['w'] ?? 4),
            'height' => (int) ($item['height'] ?? $item['h'] ?? 3),
        ])->sortBy(['y', 'x'])->values();

        if ($this->mode === 'preset' && $this->preset && isset(UniversLayoutPresets::all()[$this->preset])) {
            $dimensions = UniversLayoutPresets::all()[$this->preset]['items'];
            $items = $items->values()->map(function (array $item, int $index) use ($dimensions): array {
                return [
                    ...$item,
                    'x' => 0,
                    'y' => $index,
                    'width' => $dimensions[$index]['width'],
                    'height' => $dimensions[$index]['height'],
                ];
            });
        }

        $this->layoutItems = $items->all();

        $this->dispatch('univers-layout-updated', items: $this->layoutItems, mode: $this->mode);

        // Preset changes only reorder its fixed-size slots.
    }

    public function saveLayout(): void
    {
        $items = collect($this->layoutItems)->map(fn (array $item): array => [
            'univers_id' => (int) $item['univers_id'],
            'x' => max((int) ($item['x'] ?? 0), 0),
            'y' => max((int) ($item['y'] ?? 0), 0),
            'width' => min(max((int) ($item['width'] ?? 4), 1), 12),
            'height' => min(max((int) ($item['height'] ?? 3), 1), 18),
        ])->values()->all();

        $validIds = Univers::query()->whereKey($items ? collect($items)->pluck('univers_id') : [])->pluck('id');

        abort_unless(
            count($items) === $validIds->count() && count($items) === $validIds->unique()->count(),
            422,
            'Invalid Univers layout items.',
        );

        if ($this->mode === 'custom') {
            $this->validateNoOverlap($items);
        }

        UniversLayoutModel::singleton()->update([
            'mode' => $this->mode,
            'version' => 1,
            'layout' => ['preset' => $this->preset, 'items' => $items],
        ]);

        Notification::make()->title('Univers layout saved.')->success()->send();
    }

    public function processAll(): void
    {
        Univers::query()->eachById(function (Univers $univers): void {
            $univers->forceFill(['processing_status' => 'queued'])->saveQuietly();
            GenerateUniversDerivatives::dispatch($univers);
        });

        Notification::make()->title('Univers images queued for processing.')->success()->send();
    }

    public function process(Univers $univers): void
    {
        $univers->forceFill(['processing_status' => 'queued'])->saveQuietly();
        GenerateUniversDerivatives::dispatch($univers);
        Notification::make()->title('Image queued for processing.')->success()->send();
    }

    public function reprocess(Univers $univers): void
    {
        $univers->forceFill(['processing_status' => 'queued'])->saveQuietly();
        GenerateUniversDerivatives::dispatch($univers);
        Notification::make()->title('Image queued for reprocessing.')->success()->send();
    }

    public function selectFocalPoint(int $universId): void
    {
        $univers = Univers::findOrFail($universId);

        $this->focalPointUniversId = $univers->id;
        $this->focalX = $univers->focal_x ?? 0.5;
        $this->focalY = $univers->focal_y ?? 0.5;
    }

    public function saveFocalPoint(): void
    {
        $univers = Univers::findOrFail($this->focalPointUniversId);

        $univers->update([
            'focal_x' => min(max($this->focalX, 0), 1),
            'focal_y' => min(max($this->focalY, 0), 1),
        ]);

        Notification::make()->title('Focal point saved.')->success()->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Add image')
                ->icon('heroicon-o-plus')
                ->form([
                    FileUpload::make('path')->image()->disk('photo')->directory('univers')->required(),
                    TextInput::make('title')->maxLength(255),
                    Textarea::make('description')->maxLength(65535),
                ])
                ->action(function (array $data): void {
                    Univers::create($data);
                    Notification::make()->title('Image added.')->success()->send();
                }),
            Action::make('edit')
                ->label('Edit image')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Select::make('univers_id')->label('Image')->options(fn (): array => Univers::query()->orderBy('position')->pluck('title', 'id')->map(fn (?string $title, int $id): string => $title ?: "Image {$id}")->all())->required(),
                    TextInput::make('title')->maxLength(255),
                    Textarea::make('description')->maxLength(65535),
                    TextInput::make('focal_x')->numeric()->minValue(0)->maxValue(1)->step(0.01)->default(0.5),
                    TextInput::make('focal_y')->numeric()->minValue(0)->maxValue(1)->step(0.01)->default(0.5),
                ])
                ->action(function (array $data): void {
                    $univers = Univers::findOrFail($data['univers_id']);
                    $univers->update(collect($data)->except('univers_id')->filter(fn ($value): bool => $value !== null && $value !== '')->all());
                    Notification::make()->title('Image updated.')->success()->send();
                }),
        ];
    }

    /** @param list<array{univers_id: int, x: int, y: int, width: int, height: int}> $items */
    private function validateNoOverlap(array $items): void
    {
        foreach ($items as $index => $item) {
            abort_if($item['x'] + $item['width'] > 12, 422, 'A tile exceeds the 12-column grid.');

            foreach (array_slice($items, $index + 1) as $other) {
                $overlap = $item['x'] < $other['x'] + $other['width']
                    && $other['x'] < $item['x'] + $item['width']
                    && $item['y'] < $other['y'] + $other['height']
                    && $other['y'] < $item['y'] + $item['height'];

                abort_if($overlap, 422, 'Tiles cannot overlap.');
            }
        }
    }
}
