<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use PhilipRehberger\SlugGenerator\Concerns\HasSlug;

/**
 * Test model that generates a slug from a template pattern.
 *
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $slug
 */
class TemplatePost extends Model
{
    use HasSlug;

    protected $table = 'multi_source_posts';

    protected $guarded = [];

    public function slugTemplate(): ?string
    {
        return '{last_name}-{first_name}';
    }
}
