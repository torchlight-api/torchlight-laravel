<?php

return [
    // The Torchlight client caches highlighted code blocks. Here
    // you can define which cache driver you'd like to use.
    'cache' => env('TORCHLIGHT_CACHE_DRIVER'),

    // Which theme you want to use. You can find all of the themes at
    // https://torchlight.dev/themes, or you can provide your own.
    'theme' => 'material-theme-palenight',

    // Your API token from torchlight.dev.
    'token' => env('TORCHLIGHT_TOKEN'),

    // If you want to register the blade directives, set this to true.
    'blade_components' => true,
];
