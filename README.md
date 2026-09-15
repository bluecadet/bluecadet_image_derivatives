# Bluecadet Image Derivatives

Creates image derivatives for specific fields and image styles ahead of time (rather than on first request), so they can be rsynced to an external machine in headless Drupal setups.

## Requirements

- Drupal 10.5+ or Drupal 11.2+
- PHP 8.2 or higher

## Versions

### 3.x Branch

- **3.x**: Drupal 10.5+/11.2+ support (PHP 8.2+)

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

This module includes automated tests that run via GitHub Actions against Drupal 10.5.x-11.3.x (see `.github/drupal-ci.yml` for the exact PHP/MariaDB matrix).

### Test Plan

#### Automated Tests (GitHub Actions)

The CI pipeline runs the following for each Drupal version:

1. **PHPCS** - Drupal coding standards validation (`Drupal` and `DrupalPractice` standards)
2. **PHPStan** - static analysis for deprecated API usage
3. **PHPUnit** - automated tests

#### Current coverage

A functional test (`tests/src/Functional/ImageDerivativesTest.php`) confirms the module installs. Kernel tests cover `hook_update_status_alter()` and the settings-explosion helper (`bluecadet_image_derivatives__explode_derivative_settings()`). The queue-processing logic itself (`bluecadet_image_derivatives__queue_all_images_for_derivatives()`, `bluecadet_image_derivatives__process_dervs()`, `CreateDerivative`) is not yet covered.

## Changelog

### 3.x

- Added Drupal 11 compatibility (`drupal/core: ^10.5 || ^11.2`, PHP 8.2+); dropped Drupal 9 support
- Adopted the reusable GitHub Actions workflow architecture; moved CI to a shared, config-driven orchestrator in `bluecadet/web-gh-actions`
- **Fixed several real bugs surfaced by running PHPStan against this module for the first time:**
  - `watchdog_exception()` and `file_validate_is_image()` are both removed in Drupal 11 -- every `catch` block calling `watchdog_exception()` would itself fatal on D11 the first time an exception was actually caught. Replaced with `Drupal\Core\Utility\Error::logException()` and the `image.factory` service, respectively.
  - Two `catch` clauses (`RequeueException`, `SuspendQueueException`) were missing their `use` imports, meaning they were checking against a nonexistent class -- any exception thrown during queue processing would have fatal'd with "class not found" instead of falling through to the generic handler.
  - `bluecadet_image_derivatives__process_dervs()` (runs on every entity insert/update matching configured settings) read `$field_value->target_id` instead of `$field_value['target_id']` -- silently nulled every file ID, meaning derivative queuing via entity save has likely never actually worked.
  - `CreateDerivative` (the queue worker) converted from `\Drupal::` static calls to real constructor-based dependency injection.
- Fixed several postcss plugins that were silently relying on an old transitive dependency rather than being declared directly; updated `@bluecadet/drops` to `^1.2.1`
- Added Kernel test coverage for `hook_update_status_alter()` and the settings-explosion helper function

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
