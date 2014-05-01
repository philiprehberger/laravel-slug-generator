# Laravel Slug Generator

[![Tests](https://github.com/philiprehberger/laravel-slug-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-slug-generator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-slug-generator.svg)](https://packagist.org/packages/philiprehberger/laravel-slug-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/philiprehberger/laravel-slug-generator.svg)](https://packagist.org/packages/philiprehberger/laravel-slug-generator)
[![PHP Version](https://img.shields.io/packagist/php-v/philiprehberger/laravel-slug-generator.svg)](https://packagist.org/packages/philiprehberger/laravel-slug-generator)
[![License](https://img.shields.io/packagist/l/philiprehberger/laravel-slug-generator.svg)](LICENSE)

Automatic slug generation for Eloquent models with scoped uniqueness, slug history, and transliteration.

## Features

- Auto-generates slugs on `creating` and (optionally) `updating` events
- Configurable source field(s), separator, max length, and uniqueness per model
- Word-boundary-aware truncation
- Scoped uniqueness (e.g. same slug allowed across different tenants or categories)
- Transliteration of non-ASCII characters: é→e, ü→u, ñ→n
- Optional slug history table for tracking retired slugs
- Automatic 301 redirects from old slugs to current URLs via middleware
- PHP 8.2+ and Laravel 11 / 12

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0

The PHP `intl` extension is recommended for best transliteration results (falls back to `iconv` then a simple strip).

## Installation

```bash
composer require philiprehberger/laravel-slug-generator
```

The service provider is auto-discovered via Laravel's package discovery. No manual registration is required.

### Publish configuration

```bash
php artisan vendor:publish --tag=slug-generator-config
```

This creates `config/slug-generator.php` in your application.

### Publish and run the slug history migration (optional)

Only required if you intend to use the `HasSlugHistory` trait.

```bash
php artisan vendor:publish --tag=slug-generator-migrations
php artisan migrate
```

## Basic Usage

Add the `HasSlug` trait to any Eloquent model:

```php
use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

class Post extends Model
{
    use HasSlug;
}
```

The trait reads from the `title` column by default and writes to `slug`. No further configuration is needed for the common case:

```php
$post = Post::create(['title' => 'Hello World']);
echo $post->slug; // 'hello-world'
```

Duplicate slugs automatically receive a numeric suffix:

```php
Post::create(['title' => 'Hello World']); // slug: 'hello-world'
Post::create(['title' => 'Hello World']); // slug: 'hello-world-2'
Post::create(['title' => 'Hello World']); // slug: 'hello-world-3'
```

## Configuration

### Configuration file (`config/slug-generator.php`)

```php
return [
    'separator'       => '-',
    'max_length'      => null,
    'unique'          => true,
    'on_update'       => false,
    'history'         => [
        'enabled' => false,
        'table'   => 'slug_history',
    ],
    'transliteration' => true,
];
```

### Per-model overrides

Every configuration option can be overridden on a per-model basis by adding the corresponding method to your model class. All overrides are optional.

```php
class Article extends Model
{
    use HasSlug;

    // Source field(s) — single field or array of fields
    public function slugSource(): string|array
    {
        return 'title'; // or ['first_name', 'last_name']
    }

    // Database column that stores the slug
    public function slugField(): string
    {
        return 'slug';
    }

    // Word separator
    public function slugSeparator(): string
    {
        return '-';
    }

    // Max slug length — truncates at word boundary; null for no limit
    public function slugMaxLength(): ?int
    {
        return null;
    }

    // Whether slugs must be unique
    public function slugShouldBeUnique(): bool
    {
        return true;
    }

    // Scope column for uniqueness checks (e.g. 'tenant_id', 'category_id')
    public function slugUniqueScope(): ?string
    {
        return null;
    }

    // Whether to regenerate the slug when the model is updated
    public function slugOnUpdate(): bool
    {
        return false;
    }
}
```

## Multiple Source Fields

Build a slug from several fields by returning an array from `slugSource()`:

```php
class Author extends Model
{
    use HasSlug;

    public function slugSource(): array
    {
        return ['first_name', 'last_name'];
    }
}

Author::create(['first_name' => 'John', 'last_name' => 'Doe']);
// slug: 'john-doe'
```

## Scoped Uniqueness

Allow the same slug value in different scopes (e.g. per-category, per-tenant):

```php
class Post extends Model
{
    use HasSlug;

    public function slugUniqueScope(): ?string
    {
        return 'category_id';
    }
}

Post::create(['title' => 'My Post', 'category_id' => 1]); // slug: 'my-post'
Post::create(['title' => 'My Post', 'category_id' => 2]); // slug: 'my-post' (different scope)
Post::create(['title' => 'My Post', 'category_id' => 1]); // slug: 'my-post-2' (same scope)
```

## Max Length with Word-Boundary Truncation

```php
class Post extends Model
{
    use HasSlug;

    public function slugMaxLength(): ?int
    {
        return 20;
    }
}

Post::create(['title' => 'This Is A Very Long Blog Post Title']);
// slug: 'this-is-a-very-long' (never cuts mid-word)
```

## Manual Slug Override

Assign a value to the slug field before saving to bypass auto-generation entirely:

```php
$post = new Post();
$post->title = 'Hello World';
$post->slug  = 'my-custom-slug';
$post->save();

echo $post->slug; // 'my-custom-slug'
```

## Transliteration

Non-ASCII characters are automatically converted to their nearest ASCII equivalents:

```php
Post::create(['title' => 'café résumé']);    // slug: 'cafe-resume'
Post::create(['title' => 'Über den Wolken']); // slug: 'uber-den-wolken'
Post::create(['title' => 'El niño']);         // slug: 'el-nino'
```

Transliteration uses the PHP `intl` extension (`transliterator_transliterate`) when available, falls back to `iconv`, and finally strips non-ASCII bytes as a last resort.

To disable transliteration globally:

```php
// config/slug-generator.php
'transliteration' => false,
```

## Slug History and Redirects

### Enable history

```php
// config/slug-generator.php
'history' => [
    'enabled' => true,
    'table'   => 'slug_history',
],
```

Publish and run the migration (if not already done):

```bash
php artisan vendor:publish --tag=slug-generator-migrations
php artisan migrate
```

### Add HasSlugHistory to your model

```php
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;
use PhilipRehberger\SlugGenerator\Concerns\HasSlugHistory;

class Post extends Model
{
    use HasSlug;
    use HasSlugHistory;

    public function slugOnUpdate(): bool
    {
        return true; // required for slug changes to occur
    }
}
```

Every time the slug changes, the old value is saved to the history table.

### findBySlugOrRedirect

Use this static method in your controller to handle both current and old slugs transparently:

```php
public function show(string $slug): Response
{
    $result = Post::findBySlugOrRedirect($slug);

    if ($result === null) {
        abort(404);
    }

    if (is_array($result) && $result['redirect']) {
        return redirect(route('posts.show', $result['slug']), 301);
    }

    return view('posts.show', ['post' => $result]);
}
```

### SlugRedirectMiddleware

For route-group-level automatic redirects, register the `slug.redirect` middleware on the relevant routes. The alias is registered automatically by the service provider.

```php
// routes/web.php
Route::middleware([
    'slug.redirect:App\Models\Post,slug,/blog'
])->group(function () {
    Route::get('/blog/{slug}', [PostController::class, 'show'])->name('posts.show');
});
```

Middleware parameters (colon-separated after the alias):

| Position | Name | Description |
|----------|------|-------------|
| 1 | `modelClass` | Fully-qualified model class (must use `HasSlugHistory`) |
| 2 | `routeParam` | Route parameter name that holds the slug (default: `slug`) |
| 3 | `urlPrefix` | URL prefix for the redirect target (default: `/`) |

When the middleware detects a slug in the history table it issues a **301 redirect** to the current canonical URL. If the slug resolves to a live record, the request passes through untouched.

## SlugHistory Model

The `SlugHistory` model is publicly available for direct querying when needed:

```php
use PhilipRehberger\SlugGenerator\SlugHistory;

// All history for a specific post
$post->slugHistories; // MorphMany relationship

// Raw query
SlugHistory::where('sluggable_type', Post::class)
    ->where('slug', 'old-slug')
    ->get();
```

## Service Provider Details

The `SlugGeneratorServiceProvider`:

- Merges the package config so defaults apply even without publishing.
- Binds `SlugService` as a singleton in the container.
- Registers the `slug.redirect` middleware alias on the router.
- Publishes config and migration via `vendor:publish`.

## Testing

```bash
composer install
vendor/bin/phpunit
```

Run with coverage (requires Xdebug):

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

## Code Style

```bash
vendor/bin/pint
```

## Static Analysis

```bash
vendor/bin/phpstan analyse
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).
