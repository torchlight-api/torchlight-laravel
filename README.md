# Laravel Torchlight Client

[![Tests](https://github.com/torchlight-api/torchlight-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/torchlight-api/torchlight-laravel/actions/workflows/tests.yml) [![Latest Stable Version](https://poser.pugx.org/torchlight/torchlight-laravel/v)](//packagist.org/packages/torchlight/torchlight-laravel) [![Total Downloads](https://poser.pugx.org/torchlight/torchlight-laravel/downloads)](//packagist.org/packages/torchlight/torchlight-laravel) [![License](https://poser.pugx.org/torchlight/torchlight-laravel/license)](//packagist.org/packages/torchlight/torchlight-laravel)


A [Torchlight](https://torchlight.dev) syntax highlighting extension for the [Laravel](https://laravel.com/) framework.

Torchlight is a VS Code-compatible syntax highlighter that requires no JavaScript, supports every language, every VS Code theme, line highlighting, git diffing, and more.

## Installation

To install, require the package from composer:

```
composer require torchlight/torchlight-laravel
```

## Configuration

Once the package is downloaded, you can run the following command to publish your configuration file:

```
php artisan torchlight:install
```

Once run, you should see a new file `torchlight.php` in you `config` folder, with contents that look like this:

```php
<?php
return [
    // The Torchlight client caches highlighted code blocks. Here
    // you can define which cache driver you'd like to use.
    'cache' => env('TORCHLIGHT_CACHE_DRIVER'),

    // Which theme you want to use. You can find all of the themes at
    // https://torchlight.dev/themes, or you can provide your own.
    'theme' => env('TORCHLIGHT_THEME', 'material-theme-palenight'),

    // Your API token from torchlight.dev.
    'token' => env('TORCHLIGHT_TOKEN'),

    // If you want to register the blade directives, set this to true.
    'blade_components' => true,

    // The Host of the API.
    'host' => env('TORCHLIGHT_HOST', 'https://api.torchlight.dev'),
];

```
### Cache

Set the cache driver that Torchlight will use.

### Theme

You can change the theme of all your code blocks by adjusting the `theme` key in your configuration.

### Token

This is your API token from [torchlight.dev](https://torchlight.dev). (Torchlight is completely free for personal and open source projects.)

### Blade Components

By default Torchlight works by using a [custom Laravel component](https://laravel.com/docs/master/blade#components). If you'd like to disable the registration of the component for whatever reason, you can turn this to false.

### Host

You can change the host where your API requests are sent. Not sure why you'd ever want to do that, but you can!