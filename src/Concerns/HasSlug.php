<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Concerns;

use PhilipRehberger\SlugGenerator\SlugService;

/**
 * HasSlug
 *
 * Add this trait to any Eloquent model to enable automatic slug generation.
 * The trait hooks into the `creating` and (optionally) `updating` Eloquent
 * events and delegates all generation logic to {@see SlugService}.
 *
 * Customise behaviour by overriding the configuration methods below in your
 * model class. All overrides are optional — sensible defaults are provided.
 *
 * @example
 *   class Post extends Model
 *   {
 *       use HasSlug;
 *
 *       public function slugSource(): string { return 'title'; }
 *   }
 */
trait HasSlug
{
    /**
     * Boot the trait: register model event listeners.
     */
    public static function bootHasSlug(): void
    {
        static::creating(function (self $model): void {
            $model->generateSlugIfNeeded(isUpdate: false);
        });

        static::updating(function (self $model): void {
            $model->generateSlugIfNeeded(isUpdate: true);
        });
    }

    /**
     * Conditionally generate and assign the slug.
     *
     * Generation is skipped when:
     *   - A non-null slug has already been set manually on the model, AND
     *     that value differs from what auto-generation would produce for the
     *     current dirty state (i.e. the caller provided an explicit slug).
     *   - It is an update and `slugOnUpdate()` returns false.
     */
    protected function generateSlugIfNeeded(bool $isUpdate): void
    {
        $slugField = $this->slugField();

        if ($isUpdate && ! $this->slugOnUpdate()) {
            return;
        }

        // Respect manually set slugs: if the slug field contains a non-empty
        // value that the model has set explicitly (isDirty for the slug column
        // but NOT triggered by this trait), honour it and skip generation.
        if ($this->isManuallySetSlug($slugField)) {
            return;
        }

        /** @var SlugService $service */
        $service = app(SlugService::class);

        $this->setAttribute($slugField, $service->generate($this));
    }

    /**
     * Determine whether the current slug value was set manually by the caller.
     *
     * A slug is considered "manually set" when:
     *  1. The slug field is dirty (has changed from its original value), AND
     *  2. The new value is non-empty, AND
     *  3. The change was not initiated by this trait (we check the original vs
     *     current raw model attribute to detect external writes).
     *
     * On `creating`, models have no original, so we check whether the attribute
     * was set before the event fired — specifically, if the slug is non-null
     * and non-empty and the source field(s) have not changed (i.e. only the
     * slug was touched), we treat it as a manual override.
     */
    protected function isManuallySetSlug(string $slugField): bool
    {
        $currentSlug = $this->getRawOriginal($slugField);
        $newSlug = $this->getAttribute($slugField);

        // For new records: if a non-empty slug is already in the attribute bag
        // it was placed there by the caller before saving.
        if (! $this->exists) {
            return $newSlug !== null && $newSlug !== '';
        }

        // For existing records on update: the slug is manual if the slug field
        // itself is dirty (changed) but the source field(s) are not dirty.
        if ($this->isDirty($slugField) && $newSlug !== null && $newSlug !== '') {
            $sourceFields = (array) $this->slugSource();
            $sourceIsDirty = false;
            foreach ($sourceFields as $field) {
                if ($this->isDirty($field)) {
                    $sourceIsDirty = true;
                    break;
                }
            }

            // Slug changed but source did not — caller explicitly set the slug.
            if (! $sourceIsDirty) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Configuration methods — override in your model to customise behaviour.
    // -------------------------------------------------------------------------

    /**
     * The model attribute(s) used as the slug source.
     *
     * Return a single field name (string) or multiple field names (array).
     * When an array is given, the values are joined with a space before
     * slugification.
     *
     * @return string|array<string>
     */
    public function slugSource(): string|array
    {
        return 'title';
    }

    /**
     * The database column that stores the slug.
     */
    public function slugField(): string
    {
        return 'slug';
    }

    /**
     * The separator inserted between words in the generated slug.
     */
    public function slugSeparator(): string
    {
        return config('slug-generator.separator', '-');
    }

    /**
     * The maximum byte length of the generated slug.
     *
     * When set, truncation respects word boundaries — the slug will never be
     * cut in the middle of a word. Return null for unlimited length.
     */
    public function slugMaxLength(): ?int
    {
        $value = config('slug-generator.max_length');

        return $value !== null ? (int) $value : null;
    }

    /**
     * Whether the slug must be unique within the model's table.
     *
     * When true, duplicate slugs receive a numeric suffix: -2, -3, etc.
     */
    public function slugShouldBeUnique(): bool
    {
        return (bool) config('slug-generator.unique', true);
    }

    /**
     * An optional column name used to scope uniqueness checks.
     *
     * When set, two records may share the same slug as long as they have
     * different values in this column (e.g. tenant_id, category_id).
     *
     * Return null to disable scoped uniqueness.
     */
    public function slugUniqueScope(): ?string
    {
        return null;
    }

    /**
     * A template pattern for building the slug source string.
     *
     * Placeholders like `{title}` or `{first_name}` are resolved from model
     * attributes before slugification. When null, the standard `slugSource()`
     * behaviour is used instead.
     *
     * @example '{last_name}-{first_name}'
     * @example '{title}-{id}'
     */
    public function slugTemplate(): ?string
    {
        return null;
    }

    /**
     * Whether the slug should be regenerated when the model is updated.
     *
     * When false (default), the slug is generated once at creation and is
     * never automatically overwritten.
     */
    public function slugOnUpdate(): bool
    {
        return (bool) config('slug-generator.on_update', false);
    }
}
