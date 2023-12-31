<?php

namespace Drupal\Tests\photos\Unit;

use Drupal\photos\PhotosAlbum;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\photos\PhotosAlbum
 * @group photos
 */
class PhotosAlbumTest extends TestCase {

  /**
   * Data provider for testOrderValueChange.
   */
  public function orderValueChangeProvider(): array {
    return [
      [
        'weight',
        'asc',
        [
          'column' => 'p.weight',
          'sort' => 'asc',
        ],
      ],
      [
        'filesize',
        'asc',
        [
          'column' => 'f.filesize',
          'sort' => 'asc',
        ],
      ],
      [
        'test',
        'test',
        [
          'column' => 'p.id',
          'sort' => 'desc',
        ],
      ],
    ];
  }

  /**
   * @covers ::orderValueChange
   * @dataProvider orderValueChangeProvider
   */
  public function testOrderValueChange($field, $sort, $expected_result): void {
    $this->assertSame($expected_result, PhotosAlbum::orderValueChange($field, $sort));
  }

  /**
   * Data provider for testOrderValue.
   */
  public function orderValueProvider(): array {
    return [
      [
        'weight',
        'asc',
        0,
        0,
        [
          'order' => [
            'column' => 'p.weight',
            'sort' => 'asc',
          ],
        ],
      ],
      [
        0,
        0,
        0,
        [
          'column' => 'f.filesize',
          'sort' => 'asc',
        ],
        [
          'order' => [
            'column' => 'f.filesize',
            'sort' => 'asc',
          ],
        ],
      ],
      [
        0,
        0,
        0,
        0,
        [
          'order' => [
            'column' => 'p.id',
            'sort' => 'desc',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::orderValue
   * @dataProvider orderValueProvider
   */
  public function testOrderValue($field, $sort, $limit, $default, $expected_result): void {
    $this->assertSame($expected_result, PhotosAlbum::orderValue($field, $sort, $limit, $default));
  }

}
