<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use PhilipRehberger\SlugGenerator\SlugGeneratorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Register the package service provider.
     *
     * @param Application $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SlugGeneratorServiceProvider::class,
        ];
    }

    /**
     * Configure the application for tests.
     *
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Create the test tables in the in-memory SQLite database.
     */
    protected function setUpDatabase(): void
    {
        // Main posts table used by most tests.
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->timestamps();
        });

        // Posts table with a category scope for scoped-uniqueness tests.
        Schema::create('scoped_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
        });

        // Posts table sourced from multiple fields.
        Schema::create('multi_source_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('slug')->unique()->nullable();
            $table->timestamps();
        });

        // Posts table for slug history tests.
        Schema::create('historical_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->timestamps();
        });

        // Slug history table.
        Schema::create('slug_history', function (Blueprint $table): void {
            $table->id();
            $table->string('sluggable_type');
            $table->unsignedBigInteger('sluggable_id');
            $table->string('slug');
            $table->timestamp('created_at')->useCurrent();
            $table->index('slug');
            $table->index(['sluggable_type', 'sluggable_id'], 'slug_history_sluggable_index');
        });
    }
}
