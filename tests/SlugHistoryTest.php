<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests;

use PHPUnit\Framework\Attributes\Test;
use PhilipRehberger\SlugGenerator\SlugHistory;
use PhilipRehberger\SlugGenerator\Tests\Models\HistoricalPost;

class SlugHistoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // History recording
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_record_history_on_initial_creation(): void
    {
        HistoricalPost::create(['title' => 'Original Title']);

        $this->assertDatabaseCount('slug_history', 0);
    }

    #[Test]
    public function it_records_old_slug_when_slug_changes_on_update(): void
    {
        $post = HistoricalPost::create(['title' => 'Original Title']);
        $originalSlug = $post->slug;

        $post->title = 'Updated Title';
        $post->save();

        $this->assertDatabaseHas('slug_history', [
            'sluggable_type' => HistoricalPost::class,
            'sluggable_id'   => $post->id,
            'slug'           => $originalSlug,
        ]);
    }

    #[Test]
    public function it_records_multiple_historical_slugs(): void
    {
        $post = HistoricalPost::create(['title' => 'First Title']);
        $slug1 = $post->slug;

        $post->title = 'Second Title';
        $post->save();
        $slug2 = $post->slug;

        $post->title = 'Third Title';
        $post->save();

        $this->assertDatabaseHas('slug_history', ['slug' => $slug1]);
        $this->assertDatabaseHas('slug_history', ['slug' => $slug2]);
        $this->assertDatabaseCount('slug_history', 2);
    }

    #[Test]
    public function it_does_not_record_history_when_slug_does_not_change(): void
    {
        $post = HistoricalPost::create(['title' => 'My Post']);

        // Update a field that does not affect the slug (no on-update slug change
        // will occur because the slug is the same). We force this by directly
        // touching the record without changing the title.
        $post->touch();

        $this->assertDatabaseCount('slug_history', 0);
    }

    #[Test]
    public function slug_histories_relation_returns_correct_records(): void
    {
        $post = HistoricalPost::create(['title' => 'Relation Test']);

        $post->title = 'Relation Test Updated';
        $post->save();

        $this->assertCount(1, $post->slugHistories);
        $this->assertSame('relation-test', $post->slugHistories->first()->slug);
    }

    // -------------------------------------------------------------------------
    // findBySlugOrRedirect
    // -------------------------------------------------------------------------

    #[Test]
    public function find_by_slug_or_redirect_returns_model_for_current_slug(): void
    {
        $post = HistoricalPost::create(['title' => 'My Post']);

        $result = HistoricalPost::findBySlugOrRedirect('my-post');

        $this->assertInstanceOf(HistoricalPost::class, $result);
        $this->assertSame($post->id, $result->id);
    }

    #[Test]
    public function find_by_slug_or_redirect_returns_redirect_array_for_old_slug(): void
    {
        $post = HistoricalPost::create(['title' => 'Old Title']);
        $oldSlug = $post->slug; // 'old-title'

        $post->title = 'New Title';
        $post->save();

        $result = HistoricalPost::findBySlugOrRedirect($oldSlug);

        $this->assertIsArray($result);
        $this->assertTrue($result['redirect']);
        $this->assertSame('new-title', $result['slug']);
        $this->assertInstanceOf(HistoricalPost::class, $result['model']);
        $this->assertSame($post->id, $result['model']->id);
    }

    #[Test]
    public function find_by_slug_or_redirect_returns_null_for_unknown_slug(): void
    {
        $result = HistoricalPost::findBySlugOrRedirect('does-not-exist');

        $this->assertNull($result);
    }

    #[Test]
    public function slug_history_model_stores_created_at_timestamp(): void
    {
        $post = HistoricalPost::create(['title' => 'Timestamp Test']);

        $post->title = 'Timestamp Updated';
        $post->save();

        /** @var SlugHistory $history */
        $history = SlugHistory::first();

        $this->assertNotNull($history->created_at);
    }
}
