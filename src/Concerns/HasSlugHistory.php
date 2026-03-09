<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PhilipRehberger\SlugGenerator\SlugHistory;

/**
 * HasSlugHistory
 *
 * Optional companion trait for models that already use {@see HasSlug}.
 * When the model's slug changes, the previous value is automatically saved
 * to the `slug_history` table so that old URLs can be redirected.
 *
 * Requires the `slug_history` migration to have been run.
 * Requires `config('slug-generator.history.enabled')` to be `true`, OR the
 * `slugHistoryEnabled()` method to return `true` on the model.
 *
 * @example
 *   class Post extends Model
 *   {
 *       use HasSlug, HasSlugHistory;
 *   }
 */
trait HasSlugHistory
{
    /**
     * Boot the trait: register model event listeners.
     */
    public static function bootHasSlugHistory(): void
    {
        // After an update, record the old slug if it changed.
        static::updated(function (self $model): void {
            if (! $model->isSlugHistoryEnabled()) {
                return;
            }

            $slugField = method_exists($model, 'slugField') ? $model->slugField() : 'slug';

            /** @var string|null $originalSlug */
            $originalSlug = $model->getOriginal($slugField);
            /** @var string|null $newSlug */
            $newSlug = $model->getAttribute($slugField);

            if ($originalSlug !== null && $originalSlug !== '' && $originalSlug !== $newSlug) {
                $model->recordSlugHistory($originalSlug);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Find a model by its current slug, or — if not found — look up the slug
     * history and return redirect information.
     *
     * Return values:
     *  - The model instance when the slug matches the current slug.
     *  - An array `['redirect' => true, 'model' => $model, 'slug' => $currentSlug]`
     *    when the slug exists in history and the owning model is still present.
     *  - null when the slug is unknown.
     *
     * @param  string  $slug
     * @return static|array{redirect: true, model: static, slug: string}|null
     */
    public static function findBySlugOrRedirect(string $slug): static|array|null
    {
        $slugField = (new static())->slugField(); // @phpstan-ignore-line

        /** @var static|null $model */
        $model = static::where($slugField, $slug)->first();

        if ($model !== null) {
            return $model;
        }

        // Look up slug history.
        /** @var SlugHistory|null $history */
        $history = SlugHistory::where('slug', $slug)
            ->where('sluggable_type', static::class)
            ->latest('created_at')
            ->first();

        if ($history === null) {
            return null;
        }

        /** @var static|null $owner */
        $owner = static::find($history->sluggable_id);

        if ($owner === null) {
            return null;
        }

        return [
            'redirect' => true,
            'model'    => $owner,
            'slug'     => (string) $owner->getAttribute($slugField),
        ];
    }

    /**
     * Polymorphic relation — all historical slugs for this model instance.
     *
     * @return MorphMany<SlugHistory>
     */
    public function slugHistories(): MorphMany
    {
        return $this->morphMany(SlugHistory::class, 'sluggable');
    }

    // -------------------------------------------------------------------------
    // Configuration — override in your model if needed.
    // -------------------------------------------------------------------------

    /**
     * Whether slug history recording is active for this model.
     *
     * @return bool
     */
    public function isSlugHistoryEnabled(): bool
    {
        return (bool) config('slug-generator.history.enabled', false);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Persist an old slug value to the history table.
     *
     * @param  string  $oldSlug
     */
    protected function recordSlugHistory(string $oldSlug): void
    {
        SlugHistory::create([
            'sluggable_type' => static::class,
            'sluggable_id'   => $this->getKey(),
            'slug'           => $oldSlug,
        ]);
    }

    /**
     * Proxy for the slugField() method defined in HasSlug.
     * Declared here so static analysis does not complain about an unknown method.
     *
     * @return string
     */
    public function slugField(): string
    {
        return 'slug';
    }
}
