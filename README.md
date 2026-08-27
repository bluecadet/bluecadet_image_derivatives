# Bluecadet Image Derivatives

Creates image derivatives for specific fields and image styles ahead of time (rather than on first request), so they can be rsynced to an external machine in headless Drupal setups.

## Requirements

- Drupal 9 or Drupal 10
- PHP 7.4 or higher

## Versions

### 3.x Branch

- **3.x**: Drupal 9 and 10 support (PHP 7.4+)

<!-- Older/unsupported branches -->
### Older Branches

- **2.x**: Media handled with the core Media module — outdated, do not use.
- **1.x**: Media handled without the core Media module — outdated, do not use.

Available on Packagist: https://packagist.org/packages/bluecadet/bluecadet_image_derivatives

## Includes

- Queues images for derivative generation on a cron job, based on configured fields and image styles
- A separate cron job to process the queue and generate the derivatives
- A form to process a single image by file ID (fid)
- An admin table to view the items currently in the queue

Recommended setup: run the queue-setup cron job once a day (or less), and run the queue-processing cron job as frequently as possible for your site.

## Not using Composer

If you are not using composer, you can delete all unneeded files.

- composer.json

## Using Composer

If you are using composer to manage Drupal modules, make sure you add custom
location for this module to be downloaded to. You must add the installer types
line as well as the location for the module.

```json
  ...
  "installer-types": ["custom-drupal-module"],
  "installer-paths": {
    "web/core": ["type:drupal-core"],
    "web/modules/contrib/{$name}": ["type:drupal-module"],
    "web/modules/custom/{$name}": ["type:custom-drupal-module"],
    "web/profiles/contrib/{$name}": ["type:drupal-profile"],
    "web/themes/contrib/{$name}": ["type:drupal-theme"],
    "drush/contrib/{$name}": ["type:drupal-drush"]
  },
  ...
```

## Testing

This module includes automated tests that run via GitHub Actions against Drupal 9.4.x-10.1.x (see `.github/workflows/drupal-tests-and-standards.yml` for the exact PHP/MariaDB matrix).

### Test Plan

#### Automated Tests (GitHub Actions)

The CI pipeline runs the following for each Drupal version:

1. **PHPCS** - Drupal coding standards validation
2. **DrupalPractice** - Best practices validation
3. **Drupal-Check** - Drupal-aware static analysis
4. **PHPUnit** - automated tests

#### Current coverage

A single functional test (`tests/src/Functional/ImageDerivativesTest.php`) covers the module's admin UI and menu access.

## Changelog

### 8.x-2.2.0

- Update for Drupal 9 compatability

### 8.x-2.1.0

- Updated dependencies so we can use Composer v2

<br>
<br>
<br>

## Proudly developed @ Bluecadet

<p style="background-color: white; padding: 20px">
  <a href="https://www.bluecadet.com/"><img style="max-width: 50%; min-width: 300px; background: white; padding: 20px;" src="https://www.bluecadet.com/wp-content/themes/bluecadet-2018/images/logo/logo-bluecadet-black.svg" alt="Bluecadet"></a>
</p>
