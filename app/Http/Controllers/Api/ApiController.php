<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function json($res, $headers = [], $code = 200)
    {
        return response()->json($res, $code, $this->filterHeaders($headers), JSON_UNESCAPED_SLASHES);
    }

    public function linksForCollection($paginator)
    {
        $link = null;

        if ($paginator->onFirstPage()) {
            if ($paginator->hasMorePages()) {
                $link = '<'.$paginator->nextPageUrl().'>; rel="prev"';
            }
        } else {
            if ($paginator->previousPageUrl()) {
                $link = '<'.$paginator->previousPageUrl().'>; rel="next"';
            }

            if ($paginator->hasMorePages()) {
                $link .= ($link ? ', ' : '').'<'.$paginator->nextPageUrl().'>; rel="prev"';
            }
        }

        return $link;
    }

    private function filterHeaders($headers)
    {
        return array_filter($headers, function ($v, $k) {
            return $v != null;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
