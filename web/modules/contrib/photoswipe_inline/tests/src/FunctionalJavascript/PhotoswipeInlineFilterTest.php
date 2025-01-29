<?php

namespace Drupal\Tests\photoswipe_inline\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests for the photoswipe_inline module.
 *
 * @group photoswipe_inline
 */
class PhotoswipeInlineFilterTest extends WebDriverTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'photoswipe_inline',
    'photoswipe',
    'photoswipe_library_test',
    'node',
    'text',
  ];

  /**
   * Test Node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'page']);

    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'filters' => [
        'photoswipe_inline' => [
          'status' => 1,
        ],
      ],
    ])->save();
  }

  /**
   * Test the inline PhotoSwipe launches when a link is clicked.
   */
  public function testInlinePhotoSwipe(): void {
    $session = $this->assertSession();

    $this->node = $this->createNode([
      'body' => [
        'value' => '<img src="https://www.drupal.org/files/cta/graphic/Association_Supporting_Partner_Badge_3.png" alt="Supporting Partner Badge" width="217" height="217">',
        'format' => 'full_html',
      ],
    ]);

    $this->drupalGet('node/' . $this->node->id());

    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="Association_Supporting_Partner_Badge_3.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery a[href*="Association_Supporting_Partner_Badge_3.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="Association_Supporting_Partner_Badge_3.png"].photoswipe', 'data-pswp-width');

    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="Association_Supporting_Partner_Badge_3.png"]');
    $session->elementExists('css', 'a[href*="Association_Supporting_Partner_Badge_3.png"].photoswipe > img[src*="Association_Supporting_Partner_Badge_3.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="Association_Supporting_Partner_Badge_3.png"].photoswipe > img[src*="Association_Supporting_Partner_Badge_3.png"]');
    $session->elementAttributeContains('css', 'img[src*="Association_Supporting_Partner_Badge_3.png"]', 'width', '217');
    $session->elementAttributeContains('css', 'img[src*="Association_Supporting_Partner_Badge_3.png"]', 'height', '217');

    $this->click('a.photoswipe');
    $this->getSession()->wait(500);
    $session->elementsCount('css', '.pswp--open img.pswp__img', 1);
  }

}
