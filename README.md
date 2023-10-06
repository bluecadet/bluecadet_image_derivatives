
1.x branch is for media handled without the core Media module.
2.x branch is for media handled WITH the core Media module.
3.x branch is for Drupal 9 and above.

Available on packagist, https://packagist.org/packages/bluecadet/bluecadet_image_derivatives

## Features

This module provides functionality to create image derivatives before they are requested. By default, the image derivatives are created when there is an http request for an image for the first time. When using Drupal in a headless approach, this might not be ideal. In our use case we are rsync-ing the directories to an external machine and need the images there.

You can choose which field and which image derivatives to create and on a cron job all images that match will be queued. Then there is a seprate conr job to run and process the items in the queue. It's recommended to only set the items to be rpocess once a day or more, and run the cron job to process images as frequently as possible for your site.

There is also a form to Process an image by fid 1 at a time.

There is also a table to view the items in the queue.

## Changelog

### 8.x-2.2.0

- Update for Drupal 9 compatability

### 8.x-2.1.0

- Updated dependencies so we can use Composer v2
