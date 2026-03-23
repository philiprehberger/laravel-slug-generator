<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Generate a unique slug for the given model instance.
     *
     * Reads source field(s), slugifies the resulting string, truncates to the
     * configured max length at a word boundary, then — if uniqueness is
     * required — queries the database and appends a numeric suffix as needed.
     */
    public function generate(Model $model): string
    {
        $source = $this->resolveSource($model);
        $separator = method_exists($model, 'slugSeparator')
            ? $model->slugSeparator()
            : config('slug-generator.separator', '-');

        $slug = $this->createSlug($source, $separator);

        $maxLength = method_exists($model, 'slugMaxLength')
            ? $model->slugMaxLength()
            : config('slug-generator.max_length');

        if ($maxLength !== null && $maxLength > 0) {
            $slug = $this->truncateAtWordBoundary($slug, (int) $maxLength, $separator);
        }

        $shouldBeUnique = method_exists($model, 'slugShouldBeUnique')
            ? $model->slugShouldBeUnique()
            : (bool) config('slug-generator.unique', true);

        if ($shouldBeUnique) {
            $slug = $this->makeUnique($slug, $model);
        }

        return $slug;
    }

    /**
     * Resolve the source string from one or more model attributes.
     *
     * When a slug template is defined, placeholders like `{attribute}` are
     * resolved from the model first. Otherwise, multiple fields are
     * concatenated with a space.
     */
    protected function resolveSource(Model $model): string
    {
        $template = method_exists($model, 'slugTemplate')
            ? $model->slugTemplate()
            : null;

        if ($template !== null) {
            return $this->resolveTemplate($template, $model);
        }

        $fields = method_exists($model, 'slugSource')
            ? $model->slugSource()
            : 'title';

        if (is_array($fields)) {
            $parts = array_map(
                fn (string $field) => (string) ($model->getAttribute($field) ?? ''),
                $fields,
            );

            return implode(' ', array_filter($parts, fn (string $p) => $p !== ''));
        }

        return (string) ($model->getAttribute($fields) ?? '');
    }

    /**
     * Resolve a template string by replacing `{attribute}` placeholders with
     * the corresponding model attribute values.
     *
     * Missing or null attributes are replaced with an empty string.
     */
    protected function resolveTemplate(string $template, Model $model): string
    {
        return (string) preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($model): string {
            return (string) ($model->getAttribute($matches[1]) ?? '');
        }, $template);
    }

    /**
     * Convert a plain-text string into a URL-safe slug.
     *
     * When transliteration is enabled non-ASCII characters are converted to
     * their closest ASCII equivalents before the standard slugification step.
     */
    public function createSlug(string $source, string $separator = '-'): string
    {
        $useTransliteration = (bool) config('slug-generator.transliteration', true);

        if ($useTransliteration) {
            $source = $this->transliterate($source);
        }

        return Str::slug($source, $separator);
    }

    /**
     * Transliterate non-ASCII characters to ASCII equivalents.
     *
     * Uses the PHP intl extension (transliterator) when available and falls
     * back to iconv() followed by a simple strip of remaining non-ASCII bytes.
     */
    protected function transliterate(string $value): string
    {
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
            if ($result !== false) {
                return $result;
            }
        }

        if (function_exists('iconv')) {
            $result = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($result !== false) {
                return $result;
            }
        }

        // Last-resort: strip non-ASCII bytes so Str::slug() can work cleanly.
        return preg_replace('/[^\x00-\x7F]/u', '', $value) ?? $value;
    }

    /**
     * Truncate a slug to the given maximum length, keeping whole words.
     */
    protected function truncateAtWordBoundary(string $slug, int $maxLength, string $separator): string
    {
        if (mb_strlen($slug) <= $maxLength) {
            return $slug;
        }

        $truncated = mb_substr($slug, 0, $maxLength);

        // Walk back to the last separator so we do not cut mid-word.
        $lastSep = mb_strrpos($truncated, $separator);

        if ($lastSep !== false && $lastSep > 0) {
            $truncated = mb_substr($truncated, 0, $lastSep);
        }

        return rtrim($truncated, $separator);
    }

    /**
     * Make a slug unique within the model's table (or a configured scope).
     *
     * Queries for existing records that share the same slug prefix, then
     * appends the lowest available numeric suffix: -2, -3, …
     */
    public function makeUnique(string $slug, Model $model): string
    {
        $slugField = method_exists($model, 'slugField')
            ? $model->slugField()
            : 'slug';

        $scopeColumn = method_exists($model, 'slugUniqueScope')
            ? $model->slugUniqueScope()
            : null;

        $separator = method_exists($model, 'slugSeparator')
            ? $model->slugSeparator()
            : config('slug-generator.separator', '-');

        $query = $model->newQueryWithoutScopes()
            ->where(function ($q) use ($slugField, $slug, $separator): void {
                $q->where($slugField, $slug)
                    ->orWhere($slugField, 'like', $slug.$separator.'%');
            });

        // Exclude the current record when updating.
        if ($model->exists && $model->getKey() !== null) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        // Apply optional scope column (e.g. tenant_id, category_id).
        if ($scopeColumn !== null) {
            $query->where($scopeColumn, $model->getAttribute($scopeColumn));
        }

        /** @var array<string> $existingSlugs */
        $existingSlugs = $query->pluck($slugField)->toArray();

        if (! in_array($slug, $existingSlugs, true)) {
            return $slug;
        }

        $suffix = 2;
        do {
            $candidate = $slug.$separator.$suffix;
            $suffix++;
        } while (in_array($candidate, $existingSlugs, true));

        return $candidate;
    }
}
