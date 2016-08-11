<?php

declare(strict_types=1);

namespace PhilipRehberger\SlugGenerator\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * SlugRedirectMiddleware
 *
 * Optional middleware that detects when the current request path contains an
 * old (retired) slug and issues a 301 redirect to the model's current URL.
 *
 * Usage — register globally or on specific route groups:
 *
 *   Route::middleware(['slug.redirect:App\Models\Post,slug,/blog/'])
 *       ->group(function () { ... });
 *
 * Parameters (passed via the middleware string):
 *   1. $modelClass  — Fully-qualified model class that uses HasSlugHistory.
 *   2. $routeParam  — The route parameter name that holds the slug (default: 'slug').
 *   3. $urlPrefix   — URL prefix to prepend when building the redirect target
 *                     (default: '/').
 *
 * The middleware calls `Model::findBySlugOrRedirect($slug)` and redirects
 * only when the result contains `['redirect' => true, ...]`. If the slug
 * resolves to a live record it passes the request through untouched.
 */
class SlugRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): SymfonyResponse $next
     * @param string $modelClass Fully-qualified model class name.
     * @param string $routeParam Route parameter holding the slug value.
     * @param string $urlPrefix URL prefix for the redirect target.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $modelClass = '',
        string $routeParam = 'slug',
        string $urlPrefix = '/',
    ): SymfonyResponse {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return $next($request);
        }

        if (! method_exists($modelClass, 'findBySlugOrRedirect')) {
            return $next($request);
        }

        $slug = $request->route($routeParam);

        if ($slug === null || $slug === '') {
            return $next($request);
        }

        /** @var mixed $result */
        $result = $modelClass::findBySlugOrRedirect((string) $slug);

        if (
            is_array($result)
            && isset($result['redirect'])
            && $result['redirect'] === true
            && isset($result['slug'])
        ) {
            $prefix = rtrim($urlPrefix, '/');
            $newSlug = ltrim((string) $result['slug'], '/');
            $redirectUrl = $prefix.'/'.$newSlug;

            return redirect($redirectUrl, Response::HTTP_MOVED_PERMANENTLY);
        }

        return $next($request);
    }
}
