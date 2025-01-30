# Changelog

All notable changes to `image library` will be documented in this file.

## 2.6.0 - 2025-01-31

### Fixed

- Use with('conversions') to eager load the conversions in the Image model.

## 2.5.0 - 2024-03-27

### Fixed

- Added the $force = true on the ImageConversion observers to force the generation of the conversions. This is intended as the existing conversions should be overridden by the new ones.

## 2.4.2 - 2024-03-12

### Added

- Added support for Laravel 11.

## 2.4.1 - 2024-03-09

### Fixed

- Fixed an issue where `getbasePath` method could return null instead of a string and break the `url` and `path` methods of the Image model.

## 2.4.0 - 2024-03-04

### Added

- Added a `intersectionObserver` to the scripts blade component to be better at dynamically setting the image sizes attribute of the image tag. This is done to fix issues where the image is hidden and becomes visible after a user action. (e.g. a hidden tab that becomes visible after a user clicks on it.)

## 2.3.0 - 2024-03-04

### Added

- Added a `createSync` method to the `ConversionDefinition` entity to inform the image library to dispatch the generateConversion job synchronously. This is done to make the thumbnail generation conversion visible immediately after uploading an image when using a async queue driver.

## 2.2.1 - 2024-03-04

### Changed

- Improved the installation process by utilizing more of the Spatie package install command.

## 2.1.0 - 2024-03-28

### Fixed

- Fixed a bug where the javascript code that dynamically sets the image width as the sizes attribute of the image tag caused an infinite loop because the `load` event listener kept firing.
- Fixed several issues with the rendering of the image and picture tags when the image was set to `NULL`.

## 2.0.0 - 2024-02-26

### Added

- Added `Outerweb\ImageLibrary\Entities\ConversionDefinition::label(string $label)` method to set the label of the conversion. By default, the label will be the name of the conversion.
- Added `Outerweb\ImageLibrary\Entities\ConversionDefinition::translateLabel(bool $doTranslateLabel = true)` method to set whether the label should be translated. By default, the label will not be translated. This method will take the value of the label and put it through the `__()` function.

### Changed

- Changed the javascript code that dynamically sets the image width as the sizes attribute of the image tag. The new code takes into account any rerendering in the browser through a MutationObserver. So when livewire or any javascript library rerenders (a part of) the page, the image width will be recalculated and set as the sizes attribute of the image tag.

### Fixed

- Fixed a bug where the webp variants of the responsive variants did not get deleted when the conversions get regenerated.

## 1.2.0 - 2024-02-19

### Added

- Added `Outerweb\ImageLibrary\Facades\ImageLibrary::isSpatieTranslatable()` method to check the value of the config variable `spatie_translatable`.

## 1.1.0 - 2024-02-19

### Changed

- Image blade component will now fallback to the original image if the requested conversion is not (yet) available.
- Config variable spatie_translatable is now set to false by default.

## 1.0.0 - 2024-02-15

- Initial release
