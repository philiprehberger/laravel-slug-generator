<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

/**
 * Test model with a fixed max slug length.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 */
class MaxLengthPost extends Model
{
    use HasSlug;

    protected $table = 'posts';

    protected $guarded = [];

    public function slugMaxLength(): ?int
    {
        return 10;
    }
}
