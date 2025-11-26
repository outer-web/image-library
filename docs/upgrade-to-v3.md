# Upgrade Guide

## Upgrading to version 3.0.0

> ⚠️ **Caution:** This is a complete rewrite of the package and logic. Please review the changes carefully and back up your database and filesystem before proceeding.

### Prerequisites

Before starting the upgrade process:

1. **Create a full backup** of your database and file storage
2. **Test the upgrade process** in a staging environment first
3. **Review your custom code** that uses the image library to understand required changes

### Step 1: Update the image-library config file

The config file has been completely rewritten. You must re-publish it:

```bash
php artisan vendor:publish --tag=image-library-config --force
```

### Step 2: Run the upgrade command

The package includes an automated upgrade command to migrate your existing data:

```bash
php artisan image-library:upgrade
```

The upgrade command will walk you through the following 9 steps:

1. **Check if an upgrade is needed** - Looks for existing images table migrations
2. **Create source_images table migration** - New table for storing source image files
3. **Create new images table migration** - Updated schema for the images table
4. **Create pre-upgrade migration** - Renames existing `images` table to `tmp_images` (see [Pre-upgrade Migration Details](#pre-upgrade-migration-details))
5. **Create post-upgrade migration** - Migrates data from old to new structure (see [Post-upgrade Migration Details](#post-upgrade-migration-details))
6. **Run migrations** - Prompts to execute the new migrations
7. **Manual data migration prompt** - Pauses for you to migrate custom model relationships (see [Migrating Custom Model Relationships](#migrating-custom-model-relationships))
8. **Cleanup old data** - Offers to create cleanup migration (see [Cleanup Migration Details](#cleanup-migration-details))
9. **Run cleanup migration** - Optionally executes the cleanup to remove old data

## Detailed Migration Information

### Pre-upgrade Migration Details

The pre-upgrade migration (`pre_image_library_upgrade.php`) performs a simple but critical step:

-   **Renames the existing `images` table to `tmp_images`**
-   This preserves all your existing image data during the upgrade process
-   The old data remains accessible for mapping to the new structure

### Post-upgrade Migration Details

The post-upgrade migration (`post_image_library_upgrade.php`) handles the complex data migration:

-   **Reads each record from the `tmp_images` table**
-   **Creates corresponding `SourceImage` records** with the same UUID
-   **Uploads image files to the new storage structure** while preserving UUIDs
-   **Maintains file integrity** by copying files from old to new locations

This migration ensures that:

-   All existing image files are preserved
-   UUIDs remain consistent for data mapping
-   Files are properly structured in the new system

### Migrating Custom Model Relationships

After the automated migration completes, you need to update your models and relationships manually.

Follow the configuration steps in the [README](README.md).

#### Migrate existing image relationships

Use the provided mapping query to connect old images to new structure:

```php
// Get the ID mapping between old and new images
$mapping = DB::table('tmp_images')
    ->join('source_images', 'tmp_images.uuid', '=', 'source_images.uuid')
    ->pluck('source_images.id', 'tmp_images.id');

// For each of your models, attach the images using the new system
foreach (YourModel::cursor() as $model) {
    // Replace 'old_image_id' with your actual column name
    $oldImageId = $model->old_image_id;

    if (isset($mapping[$oldImageId])) {
        $sourceImage = SourceImage::find($mapping[$oldImageId]);

        $model->attachImage($sourceImage, [
            'context' => 'your-context-key', // Replace with your context
            // Add any other attributes you need
        ]);
    }
}
```

### Cleanup Migration Details

The cleanup migration (`cleanup_image_library_upgrade.php`) performs final cleanup:

-   **Deletes old image files** from storage (`{uuid}/original.{extension}` format)
-   **Drops the `tmp_images` table** (your backup of the original data)
-   **Drops the `image_conversions` table** if it exists from the old system

> ⚠️ **Warning:** This migration is **not reversible**. Ensure everything works correctly before running cleanup.

#### Manual verification before cleanup

Before running the cleanup, verify your migration was successful:

1. Check that all your models can access their images correctly
2. Verify image display in your application works as expected
3. Test image upload and manipulation functionality
