<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

/**
 * Standard test model — generates a slug from the `title` field.
 *
 * @property int    $id
 * @property string $title
 * @property string $slug
 */
class Post extends Model
{
    use HasSlug;

    protected $table = 'posts';

    protected $guarded = [];
}
