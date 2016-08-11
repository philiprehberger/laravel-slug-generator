<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

/**
 * Test model that builds a slug from two source fields.
 *
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $slug
 */
class MultiSourcePost extends Model
{
    use HasSlug;

    protected $table = 'multi_source_posts';

    protected $guarded = [];

    /**
     * @return array<string>
     */
    public function slugSource(): array
    {
        return ['first_name', 'last_name'];
    }
}
