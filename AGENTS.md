# AGENTS.md

Guidelines for AI coding agents working in this Laravel photo gallery application.

## Project Overview

A Laravel 12 photo gallery application with Filament admin panel. Features include:
- Photo galleries with password protection and access codes
- Thumbnail generation via queued jobs
- Public gallery viewing and ZIP downloads
- Multi-universe support for organizing galleries

## Build Commands

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
pnpm install

# Build frontend assets for production
pnpm run build

# Start development environment (server, queue, logs, vite)
composer dev

# Start individual services
php artisan serve           # Development server
php artisan queue:listen    # Queue worker
pnpm run dev                 # Vite dev server
```

## Linting & Formatting

```bash
# Run Laravel Pint (code fixer)
./vendor/bin/pint

# Check code style without fixing (dry run)
./vendor/bin/pint --test

# Format specific file
./vendor/bin/pint app/Models/Photo.php
```

## Testing

```bash
# Run all tests
php artisan test
# or
./vendor/bin/pest

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run a single test file
php artisan test tests/Feature/ExampleTest.php
./vendor/bin/pest tests/Feature/ExampleTest.php

# Run a single test by name
php artisan test --filter "test_name"
./vendor/bin/pest --filter "test_name"

# Run tests with coverage
php artisan test --coverage

# Parallel tests
php artisan test --parallel
```

## Code Style Guidelines

### PHP Formatting

- **Indentation**: 4 spaces (no tabs)
- **Line endings**: LF (`\n`)
- **Charset**: UTF-8
- **Final newline**: Required
- **Trailing whitespace**: Trim (except `.md` files)
- **YAML files**: 2-space indentation

### Imports & Namespaces

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RelatedModel;
```

- Place namespace immediately after opening `<?php` tag
- Add one blank line after namespace
- Group imports: Laravel/framework first, then external packages, then app classes
- Each import on its own line
- Use fully qualified class names in docblocks: `@return HasMany<Photo,PhotoGallery>`

### Class Structure

```php
class ExampleModel extends Model
{
    protected $fillable = ['field1', 'field2'];
    
    protected $hidden = ['password'];
    
    public function relationship(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }
    
    protected static function booted(): void
    {
        static::creating(function ($model) {
            // Model events
        });
    }
}
```

### Typing & Return Types

- Add return type declarations on all public methods: `public function name(): string`
- Use relation return types on relationship methods: `public function photos(): HasMany`
- Add PHPDoc annotations for complex types when needed
- Use constructor property promotion: `public function __construct(public Photo $photo) {}`

### Filament Resources

```php
class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([...])->filters([...])->actions([...]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotos::route('/'),
            'create' => Pages\CreatePhoto::route('/create'),
            'edit' => Pages\EditPhoto::route('/{record}/edit'),
        ];
    }
}
```

### Controllers

- Keep controllers thin; use form requests or services for complex logic
- Use route model binding: `public function show(PhotoGallery $gallery)`
- Return views with compact: `return view('gallery', compact('photos', 'gallery'));`
- Use session for flash data: `session(['key' => $value])`
- Use validation via `$request->validate()` or form request classes

### Error Handling

- Use `report($e)` to log exceptions without crashing
- Use `Log::info()`, `Log::warning()` for structured logging
- Return appropriate HTTP responses: `abort(404, 'Message')`, `back()->withErrors([...])`
- Wrap file operations in try-catch with proper error reporting

### Queue Jobs

```php
class GeneratePhotoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(public Photo $photo) {}
    
    public function handle(): void
    {
        $this->photo->generateThumbnail();
    }
}
```

### Blade Views

- Store public views in `resources/views/public/`
- Use component-based views: `<x-univers :gallery="$gallery" />`
- View components live in `app/View/Components/`

### Database

- Migrations use anonymous class syntax: `return new class extends Migration`
- Foreign keys with cascading: `$table->foreignId('photo_gallery_id')->constrained()->cascadeOnDelete()`
- Models in `App\Models` namespace (not `App\Models\` subfolders typically)
- Add fillable guards: `protected $fillable = [...]`

## Git Safety Guidelines

### NEVER Use Bulk Add Commands

**FORBIDDEN commands (will cause unintended file commits):**
- ❌ `git add .`
- ❌ `git add --all`
- ❌ `git add -A`
- ❌ `git commit -a`
- ❌ `git commit -am "message"`

**WHY:** These commands stage ALL modified files, including:
- Sensitive files (credentials, tokens)
- Large binary files
- Temporary files
- Generated files that should be gitignored
- Files accidentally modified

### Correct Way to Stage Files

**Always stage files individually or by specific pattern:**

```bash
# Stage specific files
git add resources/views/public/gallery.blade.php

# Stage files in a specific directory
git add resources/views/public/

# Stage files matching a pattern
git add "*.blade.php"

# Check what will be committed
git status
git diff --cached

# Then commit
# NEVER add --no-verify or --no-gpg-sign
# NEVER skip hooks unless explicitly requested
git commit -m "feat: add new feature"
```

### Pre-Commit Checklist

Before committing, verify:
1. ✅ `git status` - review all staged files
2. ✅ `git diff --cached` - review actual changes
3. ✅ No secrets or credentials included
4. ✅ Only intended files are staged
5. ✅ `.gitignore` properly excludes temporary files

### Commit Message Format

```bash
git commit -m "type: description

- Detail 1
- Detail 2
- Detail 3"
```

Valid types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

---

## Architecture Notes

- **Disks configured**: `photo`, `thumbnails`, `private`
- **Thumbnails**: Generated as 1920px JPEG at 80% quality
- **Access codes**: 8-character random strings for gallery access
- **Session-based gallery authentication**: Check via `session('authenticated_gallery_' . $id)`
