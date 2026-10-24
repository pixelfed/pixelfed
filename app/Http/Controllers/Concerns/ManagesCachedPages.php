<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Page;

trait ManagesCachedPages
{
    /**
     * Fetch an active CMS Page as a plain array suitable for caching.
     *
     * Returning an array (rather than the Eloquent model) avoids caching a
     * model instance, which can deserialize into a __PHP_Incomplete_Class and
     * throw "attempt to access a property on an incomplete object".
     *
     * @return array{title: ?string, content: ?string, created_at: ?string}|null
     */
    protected function cachedPage(string $slug): ?array
    {
        $page = Page::whereSlug($slug)->whereActive(true)->first();

        if (! $page) {
            return null;
        }

        return [
            'title' => $page->title,
            'content' => $page->content,
            'created_at' => $page->created_at?->format('M d, Y'),
        ];
    }
}
