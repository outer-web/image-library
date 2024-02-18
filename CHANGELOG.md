# Changelog

All notable changes to `image library` will be documented in this file.

## 1.2.0 - 2024-02-19

### Added

- Added `Outerweb\ImageLibrary\Facades\ImageLibrary::isSpatieTranslatable()` method to check the value of the config variable `spatie_translatable`.

## 1.1.0 - 2024-02-19

### Changed

- Image blade component will now fallback to the original image if the requested conversion is not (yet) available.
- Config variable spatie_translatable is now set to false by default.

## 1.0.0 - 2024-02-15

- Initial release
