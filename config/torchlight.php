<?php

return [
    'cache' => env('TORCHLIGHT_CACHE_DRIVER', 'default'),

    'bust' => 1,

    'theme' => 'material-theme-palenight',

    'token' => env('TORCHLIGHT_TOKEN'),

    'blade_directives' => true,
];