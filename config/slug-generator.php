<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Separator
    |--------------------------------------------------------------------------
    |
    | The default separator used between words in the generated slug.
    | Common choices are '-' (hyphen) or '_' (underscore).
    |
    */

    'separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Max Length
    |--------------------------------------------------------------------------
    |
    | The maximum length of a generated slug. When set, truncation respects
    | word boundaries — it will not cut in the middle of a word.
    | Set to null for no limit.
    |
    */

    'max_length' => null,

    /*
    |--------------------------------------------------------------------------
    | Unique Slugs
    |--------------------------------------------------------------------------
    |
    | When enabled, the package guarantees that all generated slugs are unique
    | within the model's table (or within a configured scope). Duplicate slugs
    | receive a numeric suffix: -2, -3, etc.
    |
    */

    'unique' => true,

    /*
    |--------------------------------------------------------------------------
    | Regenerate on Update
    |--------------------------------------------------------------------------
    |
    | When enabled, the slug will be regenerated whenever the source field(s)
    | change during an update. When disabled (default), the slug is only
    | generated once at creation time and is never overwritten automatically.
    |
    */

    'on_update' => false,

    /*
    |--------------------------------------------------------------------------
    | Slug History
    |--------------------------------------------------------------------------
    |
    | When history is enabled, any time a model's slug changes the old value
    | is stored in the history table. This allows the middleware to issue
    | 301 redirects from old slugs to the current canonical URL.
    |
    */

    'history' => [
        'enabled' => false,
        'table' => 'slug_history',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transliteration
    |--------------------------------------------------------------------------
    |
    | When enabled, non-ASCII characters are transliterated to their closest
    | ASCII equivalents before slugification. For example: é → e, ü → u, ñ → n.
    | Requires the intl PHP extension for best results.
    |
    */

    'transliteration' => true,

];
