<?php

return [

  'version' => '0.1.0',

  'nodeinfo' => [
    'url' => config('app.url') . '/' . 'api/nodeinfo/2.0.json'
  ],

  'memory_limit' => '1024M',

  'restricted_names' => [
    'reserved_routes' => true,
    'use_blacklist' => false
  ],
  
];