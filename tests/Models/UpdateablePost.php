<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

/**
 * Test model that regenerates its slug on every update.
 *
 * @property int    $id
 * @property string $title
 * @property string $slug
 */
class UpdateablePost extends Model
{
    use HasSlug;

    protected $table = 'posts';

    protected $guarded = [];

    public function slugOnUpdate(): bool
    {
        return true;
    }
}
