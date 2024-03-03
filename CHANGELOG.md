# Changelog

All notable changes to `image library` will be documented in this file.

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
