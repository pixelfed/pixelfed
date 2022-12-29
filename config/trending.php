<?php

return [
    'hashtags' => [
        'ttl' => env('PF_HASHTAGS_TRENDING_TTL', 43200),
        'recency_mins' => env('PF_HASHTAGS_TRENDING_RECENCY_MINS', 20160),
        'limit' => env('PF_HASHTAGS_TRENDING_LIMIT', 20)
    ]
];
