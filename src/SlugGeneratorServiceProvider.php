<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use PhilipRehberger\SlugGenerator\Middleware\SlugRedirectMiddleware;

class SlugGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->publishMigrations();
        }

        $this->registerMiddlewareAlias();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/slug-generator.php',
            'slug-generator'
        );

        $this->app->singleton(SlugService::class, fn () => new SlugService());
    }

    /**
     * Publish the package configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/slug-generator.php' => config_path('slug-generator.php'),
        ], 'slug-generator-config');
    }

    /**
     * Publish the slug history migration stub.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/create_slug_history_table.php'
                => database_path('migrations/' . date('Y_m_d_His') . '_create_slug_history_table.php'),
        ], 'slug-generator-migrations');
    }

    /**
     * Register the slug.redirect middleware alias with the router.
     */
    protected function registerMiddlewareAlias(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('slug.redirect', SlugRedirectMiddleware::class);
    }
}
