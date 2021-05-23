# Changelog

## Unreleased

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
