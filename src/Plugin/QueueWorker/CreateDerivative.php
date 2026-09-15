<?php

namespace Drupal\bluecadet_image_derivatives\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check Public files on CRON run.
 *
 * @QueueWorker(
 *   id = "bcid_create_derivative",
 *   title = @Translation("Create Derivative"),
 *   cron = {"time" = 15}
 * )
 */
class CreateDerivative extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, StateInterface $state, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->logger = $logger_factory->get('bluecadet_image_derivatives');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $module_settings = $this->state->get('bluecadet_image_derivatives.settings', []);

    $fid = $data->fid;
    $image_style_id = $data->image_style_id;

    $file = $this->entityTypeManager->getStorage('file')->load($fid);

    if ($module_settings['log_activity']) {
      $this->logger->debug("Starting item. FID: @fid", [
        '@fid' => $fid,
      ]);
    }

    if ($file) {
      if ($module_settings['log_activity']) {
        $this->logger->debug("File found. FID: @fid", [
          '@fid' => $fid,
        ]);
      }

      $image_style = $this->entityTypeManager->getStorage('image_style')->load($image_style_id);

      $img_path_uri = $file->getFileUri();

      $image_style_uri = $image_style->buildUri($img_path_uri);

      if (!file_exists($image_style_uri)) {

        if ($module_settings['log_activity']) {
          $this->logger->debug("Creating derivative. FID: @fid", [
            '@fid' => $fid,
          ]);
        }

        $image_style->createDerivative($img_path_uri, $image_style_uri);

      }
      elseif ($module_settings['log_activity']) {
        $this->logger->debug("Derivative Exists. Skipping. FID: @fid", [
          '@fid' => $fid,
        ]);
      }
    }
    else {
      if ($module_settings['log_activity']) {
        $this->logger->notice("No file. FID: @fid", [
          '@fid' => $fid,
        ]);
      }
    }
  }

}
