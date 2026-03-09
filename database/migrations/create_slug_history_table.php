<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the slug_history table used by the HasSlugHistory trait.
 *
 * Each row stores a retired slug value alongside a polymorphic reference to
 * the Eloquent model that formerly used it, plus a timestamp indicating when
 * the slug was superseded.
 *
 * Publish and run this migration with:
 *
 *   php artisan vendor:publish --tag=slug-generator-migrations
 *   php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_history', function (Blueprint $table): void {
            $table->id();

            // Polymorphic relationship columns.
            $table->string('sluggable_type');
            $table->unsignedBigInteger('sluggable_id');

            // The retired slug value.
            $table->string('slug');

            // When the slug was retired (no updated_at — rows are immutable).
            $table->timestamp('created_at')->useCurrent();

            // Index to speed up history lookups by slug value.
            $table->index('slug');

            // Index to speed up loading all history for a single model.
            $table->index(['sluggable_type', 'sluggable_id'], 'slug_history_sluggable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_history');
    }
};
