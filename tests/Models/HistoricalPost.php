<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;
use PhilipRehberger\SlugGenerator\Concerns\HasSlugHistory;

/**
 * Test model that records old slugs in the history table.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 */
class HistoricalPost extends Model
{
    use HasSlug;
    use HasSlugHistory;

    protected $table = 'historical_posts';

    protected $guarded = [];

    public function slugOnUpdate(): bool
    {
        return true;
    }

    public function isSlugHistoryEnabled(): bool
    {
        return true;
    }

    /**
     * Override slugField() to satisfy both traits.
     */
    public function slugField(): string
    {
        return 'slug';
    }
}
