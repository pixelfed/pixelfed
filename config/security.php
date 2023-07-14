<?php

return [
    'url' => [
        'verify_dns' => env('PF_SECURITY_URL_VERIFY_DNS', false),

        'trusted_domains' => env('PF_SECURITY_URL_TRUSTED_DOMAINS', 'pixelfed.social,pixelfed.art,mastodon.social'),
    ]
];
