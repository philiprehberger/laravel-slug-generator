# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-09

### Added
- `HasSlug` trait for automatic slug generation on Eloquent models.
- `SlugService` with `generate()`, `createSlug()`, and `makeUnique()` methods.
- Configurable source field(s), separator, max length, and uniqueness via model methods.
- Word-boundary-aware truncation when `slugMaxLength()` is set.
- Scoped uniqueness via `slugUniqueScope()` (e.g. per-tenant or per-category slugs).
- Optional slug regeneration on update via `slugOnUpdate()`.
- Manual slug override: when a slug is explicitly set on the model, auto-generation is skipped.
- Transliteration of non-ASCII characters (é→e, ü→u, ñ→n) using PHP intl extension with iconv fallback.
- `HasSlugHistory` trait that saves superseded slugs to the `slug_history` table.
- `SlugHistory` Eloquent model with polymorphic `sluggable` relationship.
- `findBySlugOrRedirect()` static method for history-aware slug resolution.
- `SlugRedirectMiddleware` for automatic 301 redirects from old slugs to current URLs.
- `SlugGeneratorServiceProvider` with config and migration publishing.
- `slug.redirect` middleware alias registered automatically.
- PHPUnit test suite covering all major behaviours.
- GitHub Actions CI matrix for PHP 8.2, 8.3, 8.4 against Laravel 11 and 12.
- Laravel Pint configuration with strict preset.
- PHPStan level 8 configuration via Larastan.

[Unreleased]: https://github.com/philiprehberger/laravel-slug-generator/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/philiprehberger/laravel-slug-generator/releases/tag/v1.0.0
