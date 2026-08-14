# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Filament v4 Demo Application** built on Laravel 12, showcasing the capabilities of the Filament admin panel framework. The application demonstrates a multi-domain e-commerce and blog system with extensive relationship examples, media management, and advanced admin panel features.

## Common Development Commands

### Setup
```bash
# Initial project setup (creates .env, generates key, creates SQLite database, runs migrations and seeders)
composer setup

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install
```

### Development Server
```bash
# Start Laravel development server
php artisan serve

# Build frontend assets for development
npm run dev

# Build frontend assets for production
npm run build
```

### Code Quality
```bash
# Run Laravel Pint code formatter
composer pint

# Run PHPStan static analysis
composer test:phpstan
```

### Database
```bash
# Run database migrations
php artisan migrate

# Seed database with demo data
php artisan db:seed

# Create a fresh database with seeded data
php artisan migrate:fresh --seed
```

### Testing
```bash
# Run PHPUnit tests
./vendor/bin/phpunit

# Run PHPStan static analysis
composer test:phpstan
```

### Default Admin Login
- **Email:** admin@filamentphp.com
- **Password:** password

## Architecture Overview

### Filament Resource Organization

This project follows a **highly organized Filament architecture** that separates concerns into distinct directories:

#### Resource Structure Pattern
```
app/Filament/
├── Clusters/                    # Grouped resources (e.g., Products cluster)
│   └── Products/
│       ├── ProductsCluster.php
│       └── Resources/
│           ├── Brands/
│           │   ├── BrandResource.php
│           │   ├── Pages/       # CRUD pages (List, Create, Edit)
│           │   ├── RelationManagers/  # Manage related models
│           │   ├── Schemas/     # Form definitions (BrandForm.php)
│           │   └── Tables/      # Table definitions (BrandsTable.php)
│           ├── Categories/
│           └── Products/
├── Resources/                   # Standalone resources (Shop, Blog)
│   ├── Blog/
│   │   ├── Authors/
│   │   ├── Categories/
│   │   └── Posts/
│   └── Shop/
│       ├── Customers/
│       ├── Orders/
└── Widgets/                     # Dashboard widgets
```

#### Key Architectural Decisions

1. **Schemas and Tables Pattern**: Form logic and table configurations are extracted into separate classes (`Schemas/` and `Tables/` directories) rather than being defined directly in the Resource class. This promotes reusability and maintainability.

2. **Resource Class Structure**:
   - Resources call static methods on Schema/Table classes: `ProductForm::configure($schema)` and `ProductsTable::configure($table)`
   - This keeps Resource classes clean and focused on registration

3. **Clusters**: Related resources are grouped under Clusters (e.g., `ProductsCluster` contains Brands, Categories, and Products)

### Domain Models

The application is organized into two primary domains:

#### Shop Domain (`app/Models/Shop/`)
- **Product**: E-commerce products with media library integration (Spatie Media Library)
- **Brand**: Product manufacturers/brands with polymorphic addresses
- **Category**: Product categorization (many-to-many with products)
- **Customer**: Customer accounts with polymorphic addresses
- **Order**: Purchase orders with polymorphic addresses
- **OrderItem**: Individual items within an order
- **Payment**: Payment transactions linked to orders

#### Blog Domain (`app/Models/Blog/`)
- **Post**: Blog posts with rich editor content, tags, and comments
- **Author**: Content creators with profile information
- **Category**: Post categorization

#### Shared Models
- **Comment**: Polymorphic comments (can be attached to Products or Posts)
- **Address**: Polymorphic addresses (used by Brands and Customers via `MorphToMany`)

### Key Relationships Demonstrated

This demo showcases all major Eloquent relationship types:

- **BelongsTo**: Product → Brand, Order → Customer, Post → Author
- **BelongsToMany**: Product ↔ Category, Brand ↔ Address (morphToMany)
- **HasMany**: Order → OrderItem, Post → Comments
- **HasManyThrough**: Customer → Payments (through Orders)
- **MorphOne**: Order → Address
- **MorphMany**: Product → Comments, Post → Comments
- **MorphToMany**: Brand ↔ Address, Customer ↔ Address

### Panel Configuration

The admin panel is configured in `app/Providers/Filament/AdminPanelProvider.php`:
- Uses auto-discovery for Resources, Pages, Widgets, and Clusters
- Implements SPA mode for better UX
- Database notifications enabled
- Custom login page
- Navigation groups: Shop, Blog

### Frontend Stack

- **Vite**: Asset bundling and hot module replacement
- **Tailwind CSS v4**: Utility-first CSS framework
- **Alpine.js**: Lightweight JavaScript framework (used by Filament)
- **Livewire**: Full-stack framework (Filament is built on Livewire)

Hot reload is configured for Filament directories in `vite.config.js`.

### Media Management

Uses **Spatie Media Library** for file uploads:
- Product images are stored in the `product-images` collection
- Media conversions configured (e.g., thumbnails: 40x40px)
- Disk configuration in `config/filesystems.php`

## When Developing Features

### Adding a New Resource

1. Create the Eloquent model in the appropriate domain (`Shop/` or `Blog/`)
2. Create a Filament Resource using: `php artisan make:filament-resource ModelName`
3. Extract form logic into `Schemas/ModelForm.php` with a static `configure(Schema $schema)` method
4. Extract table logic into `Tables/ModelsTable.php` with a static `configure(Table $table)` method
5. Update the Resource to call these static methods
6. If grouping with other resources, assign a `$cluster` property

### Working with Relationships

- Use `RelationManagers/` for managing related records within a Resource
- Follow the existing patterns in `CustomerResource\RelationManagers\PaymentsRelationManager.php`
- Relation managers are registered in the Resource's `getRelations()` method

### Database Changes

- All migrations are in `database/migrations/`
- Database seeding uses factories extensively (`database/factories/`)
- The project uses **SQLite** by default for simplicity (see `.env.example`)

## File Locations

- **Models**: `app/Models/Shop/` and `app/Models/Blog/`
- **Filament Resources**: `app/Filament/Resources/` and `app/Filament/Clusters/`
- **Migrations**: `database/migrations/`
- **Seeders**: `database/seeders/`
- **Frontend Assets**: `resources/css/` and `resources/js/`
- **Views**: `resources/views/`
- **Config**: `config/`

## Important Notes

- This is a **demo application** - bulk delete actions are intentionally disabled with warning messages
- The database uses SQLite (file located at `database/database.sqlite`)
- All Filament panels use auto-discovery; add new resources to appropriate directories and they'll be automatically registered
- Product media requires JPEG format only (configured in `Product` model)

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.16
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v12
- laravel/nightwatch (NIGHTWATCH) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- larastan/larastan (LARASTAN) - v3
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the new Laravel structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.
</laravel-boost-guidelines>
