<?php

namespace Drupal\bluecadet_image_derivatives\Form;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows Admin to choose which image fields and image styles to be processed.
 */
class ImageDerivatives extends FormBase {

  /**
   * QueueFactory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * QueueManager.
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
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info, EntityFieldManager $entity_field_manager) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;

    $this->messenger();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
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
      ],
    ];

    $settings = \Drupal::state()->get('bluecadet_image_derivatives.settings', []);

    $form['log_activity'] = [
      '#type' => 'checkbox',
      '#title' => 'Log Activity',
      '#default_value' => ($settings['log_activity']) ?? FALSE,
    ];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
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
      '#default_value' => ($settings['bundles']) ?? [],
      '#suffix' => '<hr><br>',
    ];

    $bundleFields = $this->buildBundleFields();

    $styles = ImageStyle::loadMultiple();

    $is_headers = ['Fields'];
    $is_defs = [];
    foreach ($styles as $style_id => $style_def) {
      $is_headers[] = $style_def->label();
      $is_defs[] = [
        'id' => $style_id,
        'name' => $style_def->label(),
      ];
    }

    $form['styles'] = [
      '#type' => 'table',
      '#header' => $is_headers,
      '#empty' => t('There are no fields.'),
      '#attributes' => [
        'class' => [''],
      ],
      '#prefix' => '<div class="wide-table-container">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => ['bluecadet_image_derivatives/settings'],
      ],
    ];

    foreach ($bundleFields as $entity_id => $bundles) {
      foreach ($bundles as $bundle_id => $fields) {
        foreach (array_keys($fields) as $field_id) {
          $field_compound_id = $entity_id . '.' . $bundle_id . '.' . $field_id;
          $row = [
            'label' => [
              '#markup' => $field_compound_id,
            ],
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
        '#submit' => [[$this, 'reset']],
      ],
      'flush' => [
        '#type' => 'submit',
        '#value' => $this->t('Flush Queue only'),
        '#submit' => [[$this, 'flush']],
      ],
    ];

    return $form;
  }

  /**
   * Build bundle Fields data structure.
   *
   * @return array
   *   Data structure
   */
  protected function buildBundleFields() {
    $bundleFields = [];
    $field_map = $this->entityFieldManager->getFieldMap();

    foreach ($field_map as $entity_id => $fields) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_id);
      foreach (array_keys($bundles) as $bundle_id) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entity_id, $bundle_id);

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

    return $bundleFields;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    \Drupal::state()->set('bluecadet_image_derivatives.settings', [
      'bundles' => $values['bundles'],
      'styles' => $values['styles'],
      'log_activity' => $values['log_activity'],
    ]);

    $this->messenger->addMessage('You have saved the settings.');
  }

  /**
   * Reset the queue.
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    $derivative_queue->deleteQueue();

    _queue_all_images_for_derivatives();
    $this->messenger->addMessage('Queue cleared and rest.');
  }

  /**
   * Flush the queue.
   */
  public function flush(array &$form, FormStateInterface $form_state) {
    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    $derivative_queue->deleteQueue();

    $this->messenger->addMessage('Queue cleared.');
  }

}
