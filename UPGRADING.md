# Upgrading

All upgrade guides for upgrading to a new major version can be found here.

## Upgrading to 2.0.0

Upgrading to version 2.0.0 of the image library package brings some breaking changes. Please read this guide carefully before upgrading.

#### Changed the blade component prefix

We have added a blade component prefix to the blade components of the image library package. This is to prevent conflicts with other blade components. You will have to change the prefix of the blade components in your views.

```html
<x-image-library-image /> // instead of <x-image />

<x-image-library-picture /> // instead of <x-picture />
```

#### Changed the javascript code that dynamically sets the image width as the sizes attribute of the image tag

Because the way the image width is set as the sizes attribute of the image tag has changed, you will have to add the new script blade component to your layout. This replaces the old inline onload attribute on the picture and img tags. Including this script more than once will not cause any problems or performance issues.

```html
<x-image-library-scripts />
```
