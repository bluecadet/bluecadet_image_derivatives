<?php

namespace Drupal\bluecadet_image_derivatives\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\image\Entity\ImageStyle;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class ImageDerivatives extends FormBase {

  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * @var QueueWorkerManagerInterface
   */
  protected $queueManager;


  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reset_public_files';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    $queue_num = $derivative_queue->numberOfItems();
    $msg_sing = ':num Item queued.';
    $msg_plur = ':num Items queued.';

    $form['data'] = [
      'message' => [
        '#markup' => \Drupal::translation()->formatPlural($queue_num, $msg_sing, $msg_plur, [':num' => $queue_num]),
      ]
    ];

    $settings = \Drupal::state()->get('bluecadet_image_derivatives.settings', []);

    $bundles = \Drupal::entityManager()->getBundleInfo('media');
    $bundle_options = [];

    foreach ($bundles as $bundle_id => $bundle_data) {
      $bundle_options[$bundle_id] = $bundle_data['label'];
    }

    $form['bundles'] = [
      '#type' => 'select',
      '#title' => 'Media Bundles',
      '#description' => 'Please choose which Media bundles contain Images as their Primary source fields.',
      '#options' => $bundle_options,
      '#multiple' => TRUE,
      '#default_value' => isset($settings['bundles'])? $settings['bundles'] : [],
      '#suffix' => '<hr><br>',
    ];

    $field_map = \Drupal::entityManager()->getFieldMap();

    $entity_ref_fields = [];
    $bundleFields = [];
    foreach ($field_map as $entity_id => $fields) {
      $bundles = \Drupal::entityManager()->getBundleInfo($entity_id);
      foreach ($bundles as $bundle_id => $bundle_name) {
        $fields = \Drupal::entityManager()->getFieldDefinitions($entity_id, $bundle_id);

        foreach ($fields as $field_name => $field_definition) {
          if ($field_definition->getType() == 'entity_reference' &&
              $field_definition->getFieldStorageDefinition()->isBaseField() == FALSE) {

            $field_settings = $field_definition->getSettings();

            if ($field_settings['handler'] == 'default:media') {
              $bundleFields[$entity_id][$bundle_id][$field_name] = $field_definition;
            }
          }
        }
      }
    }

    $styles = ImageStyle::loadMultiple();

    $is_headers = ['Fields'];
    $is_defs = [];
    foreach ($styles as $style_id => $style_def) {
      $is_headers[] = $style_def->label();
      $is_defs[] = [
        'id' => $style_id,
        'name' => $style_def->label()
      ];
    }

    $form['styles'] = [
      '#type' => 'table',
      '#header' => $is_headers,
      '#empty' => t('There are no fields.'),
      '#attributes' => [
        'class' => [''],
      ]
    ];

    foreach ($bundleFields as $entity_id => $bundles) {
      foreach ($bundles as $bundle_id => $fields) {
        foreach ($fields as $field_id => $field_def) {
          $field_compound_id = $entity_id . '.' . $bundle_id . '.' . $field_id;
          $row = [
            'label' => [
              '#markup' => $field_compound_id,
            ]
          ];

          foreach ($is_defs as $is_def) {
            $val = FALSE;
            if (isset($settings['styles'][$field_compound_id]) && isset($settings['styles'][$field_compound_id][$is_def['id']])) {
              $val = $settings['styles'][$field_compound_id][$is_def['id']];
            }
            $row[$is_def['id']] = [
              '#type' => 'checkbox',
              '#title' => $is_def['name'],
              '#title_display' => 'invisible',
              '#default_value' => $val,
            ];
          }


          $form['styles'][$field_compound_id] = $row;
        }
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      'save' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];

    $form['sub_actions'] = [
      '#type' => 'actions',
      '#prefix' => '<hr>',
      'reset' => [
        '#type' => 'submit',
        '#value' => $this->t('Flush & Reset Queue'),
        '#submit' => array([$this, 'reset']),
      ],
      'flush' => [
        '#type' => 'submit',
        '#value' => $this->t('Flush Queue only'),
        '#submit' => array([$this, 'flush']),
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    \Drupal::state()->set('bluecadet_image_derivatives.settings', [
      'bundles' => $values['bundles'],
      'styles' => $values['styles']
    ]);

    drupal_set_message('You have saved the settings.');
  }

  public function reset(array &$form, FormStateInterface $form_state) {
    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    $derivative_queue->deleteQueue();

    _queue_all_images_for_derivatives();
    drupal_set_message('Queue cleared and rest.');
  }

  public function flush(array &$form, FormStateInterface $form_state) {
    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    $derivative_queue->deleteQueue();

    drupal_set_message('Queue cleared.');
  }
}