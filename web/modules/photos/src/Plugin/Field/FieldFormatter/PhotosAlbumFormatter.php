<?php

namespace Drupal\photos\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Render\RendererInterface;
use Drupal\photos\PhotosAlbum;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'photos_album' formatter.
 *
 * @FieldFormatter(
 *   id = "photos_album",
 *   label = @Translation("Photo album"),
 *   description = @Translation("Display the photo album."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class PhotosAlbumFormatter extends EntityReferenceFormatterBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an MediaThumbnailFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'photos_display_type' => 'cover',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * This has to be overridden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['photos_display_type'] = [
      '#title' => 'Type',
      '#type' => 'select',
      '#options' => [
        'cover' => 'Cover',
        'images' => 'Images',
        'slideshow' => 'Slideshow',
      ],
    ];
    unset($element['image_link']['#options']['file']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $display_types = [
      'none' => '',
      'colorbox' => $this->t('Display cover as link to colorbox album'),
      'cover' => $this->t('Display cover that links to album view'),
      'images' => $this->t('Display the images'),
      'slideshow' => $this->t('Display an image slideshow'),
      'thumbnails' => $this->t('Display a few thumbnails'),
    ];
    // Display this setting only if image is linked.
    $image_setting = $this->getSetting('photos_display_type');
    if (isset($display_types[$image_setting])) {
      $summary[] = $display_types[$image_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $photosDisplayType = $this->getSetting('photos_display_type');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // @todo fall back on label display if not a photo album.
      $label = $entity->label();
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      switch ($photosDisplayType) {
        case 'none':
          // Render nothing.
          break;

        case 'cover':
          $albumView = [];
          if (isset($entity->album) && isset($entity->album['cover_id'])) {
            $coverId = $entity->album['cover_id'];
            $photos_album = new PhotosAlbum($entity->id());
            $albumView = $photos_album->getCover($coverId);
          }
          // @todo fallback on image?
          $elements[$delta] = $albumView;
          break;
      }

      if (!$entity->isNew()) {
        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
      $elements[$delta]['#cache']['tags'][] = 'photos:album:' . $entity->id();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media items.
    // @todo check if node type photos?
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'node');
  }

}
