<?php

return [

    'api' => [
      'base'        => config('app.url').'/api/1/',
      'sharedInbox' => config('app.url').'/api/sharedInbox',
      'search'      => config('app.url').env('MIX_API_SEARCH'),
    ],

    'hashtag' => [
      'base'   => config('app.url').'/discover/tags/',
      'search' => config('app.url').'/discover/tags/',
    ],

];
