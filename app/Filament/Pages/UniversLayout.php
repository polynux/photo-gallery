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
use Illuminate\Support\Facades\URL;

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

    public bool $isDirty = false;

    public float $focalX = 0.5;

    public float $focalY = 0.5;

    public function mount(UniversLayoutService $layouts): void
    {
        $univers = Univers::query()->orderBy('position')->get();
        $saved = UniversLayoutModel::singleton();
        $resolved = $layouts->resolve($univers, $saved);

        $this->mode = $resolved['mode'];
        $this->preset = $resolved['preset'];
        $this->layoutItems = $resolved['items'];
        $this->isDirty = false;
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
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['label']])
            ->all();
    }

    /** @return list<array<string, int|string>> */
    public function editorItems(): array
    {
        $univers = Univers::query()->orderBy('position')->get()->keyBy('id');

        return collect($this->layoutItems)
            ->map(function (array $item) use ($univers): ?array {
                $image = $univers->get((int) $item['univers_id']);

                if (! $image) {
                    return null;
                }

                return [
                    ...$item,
                    'title' => $image->title ?: 'Untitled image',
                    'status' => str_replace('_', ' ', $image->processing_status),
                    'source' => URL::temporarySignedRoute('univers.source', now()->addMinutes(10), $image),
                    'focal_x' => (float) ($image->focal_x ?? 0.5),
                    'focal_y' => (float) ($image->focal_y ?? 0.5),
                ];
            })
            ->filter()
            ->values()
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

            $this->isDirty = true;
            $this->dispatchEditorUpdate();
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
        $this->layoutItems = app(UniversLayoutService::class)->itemsForPreset($univers, $preset, $presetItems);
        $this->isDirty = true;

        $this->dispatchEditorUpdate();
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
                    'width' => $dimensions[$index]['width'],
                    'height' => $dimensions[$index]['height'],
                ];
            });
        }

        $this->layoutItems = $items->all();
        $this->isDirty = true;

        $this->dispatchEditorUpdate();
    }

    /** @param list<int|string> $universIds */
    public function reorderGeneric(array $universIds): void
    {
        abort_unless($this->mode === 'generic', 422, 'Generic order is not active.');

        $validIds = Univers::query()->whereKey($universIds)->pluck('id')->all();
        $universIds = collect($universIds)->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();

        abort_unless(count($universIds) === count($validIds) && count($universIds) === Univers::count(), 422, 'Invalid Univers order.');

        $univers = Univers::query()->whereKey($universIds)->get()->keyBy('id');
        $ordered = collect($universIds)->map(fn (int $id): Univers => $univers->get($id));
        $this->layoutItems = app(UniversLayoutService::class)->generic($ordered);
        $this->isDirty = true;
        $this->dispatchEditorUpdate();
    }

    public function swapPresetItems(int $firstUniversId, int $secondUniversId): void
    {
        abort_unless($this->mode === 'preset', 422, 'Preset slots cannot be moved.');

        $first = collect($this->layoutItems)->search(fn (array $item): bool => (int) $item['univers_id'] === $firstUniversId);
        $second = collect($this->layoutItems)->search(fn (array $item): bool => (int) $item['univers_id'] === $secondUniversId);

        abort_unless($first !== false && $second !== false, 422, 'Invalid Univers layout items.');

        [$this->layoutItems[$first]['univers_id'], $this->layoutItems[$second]['univers_id']] = [
            $this->layoutItems[$second]['univers_id'],
            $this->layoutItems[$first]['univers_id'],
        ];

        $this->dispatchEditorUpdate();
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

        if ($this->mode === 'generic') {
            $layout = ['preset' => null, 'order' => collect($items)->pluck('univers_id')->all(), 'items' => []];
        } elseif ($this->mode === 'preset') {
            $layout = ['preset' => $this->preset, 'assignments' => collect($items)->pluck('univers_id')->all(), 'items' => []];
        } else {
            $layout = ['preset' => null, 'items' => $items];
        }

        UniversLayoutModel::singleton()->update([
            'mode' => $this->mode,
            'version' => 1,
            'layout' => $layout,
        ]);

        $this->isDirty = false;

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
                    $this->refreshEditorState();
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

    private function refreshEditorState(): void
    {
        $univers = Univers::query()->orderBy('position')->get();
        $saved = UniversLayoutModel::singleton();
        $resolved = app(UniversLayoutService::class)->resolve($univers, $saved);

        $this->mode = $resolved['mode'];
        $this->preset = $resolved['preset'];
        $this->layoutItems = $resolved['items'];
        $this->isDirty = false;
        $this->dispatchEditorUpdate();
    }

    private function dispatchEditorUpdate(): void
    {
        $this->dispatch(
            'univers-layout-updated',
            editorItems: $this->editorItems(),
            mode: $this->mode,
            preset: $this->preset,
            preview: $this->preview,
        );
    }
}
