<?php

namespace Drupal\bluecadet_image_derivatives\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/**
 * Check Public files on CRON run.
 *
 * @QueueWorker(
 *   id = "bcid_create_derivative",
 *   title = @Translation("Create Derivative"),
 *   cron = {"time" = 15}
 * )
 */
class CreateDerivative extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $module_settings = \Drupal::state()->get('bluecadet_image_derivatives.settings', []);

    $fid = $data->fid;
    $image_style_id = $data->image_style_id;

    $file = File::load($fid);

    if ($module_settings['log_activity']) {
      \Drupal::logger('bluecadet_image_derivatives')->debug("Starting item. FID: @fid", [
        '@fid' => $fid,
      ]);
    }

    if ($file) {
      if ($module_settings['log_activity']) {
        \Drupal::logger('bluecadet_image_derivatives')->debug("File found. FID: @fid", [
          '@fid' => $fid,
        ]);
      }

      $image_style = ImageStyle::load($image_style_id);

      $img_path_uri = $file->getFileUri();

      $image_style_uri = $image_style->buildUri($img_path_uri);

      if (!file_exists($image_style_uri)) {

        if ($module_settings['log_activity']) {
          \Drupal::logger('bluecadet_image_derivatives')->debug("Creating derivative. FID: @fid", [
            '@fid' => $fid,
          ]);
        }

        $image_style->createDerivative($img_path_uri, $image_style_uri);

      }
      elseif ($module_settings['log_activity']) {
        \Drupal::logger('bluecadet_image_derivatives')->debug("Derivative Exists. Skipping. FID: @fid", [
          '@fid' => $fid,
        ]);
      }
    }
    else {
      if ($module_settings['log_activity']) {
        \Drupal::logger('bluecadet_image_derivatives')->notice("No file. FID: @fid", [
          '@fid' => $fid,
        ]);
      }
    }
  }

}
