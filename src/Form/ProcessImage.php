<?php

namespace Drupal\bluecadet_image_derivatives\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class ProcessImage extends FormBase {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager)
  {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
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

    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    // $queue_num = $derivative_queue->numberOfItems();

    $options = [];
    $styles = ImageStyle::loadMultiple();
    foreach ($styles as $style_id => $style_def) {
      $options[$style_id] = $style_def->label();
    }
    $form['fid'] = [
      '#type' => 'number',
      '#title' => 'FID',
      '#description' => 'Type in image FID',
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

    // Validate File exists.
    if ($file = File::load($fid)) {

      // Validate file is an image file.
      $errors = file_validate_is_image($file);
      if(!empty($errors)) {
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
    ksm($values);

    $module_settings = \Drupal::state()->get('bluecadet_image_derivatives.settings', []);

    $queue_worker = \Drupal::service('plugin.manager.queue_worker');
    $derivative_queue_worker = $queue_worker->createInstance('bcid_create_derivative');

    try {
      if ($module_settings['log_activity']) {
        \Drupal::logger('bluecadet_image_derivatives')->debug("Trying to force Image Creation. FID: @fid", [
          '@fid' => $values['fid'],
        ]);
      }
      $data = new \stdClass();
      $data->fid = $values['fid'];
      $data->image_style_id = $values['image_style_id'];

      $derivative_queue_worker->processItem($data);
    } catch (SuspendQueueException $e) {
      watchdog_exception('bluecadet_image_derivatives', $e);
    }
    catch (\Exception $e) {
      watchdog_exception('bluecadet_image_derivatives', $e);
    }

    // drupal_set_message('You have saved the settings.');
  }


  // public function reset(array &$form, FormStateInterface $form_state) {
  //   $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

  //   $derivative_queue->deleteQueue();

  //   _queue_all_images_for_derivatives();
  //   drupal_set_message('Queue cleared and rest.');
  // }

  // public function flush(array &$form, FormStateInterface $form_state) {
  //   $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

  //   $derivative_queue->deleteQueue();

  //   drupal_set_message('Queue cleared.');
  // }

}
