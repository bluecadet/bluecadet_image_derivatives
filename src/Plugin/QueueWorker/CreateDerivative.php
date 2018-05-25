<?php

namespace Drupal\bluecadet_image_derivatives\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $fid = $data->fid;
    $image_style_id = $data->image_style_id;

    $file = File::load($fid);
    if ($file) {
      $image_style = ImageStyle::load($image_style_id);

      $img_path_uri = $file->getFileUri();

      $image_style_uri = $image_style->buildUri($img_path_uri);

      if (!file_exists($image_style_uri)) {
        $image_style->createDerivative($img_path_uri, $image_style_uri);
      }
    }
  }
}