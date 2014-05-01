<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

/**
 * Test model that scopes uniqueness by `category_id`.
 *
 * @property int         $id
 * @property string      $title
 * @property string      $slug
 * @property int|null    $category_id
 */
class ScopedPost extends Model
{
    use HasSlug;

    protected $table = 'scoped_posts';

    protected $guarded = [];

    public function slugUniqueScope(): ?string
    {
        return 'category_id';
    }
}
