<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use PhilipRehberger\SlugGenerator\Concerns\HasSlugHistory;

/**
 * SlugHistory
 *
 * Stores historical slugs for any model that uses the {@see HasSlugHistory}
 * trait. Each row records the old slug value, the owning model type and id,
 * and the timestamp at which it was superseded.
 *
 * @property int $id
 * @property string $sluggable_type
 * @property int|string $sluggable_id
 * @property string $slug
 * @property Carbon $created_at
 */
class SlugHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'slug_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'sluggable_type',
        'sluggable_id',
        'slug',
    ];

    /**
     * Disable the updated_at timestamp — history rows are immutable.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Manually maintain created_at so we know when the old slug was retired.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The polymorphic relationship back to the owning model.
     *
     * @return MorphTo<Model, SlugHistory>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Override save() to always stamp created_at when inserting.
     *
     * @param array<string, mixed> $options
     */
    public function save(array $options = []): bool
    {
        if (! $this->exists) {
            $this->created_at = now();
        }

        return parent::save($options);
    }
}
