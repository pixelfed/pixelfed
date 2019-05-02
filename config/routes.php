<?php

return [

    'api' => [
      'base'        => config('app.url').'/api/1/',
      'sharedInbox' => config('app.url').'/api/sharedInbox',
    ],

    'hashtag' => [
      'base'   => config('app.url').'/discover/tags/',
      'search' => config('app.url').'/discover/tags/',
    ],

];
