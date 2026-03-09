<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests;

use PhilipRehberger\SlugGenerator\Tests\Models\MaxLengthPost;
use PhilipRehberger\SlugGenerator\Tests\Models\MultiSourcePost;
use PhilipRehberger\SlugGenerator\Tests\Models\Post;
use PhilipRehberger\SlugGenerator\Tests\Models\ScopedPost;
use PhilipRehberger\SlugGenerator\Tests\Models\UpdateablePost;
use PHPUnit\Framework\Attributes\Test;

class SlugGenerationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Basic generation
    // -------------------------------------------------------------------------

    #[Test]
    public function it_auto_generates_a_slug_on_create(): void
    {
        $post = Post::create(['title' => 'Hello World']);

        $this->assertSame('hello-world', $post->slug);
    }

    #[Test]
    public function it_lowercases_and_hyphenates_the_slug(): void
    {
        $post = Post::create(['title' => 'My Awesome Blog Post']);

        $this->assertSame('my-awesome-blog-post', $post->slug);
    }

    #[Test]
    public function it_strips_special_characters(): void
    {
        $post = Post::create(['title' => 'Hello, World! #1']);

        $this->assertSame('hello-world-1', $post->slug);
    }

    // -------------------------------------------------------------------------
    // Uniqueness
    // -------------------------------------------------------------------------

    #[Test]
    public function it_appends_suffix_when_slug_is_not_unique(): void
    {
        Post::create(['title' => 'Hello World']);
        $post2 = Post::create(['title' => 'Hello World']);

        $this->assertSame('hello-world-2', $post2->slug);
    }

    #[Test]
    public function it_increments_suffix_for_multiple_duplicates(): void
    {
        Post::create(['title' => 'Hello World']);
        Post::create(['title' => 'Hello World']);
        $post3 = Post::create(['title' => 'Hello World']);

        $this->assertSame('hello-world-3', $post3->slug);
    }

    #[Test]
    public function it_does_not_collide_with_existing_suffixed_slugs(): void
    {
        // Manually insert a record that already has the -2 suffix.
        Post::create(['title' => 'Foo Bar']);
        Post::forceCreate(['title' => 'Foo Bar', 'slug' => 'foo-bar-2']);

        $post3 = Post::create(['title' => 'Foo Bar']);

        $this->assertSame('foo-bar-3', $post3->slug);
    }

    // -------------------------------------------------------------------------
    // Scoped uniqueness
    // -------------------------------------------------------------------------

    #[Test]
    public function it_allows_same_slug_in_different_scopes(): void
    {
        $post1 = ScopedPost::create(['title' => 'My Post', 'category_id' => 1]);
        $post2 = ScopedPost::create(['title' => 'My Post', 'category_id' => 2]);

        $this->assertSame('my-post', $post1->slug);
        $this->assertSame('my-post', $post2->slug);
    }

    #[Test]
    public function it_appends_suffix_within_the_same_scope(): void
    {
        ScopedPost::create(['title' => 'My Post', 'category_id' => 1]);
        $post2 = ScopedPost::create(['title' => 'My Post', 'category_id' => 1]);

        $this->assertSame('my-post-2', $post2->slug);
    }

    // -------------------------------------------------------------------------
    // Multiple source fields
    // -------------------------------------------------------------------------

    #[Test]
    public function it_concatenates_multiple_source_fields(): void
    {
        $post = MultiSourcePost::create(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertSame('john-doe', $post->slug);
    }

    #[Test]
    public function it_handles_partially_empty_source_fields(): void
    {
        $post = MultiSourcePost::create(['first_name' => 'John', 'last_name' => null]);

        $this->assertSame('john', $post->slug);
    }

    // -------------------------------------------------------------------------
    // Max length with word-boundary truncation
    // -------------------------------------------------------------------------

    #[Test]
    public function it_truncates_slug_at_word_boundary(): void
    {
        // "this-is-a-long" = 14 chars; max is 10.
        // Full slug: "this-is-a-long-title"
        // Truncate to 10 => "this-is-a-" => last separator at pos 9 (before 'l')
        // => "this-is-a"
        $post = MaxLengthPost::create(['title' => 'This Is A Long Title']);

        $this->assertSame('this-is-a', $post->slug);
    }

    #[Test]
    public function it_does_not_truncate_when_slug_fits_within_limit(): void
    {
        $post = MaxLengthPost::create(['title' => 'Short']);

        $this->assertSame('short', $post->slug);
    }

    #[Test]
    public function it_does_not_end_with_separator_after_truncation(): void
    {
        $post = MaxLengthPost::create(['title' => 'Hello World Extra']);

        $slug = $post->slug;

        $this->assertNotSame('-', substr($slug, -1));
        $this->assertLessThanOrEqual(10, strlen($slug));
    }

    // -------------------------------------------------------------------------
    // Manual slug override
    // -------------------------------------------------------------------------

    #[Test]
    public function it_skips_auto_generation_when_slug_is_manually_set(): void
    {
        $post = new Post;
        $post->title = 'Hello World';
        $post->slug = 'my-custom-slug';
        $post->save();

        $this->assertSame('my-custom-slug', $post->slug);
    }

    // -------------------------------------------------------------------------
    // slugOnUpdate behaviour
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_regenerate_slug_on_update_by_default(): void
    {
        $post = Post::create(['title' => 'Original Title']);
        $originalSlug = $post->slug;

        $post->title = 'Updated Title';
        $post->save();

        $this->assertSame($originalSlug, $post->fresh()->slug);
    }

    #[Test]
    public function it_regenerates_slug_on_update_when_slug_on_update_is_true(): void
    {
        $post = UpdateablePost::create(['title' => 'Original Title']);

        $post->title = 'Updated Title';
        $post->save();

        $this->assertSame('updated-title', $post->fresh()->slug);
    }
}
