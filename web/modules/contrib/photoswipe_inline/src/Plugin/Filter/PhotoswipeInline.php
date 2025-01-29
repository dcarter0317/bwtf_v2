<?php

namespace Drupal\photoswipe_inline\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'PhotoSwipe Inline' filter.
 *
 * @Filter(
 *   id = "photoswipe_inline",
 *   title = @Translation("PhotoSwipe Inline Text Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = -10
 * )
 */
class PhotoswipeInline extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = '<div class="photoswipe-gallery">' . $text . '</div>';
    $dom = Html::load($text);

    $elements = $dom->getElementsByTagName('img');
    if ($elements->length === 0) {
      return new FilterProcessResult(Html::serialize($dom));
    }

    foreach ($elements as $element) {
      if (!$element->hasAttribute('class')) {
        $a = $dom->createElement('a');
        $element->parentNode->insertBefore($a, $element);
        $a->appendChild($element);
        $img_width = $element->getAttribute('width');
        $img_height = $element->getAttribute('height');
        $a->setAttribute('href', $element->getAttribute('src'));
        $a->setAttribute('class', 'photoswipe');
        $a->setAttribute('data-pswp-width', $img_width);
        $a->setAttribute('data-pswp-height', $img_height);
      }
    }

    return new FilterProcessResult(Html::serialize($dom));
  }

}
