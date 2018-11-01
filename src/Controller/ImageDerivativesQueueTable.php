<?php

namespace Drupal\bluecadet_image_derivatives\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class ImageDerivativesQueueTable extends ControllerBase {

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
   *
   */
  public function viewTable() {
    $derivative_queue = $this->queueFactory->get('bcid_create_derivative');

    $queue_num = $derivative_queue->numberOfItems();
    $msg_sing = ':num Item queued.';
    $msg_plur = ':num Items queued.';

    $data['data'] = [
      '#markup' => \Drupal::translation()->formatPlural($queue_num, $msg_sing, $msg_plur, [':num' => $queue_num]),
    ];

    $data['items'] = [
      '#type' => 'table',
      '#header' => ['Item ID', 'FID', 'Image Style ID', 'Created'],
      '#rows' => [],
      '#empty' => t('There are no queued items.'),
      '#attributes' => [
        'class' => [''],
      ],
    ];

    $db = Database::getConnection();

    $query = $db->select('queue', 'q');
    $query->fields('q');
    $query->condition('q.name', 'bcid_create_derivative');
    $items = $query->execute()->fetchAll();

    foreach ($items as $item) {
      $item_data = unserialize($item->data);

      $data['items']['#rows'][] = [
        $item->item_id,
        $item_data->fid,
        $item_data->image_style_id,
        $item->created,
      ];
    }

    return $data;
  }

}
