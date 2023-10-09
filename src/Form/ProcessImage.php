<?php

namespace Drupal\bluecadet_image_derivatives\Form;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\bluecadet_image_derivatives\DrupalStateTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create Derivatives for a specific image.
 */
class ProcessImage extends FormBase {

  use DrupalStateTrait;
  use LoggerChannelTrait;

  /**
   * QueueFactory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * QueueWorkerManagerInterface.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'process_image';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options = [];

    $image_style_storage = $this->entityTypeManager->getStorage('image_style');
    $styles = $image_style_storage->loadMultiple();

    foreach ($styles as $style_id => $style_def) {
      $options[$style_id] = $style_def->label();
    }
    $form['fid'] = [
      '#type' => 'number',
      '#title' => 'FID',
      '#description' => $this->t('Type in image FID'),
      '#min' => 1,
      '#step' => 1,
      '#size' => 2,
    ];

    $form['image_style_id'] = [
      '#type' => 'select',
      '#title' => 'Image Style',
      '#description' => '',
      '#options' => $options,
      '#attributes' => [
        'class' => [''],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'save' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    $fid = $values['fid'];

    $file_storage = $this->entityTypeManager->getStorage('file');

    // Validate File exists.
    if ($file = $file_storage->load($fid)) {

      // Validate file is an image file.
      $errors = file_validate_is_image($file);
      if (!empty($errors)) {
        $form_state->setErrorByName('fid', "FID: $fid: Is not an image.");
      }
    }
    else {
      $form_state->setErrorByName('fid', "FID: $fid: File does not exist.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $module_settings = $this->drupalState()->get('bluecadet_image_derivatives.settings', []);

    $derivative_queue_worker = $this->queueManager->createInstance('bcid_create_derivative');

    try {
      if ($module_settings['log_activity']) {
        $this->getLogger('bluecadet_image_derivatives')->debug("Trying to force Image Creation. FID: @fid", [
          '@fid' => $values['fid'],
        ]);
      }
      $data = new \stdClass();
      $data->fid = $values['fid'];
      $data->image_style_id = $values['image_style_id'];

      $derivative_queue_worker->processItem($data);
    }
    catch (SuspendQueueException $e) {
      watchdog_exception('bluecadet_image_derivatives', $e);
    }
    catch (\Exception $e) {
      watchdog_exception('bluecadet_image_derivatives', $e);
    }
  }

}
