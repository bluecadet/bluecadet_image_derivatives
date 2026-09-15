<?php

namespace Drupal\Tests\bluecadet_image_derivatives\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests bluecadet_image_derivatives__explode_derivative_settings().
 *
 * @group bluecadet_image_derivatives
 */
class ExplodeDerivativeSettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'media', 'bluecadet_image_derivatives'];

  /**
   * Tests a single dotted field id explodes into the nested structure.
   */
  public function testSingleFieldExplodes(): void {
    $settings = [
      'media.image.field_media_image' => [
        'thumbnail' => TRUE,
        'large' => FALSE,
      ],
    ];

    $expected = [
      'media' => [
        'image' => [
          'field_media_image' => [
            'thumbnail' => TRUE,
            'large' => FALSE,
          ],
        ],
      ],
    ];

    $this->assertSame($expected, bluecadet_image_derivatives__explode_derivative_settings($settings));
  }

  /**
   * Tests multiple bundles for the same entity type merge under one key.
   */
  public function testMultipleBundlesMergeUnderEntityType(): void {
    $settings = [
      'media.image.field_media_image' => ['thumbnail' => TRUE],
      'media.document.field_media_file' => ['thumbnail' => FALSE],
    ];

    $result = bluecadet_image_derivatives__explode_derivative_settings($settings);

    $this->assertArrayHasKey('image', $result['media']);
    $this->assertArrayHasKey('document', $result['media']);
    $this->assertSame(['thumbnail' => TRUE], $result['media']['image']['field_media_image']);
    $this->assertSame(['thumbnail' => FALSE], $result['media']['document']['field_media_file']);
  }

  /**
   * Tests an empty settings array explodes into an empty result.
   */
  public function testEmptySettings(): void {
    $this->assertSame([], bluecadet_image_derivatives__explode_derivative_settings([]));
  }

}
