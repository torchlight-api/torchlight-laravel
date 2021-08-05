# Changelog

## Unreleased

### Changed

- Post-processors don't run if Laravel is compiling views.

### Added

- You can set `tab_width` to `false` to output literal tabs into the rendered HTML.

## 0.5.2 - 2021-08-02

### Fixed
- Replace tabs with spaces in code before it's sent to the API.

## 0.5.1 - 2021-08-01

### Added
- Added support for Laravel Livewire ([#10](https://github.com/torchlight-api/torchlight-laravel/pull/10))
- Added post-processors to allow your app to hook into the rendered response before it's sent to the browser.

## 0.5.0 - 2021-07-31

### Changed
- Changed the signature for the Manager class. Remove the requirement for the container to be passed in.

## 0.4.6 - 2021-07-28

### Added
- Added the ability to send `options` from the config file to the API.

## 0.4.5 - 2021-07-18

### Changed
- The default response (used if a request fails) now includes the `<div class='line'>` wrappers.

## 0.4.4 - 2021-06-16

### Fixed
- Catch `ConnectionException`s in addition to exceptions from the Torchlight API.

## 0.4.3 - 2021-05-25

### Added
- `getConfigUsing` now accepts a plain array in addition to a callback.

## 0.4.2 - 2021-05-24

### Fixed
- Cover a bug in Laravel pre 8.23.0.

## 0.4.1 - 2021-05-23

### Added
- Ability to override the environment.

## 0.4.0 - 2021-05-23

### Added
- `Torchlight::findTorchlightIds` method to search a string and return all Torchlight placeholders.
- `BladeManager::getBlocks`
- `BladeManager::clearBlocks`

### Changed
- Added square brackets around the Torchlight ID in the Block placeholder.
- The BladeManager no longer clears the blocks while rendering. Needed for Jigsaw.

## 0.3.0 - 2021-05-22

- Add `Torchlight` facade
- Add ability to set the cache implementation.
- Add ability to abstract config from Laravel's `config` helper.
- Changed package name from `torchlight/laravel` to `torchlight/torchlight-laravel`


## 0.2.1 - 2021-05-20

- Add `Block::generateIdsUsing`
