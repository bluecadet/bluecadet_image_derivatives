<?php

namespace Drupal\Tests\bluecadet_image_derivatives\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests update status alter behavior.
 *
 * @group bluecadet_image_derivatives
 */
class UpdateStatusAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'media', 'bluecadet_image_derivatives'];

  /**
   * Tests projects that are not targeted remain unchanged.
   */
  public function testNonTargetProjectUnchanged(): void {
    $projects = [
      'example_module' => [
        'name' => 'example_module',
        'project_type' => 'module',
        'status' => 1,
      ],
    ];

    $expected = $projects;

    bluecadet_image_derivatives_update_status_alter($projects);

    $this->assertSame($expected, $projects);
  }

}
