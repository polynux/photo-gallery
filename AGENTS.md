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
# Run all linting (TLint, PHPCS, PHP-CS-Fixer, Pint)
composer lint

# Auto-fix all linting issues
composer lint:fix

# Run Duster directly
./vendor/bin/duster lint
./vendor/bin/duster fix

# Run Laravel Pint directly
./vendor/bin/pint
./vendor/bin/pint --test

# Check blade syntax (caches views, fails on syntax errors)
php artisan view:cache
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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v3
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament rules ===

## Filament

- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan

- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features

- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships

- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>

## Testing

- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>

## Version 3 Changes To Focus On

- Resources are located in `app/Filament/Resources/` directory.
- Resource pages (List, Create, Edit) are auto-generated within the resource's directory - e.g., `app/Filament/Resources/PostResource/Pages/`.
- Forms use the `Forms\Components` namespace for form fields.
- Tables use the `Tables\Columns` namespace for table columns.
- A new `Filament\Forms\Components\RichEditor` component is available.
- Form and table schemas now use fluent method chaining.
- Added `php artisan filament:optimize` command for production optimization.
- Requires implementing `FilamentUser` contract for production access control.

</laravel-boost-guidelines>
