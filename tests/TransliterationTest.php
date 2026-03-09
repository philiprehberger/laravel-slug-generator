<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests;

use PHPUnit\Framework\Attributes\Test;
use PhilipRehberger\SlugGenerator\SlugService;
use PhilipRehberger\SlugGenerator\Tests\Models\Post;

class TransliterationTest extends TestCase
{
    private SlugService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlugService();
    }

    // -------------------------------------------------------------------------
    // createSlug — unit-level transliteration
    // -------------------------------------------------------------------------

    #[Test]
    public function it_transliterates_accented_latin_characters(): void
    {
        config(['slug-generator.transliteration' => true]);

        $slug = $this->service->createSlug('café résumé');

        // é → e, é → e
        $this->assertSame('cafe-resume', $slug);
    }

    #[Test]
    public function it_transliterates_umlaut_characters(): void
    {
        config(['slug-generator.transliteration' => true]);

        $slug = $this->service->createSlug('Über die Straße');

        $this->assertMatchesRegularExpression('/^uber-die-strasse$|^uber-die-strasse$/', $slug);
    }

    #[Test]
    public function it_transliterates_spanish_characters(): void
    {
        config(['slug-generator.transliteration' => true]);

        $slug = $this->service->createSlug('El niño mañana');

        $this->assertSame('el-nino-manana', $slug);
    }

    #[Test]
    public function it_produces_a_slug_for_mixed_ascii_and_non_ascii(): void
    {
        config(['slug-generator.transliteration' => true]);

        $slug = $this->service->createSlug('Hello Wörld');

        $this->assertSame('hello-world', $slug);
    }

    #[Test]
    public function it_still_slugifies_when_transliteration_is_disabled(): void
    {
        config(['slug-generator.transliteration' => false]);

        $slug = $this->service->createSlug('Hello World');

        $this->assertSame('hello-world', $slug);
    }

    // -------------------------------------------------------------------------
    // End-to-end via model
    // -------------------------------------------------------------------------

    #[Test]
    public function it_transliterates_through_model_on_create(): void
    {
        config(['slug-generator.transliteration' => true]);

        $post = Post::create(['title' => 'Héllo Wörld']);

        // Depending on the available PHP extension the exact output may vary
        // slightly, but it must contain only ASCII-safe characters after slug.
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $post->slug);
        $this->assertStringNotContainsString('é', $post->slug);
        $this->assertStringNotContainsString('ö', $post->slug);
    }
}
