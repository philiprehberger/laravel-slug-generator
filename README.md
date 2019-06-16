# Laravel Slug Generator

[![Tests](https://github.com/philiprehberger/laravel-slug-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-slug-generator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-slug-generator.svg)](https://packagist.org/packages/philiprehberger/laravel-slug-generator)
[![License](https://img.shields.io/github/license/philiprehberger/laravel-slug-generator)](LICENSE)

Automatic slug generation for Eloquent models with scoped uniqueness, slug history, and transliteration.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

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

## Usage

### Basic Usage

Add the `HasSlug` trait to any Eloquent model:

```php
use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

class Post extends Model
{
    use HasSlug;
}
```

The trait reads from the `title` column by default and writes to `slug`:

```php
$post = Post::create(['title' => 'Hello World']);
echo $post->slug; // 'hello-world'
```

Duplicate slugs automatically receive a numeric suffix:

```php
Post::create(['title' => 'Hello World']); // slug: 'hello-world'
Post::create(['title' => 'Hello World']); // slug: 'hello-world-2'
```

### Per-Model Overrides

```php
class Article extends Model
{
    use HasSlug;

    public function slugSource(): string|array  { return 'title'; }
    public function slugField(): string          { return 'slug'; }
    public function slugSeparator(): string      { return '-'; }
    public function slugMaxLength(): ?int        { return null; }
    public function slugShouldBeUnique(): bool   { return true; }
    public function slugUniqueScope(): ?string   { return null; }
    public function slugOnUpdate(): bool         { return false; }
}
```

### Scoped Uniqueness

```php
class Post extends Model
{
    use HasSlug;

    public function slugUniqueScope(): ?string
    {
        return 'category_id';
    }
}
```

### Slug History and Redirects

```php
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;
use PhilipRehberger\SlugGenerator\Concerns\HasSlugHistory;

class Post extends Model
{
    use HasSlug;
    use HasSlugHistory;

    public function slugOnUpdate(): bool { return true; }
}
```

Use `findBySlugOrRedirect()` in controllers to handle current and old slugs transparently:

```php
public function show(string $slug): Response
{
    $result = Post::findBySlugOrRedirect($slug);

    if ($result === null) { abort(404); }

    if (is_array($result) && $result['redirect']) {
        return redirect(route('posts.show', $result['slug']), 301);
    }

    return view('posts.show', ['post' => $result]);
}
```

## API

### HasSlug Trait — Override Methods

| Method | Return Type | Default | Description |
|--------|-------------|---------|-------------|
| `slugSource()` | `string\|array` | `'title'` | Source column(s) to generate slug from |
| `slugField()` | `string` | `'slug'` | Database column to store the slug |
| `slugSeparator()` | `string` | `'-'` | Word separator |
| `slugMaxLength()` | `?int` | `null` | Max length; truncates at word boundary |
| `slugShouldBeUnique()` | `bool` | `true` | Enforce unique slugs |
| `slugUniqueScope()` | `?string` | `null` | Column to scope uniqueness checks |
| `slugOnUpdate()` | `bool` | `false` | Regenerate slug on model update |

### HasSlugHistory Trait

| Method | Description |
|--------|-------------|
| `Post::findBySlugOrRedirect(string $slug)` | Returns model, redirect array, or null |
| `->slugHistories` | MorphMany relationship to slug history records |

### SlugRedirectMiddleware Parameters

| Position | Name | Description |
|----------|------|-------------|
| 1 | `modelClass` | Fully-qualified model class (must use `HasSlugHistory`) |
| 2 | `routeParam` | Route parameter name that holds the slug (default: `slug`) |
| 3 | `urlPrefix` | URL prefix for the redirect target (default: `/`) |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
