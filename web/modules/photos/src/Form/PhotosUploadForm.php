<?php

namespace Drupal\photos\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\photos\PhotosUploadInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to upload photos to this site.
 */
class PhotosUploadForm extends FormBase {

  use DependencySerializationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The photos upload handler.
   *
   * @var \Drupal\photos\PhotosUploadInterface
   */
  protected $photosUpload;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\photos\PhotosUploadInterface $photos_upload
   *   The photos upload handler.
   */
  public function __construct(Connection $connection, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_manager, FileSystem $file_system, ImageFactory $image_factory, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, RouteMatchInterface $route_match, PhotosUploadInterface $photos_upload) {
    $this->connection = $connection;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_manager;
    $this->fileSystem = $file_system;
    $this->imageFactory = $image_factory;
    $this->logger = $this->getLogger('photos');
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->photosUpload = $photos_upload;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('image.factory'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('photos.upload')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_upload';
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The album node entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(NodeInterface $node) {
    // Check if user can edit this album.
    if ($node->getType() == 'photos' && $node->access('update')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $config = $this->config('photos.settings');
    $nid = $node->id();

    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['new'] = [
      '#title' => $this->t('Image upload'),
      '#weight' => -4,
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $allow_zip = (($config->get('photos_upzip')) ? ' zip' : '');
    // Check if plubload is installed.
    if ($config->get('photos_plupload_status')) {
      $form['new']['plupload'] = [
        '#type' => 'plupload',
        '#title' => $this->t('Upload photos'),
        '#description' => $this->t('Upload multiple images.'),
        '#autoupload' => TRUE,
        '#submit_element' => '#edit-submit',
        '#upload_validators' => [
          'file_validate_extensions' => ['jpg jpeg gif png' . $allow_zip],
        ],
        '#plupload_settings' => [
          'chunk_size' => '1mb',
        ],
      ];
    }
    else {
      // Manual upload form.
      $form['new']['#description'] = $this->t('Allowed types: jpg gif png jpeg@zip', ['@zip' => $allow_zip]);
      $album_photo_limit = $config->get('album_photo_limit');
      $classic_field_count = $config->get('photos_num');
      if ($album_photo_limit && ($classic_field_count > $album_photo_limit)) {
        $classic_field_count = $album_photo_limit;
      }

      for ($i = 0; $i < $classic_field_count; ++$i) {
        $form['new']['images_' . $i] = [
          '#type' => 'file',
        ];
        $form['new']['title_' . $i] = [
          '#type' => 'textfield',
          '#title' => $this->t('Image title'),
        ];
        $form['new']['des_' . $i] = [
          '#type' => 'textarea',
          '#title' => $this->t('Image description'),
          '#cols' => 40,
          '#rows' => 3,
        ];
      }
    }
    if ($this->moduleHandler->moduleExists('media_library_form_element')) {
      // Check photos default multi-upload field.
      $uploadField = $this->config('photos.settings')->get('multi_upload_default_field');
      $uploadFieldParts = explode(':', $uploadField);
      $field = $uploadFieldParts[0] ?? 'field_image';
      $allBundleFields = $this->entityFieldManager->getFieldDefinitions('photos_image', 'photos_image');
      if (isset($allBundleFields[$field])) {
        $fieldType = $allBundleFields[$field]->getType();
        // Check if media field.
        if ($fieldType == 'entity_reference') {
          $mediaField = $uploadFieldParts[1] ?? '';
          $mediaBundle = $uploadFieldParts[2] ?? '';
          if ($mediaField && $mediaBundle) {
            $form['new']['media_images'] = [
              '#type' => 'media_library',
              '#allowed_bundles' => [$mediaBundle],
              '#title' => $this->t('Select media images'),
              '#default_value' => NULL,
              '#description' => $this->t('Select media images to add to this album.'),
              '#cardinality' => -1,
            ];
          }
        }
      }
    }
    // @todo album_id is redundant unless albums become own entity.
    //   - maybe make album_id serial and add nid... or entity_id.
    $form['new']['album_id'] = [
      '#type' => 'value',
      '#value' => $nid,
    ];
    $form['new']['nid'] = [
      '#type' => 'value',
      '#value' => $nid,
    ];
    $form['new']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm upload'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('photos.settings');
    $album_id = $form_state->getValue('album_id');
    $album_photo_limit = $config->get('album_photo_limit');

    $photo_count = $this->connection->query("SELECT count FROM {photos_album} WHERE album_id = :album_id", [
      ':album_id' => $album_id,
    ])->fetchField();

    if ($album_photo_limit && ($photo_count >= $album_photo_limit)) {
      $form_state->setErrorByName('new', $this->t('Maximum number of photos reached for this album.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = FALSE;
    $user = $this->currentUser();
    $config = $this->config('photos.settings');
    $album_photo_limit = $config->get('album_photo_limit') ?? 0;
    $count = 0;
    $nid = $form_state->getValue('nid');
    $album_id = $form_state->getValue('album_id');
    $photo_count = $this->connection->query("SELECT count FROM {photos_album} WHERE album_id = :album_id", [
      ':album_id' => $album_id,
    ])->fetchField() ?? 0;
    // If photos_access is enabled check viewid.
    $scheme = 'default';
    if ($this->moduleHandler->moduleExists('photos_access')) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if (isset($node->photos_privacy) && isset($node->photos_privacy['viewid'])) {
        $album_viewid = $node->photos_privacy['viewid'];
        if ($album_viewid > 0) {
          // Check for private file path.
          if (PrivateStream::basePath()) {
            $scheme = 'private';
          }
          else {
            // Set warning message.
            $this->messenger->addWarning($this->t('Warning: image
              files can still be accessed by visiting the direct URL. For
              better security, ask your website admin to setup a private file
              path.'));
          }
        }
      }
    }
    // Check if plupload is enabled.
    // @todo check for plupload library?
    if ($config->get('photos_plupload_status')) {
      $plupload_files = $form_state->getValue('plupload');
      $plupload_chunks = array_chunk($plupload_files, 10);
      $total_count = count($plupload_files);
      // Prep batch operations.
      $operations = [];
      foreach ($plupload_chunks as $files) {
        $batch_operation_params = [
          $files,
          $nid,
          $album_id,
          $user->id(),
          $scheme,
          $total_count,
          $album_photo_limit,
          $photo_count,
          $config->get('photos_upzip'),
        ];
        $operations[] = [
          ['\Drupal\photos\Form\PhotosUploadForm', 'pluploadSubmitBatch'], $batch_operation_params,
        ];
      }
      $batch = [
        'title' => $this->t('Processing images'),
        'operations' => $operations,
        'finished' => ['\Drupal\photos\Form\PhotosUploadForm', 'batchFinished'],
        'progress_message' => '',
      ];
      // Start batch.
      batch_set($batch);
      $batch = TRUE;
    }
    else {
      // Manual upload form.
      $photos_num = $config->get('photos_num');
      for ($i = 0; $i < $photos_num; ++$i) {
        if (isset($_FILES['files']['name']['images_' . $i]) && $_FILES['files']['name']['images_' . $i]) {
          if ($album_photo_limit && ($photo_count >= $album_photo_limit)) {
            $this->messenger()->addWarning($this->t('Maximum number of photos reached for this album.'));
            break;
          }
          $ext = mb_substr($_FILES['files']['name']['images_' . $i], -3);
          if ($ext != 'zip' && $ext != 'ZIP') {
            // Prepare directory.
            $photosPath = $this->photosUpload->path($scheme);
            $photosName = $_FILES['files']['name']['images_' . $i];
            $file_uri = $this->fileSystem
              ->getDestinationFilename($photosPath . '/' . $photosName, FileSystemInterface::EXISTS_RENAME);
            if ($this->fileSystem->move($_FILES['files']['tmp_name']['images_' . $i], $file_uri)) {
              $path_parts = pathinfo($file_uri);
              $image = $this->imageFactory->get($file_uri);
              // @todo file_validate_is_image?
              if (isset($path_parts['extension']) && $path_parts['extension'] && $image->getWidth()) {
                // Create a file entity.
                /** @var \Drupal\file\FileInterface $file */
                $file = $this->entityTypeManager->getStorage('file')->create([
                  'uri' => $file_uri,
                  'uid' => $user->id(),
                  'status' => FileInterface::STATUS_PERMANENT,
                  'album_id' => $form_state->getValue('album_id'),
                  'nid' => $form_state->getValue('nid'),
                  'filename' => $photosName,
                  'filesize' => $image->getFileSize(),
                  'filemime' => $image->getMimeType(),
                  'title' => $form_state->getValue('title_' . $i),
                  'des' => $form_state->getValue('des_' . $i),
                ]);

                if ($file->save()) {
                  $this->photosUpload->saveImage($file);
                  $photo_count++;
                }
                $count++;
              }
              else {
                $this->fileSystem->delete($file_uri);
                $this->logger->notice('Wrong file type');
              }
            }
          }
          else {
            // Zip upload from manual upload form.
            if (!$config->get('photos_upzip')) {
              $this->messenger->addError($this->t('Please update settings to allow zip uploads.'));
            }
            else {
              $directory = $this->photosUpload->path();
              $this->fileSystem->prepareDirectory($directory);
              $zip = $this->fileSystem
                ->getDestinationFilename($directory . '/' . trim(basename($_FILES['files']['name']['images_' . $i])), FileSystemInterface::EXISTS_RENAME);
              if ($this->fileSystem->move($_FILES['files']['tmp_name']['images_' . $i], $zip)) {
                $params = [];
                $params['album_id'] = $album_id;
                $params['photo_count'] = $photo_count;
                $params['album_photo_limit'] = $album_photo_limit;
                $params['nid'] = $form_state->getValue('nid') ? $form_state->getValue('nid') : $form_state->getValue('album_id');
                $params['description'] = $form_state->getValue('des_' . $i);
                $params['title'] = $form_state->getValue('title_' . $i);
                if (!$file_count = $this->photosUpload->unzip($zip, $params, $scheme)) {
                  // Upload failed.
                }
                else {
                  $count = $count + $file_count;
                  $photo_count = $photo_count + $file_count;
                }
              }
            }
          }
        }
      }
    }
    // Handle media field.
    $selected_media = $form_state->getValue('media_images') ? explode(',', $form_state->getValue('media_images')) : [];
    foreach ($selected_media as $media_id) {
      if ($album_photo_limit && ($photo_count >= $album_photo_limit)) {
        $this->messenger()->addWarning($this->t('Maximum number of photos reached for this album.'));
        break;
      }
      // Save media to album.
      $mediaSaved = $this->photosUpload->saveExistingMedia($media_id, $nid);
      if ($mediaSaved) {
        $photo_count++;
        $count++;
      }
    }
    if (!$batch) {
      // Clear node and album page cache.
      Cache::invalidateTags(['node:' . $nid, 'photos:album:' . $nid]);
      $message = $this->formatPlural($count, '1 image uploaded.', '@count images uploaded.');
      $this->messenger->addMessage($message);
    }
  }

  /**
   * Process plupload images.
   *
   * @param array $plupload_files
   *   An array of files uploaded with the plupload widget.
   * @param int $nid
   *   The album node ID.
   * @param int $album_id
   *   The album ID (same as nid for now).
   * @param int $uid
   *   The user uploading the files.
   * @param string $scheme
   *   The file scheme (private or public for example).
   * @param int $total_count
   *   The total number of plupload files being processed.
   * @param int $album_photo_limit
   *   The maximum number of photos per album setting.
   * @param int $photo_count
   *   The number of photos already in this photo album.
   * @param bool $zip_allowed
   *   If zip archives are allowed.
   * @param array|\ArrayAccess $context
   *   The batch context array, passed by reference.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function pluploadSubmitBatch(array $plupload_files, int $nid, int $album_id, int $uid, string $scheme, int $total_count, int $album_photo_limit, int $photo_count, bool $zip_allowed, &$context) {
    if (!isset($context['results']['count'])) {
      $context['results']['progress'] = 0;
      $context['results']['max'] = $total_count;
      // Pass album nid to batch finished.
      $context['results']['nid'] = $nid;
      $context['results']['messages'] = [
        'warning' => [],
        'error' => [],
      ];
      // Photos saved during this batch run.
      $context['results']['count'] = 0;
      // The album total photo count.
      $context['results']['photo_count'] = $photo_count;
    }
    /** @var \Drupal\photos\PhotosUploadInterface $photos_upload */
    $photos_upload = \Drupal::service('photos.upload');
    /** @var \Drupal\file\FileStorageInterface $file_storage */
    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    /** @var \Drupal\Core\Image\ImageFactory $image_factory */
    $image_factory = \Drupal::service('image.factory');
    foreach ($plupload_files as $uploaded_file) {
      if ($uploaded_file['status'] == 'done') {
        if ($album_photo_limit && ($context['results']['photo_count'] >= $album_photo_limit)) {
          $context['results']['messages']['warning'][] = t('Maximum number of photos reached for this album.');
          break;
        }
        // Check for zip files.
        $ext = mb_substr($uploaded_file['name'], -3);
        if ($ext != 'zip' && $ext != 'ZIP') {
          // Prepare directory.
          // @todo move path to after entity is created or move again later if needed.
          // @todo generate temp path before tokens are available.
          $photosPath = $photos_upload->path($scheme);
          $photosName = $uploaded_file['name'];
          $file_uri = $file_system->getDestinationFilename($photosPath . '/' . $photosName, FileSystemInterface::EXISTS_RENAME);
          if ($file_system->move($uploaded_file['tmppath'], $file_uri)) {
            $path_parts = pathinfo($file_uri);
            $image = $image_factory->get($file_uri);
            if (isset($path_parts['extension']) && $path_parts['extension'] && $image->getWidth()) {
              // Create a file entity.
              /** @var \Drupal\file\FileInterface $file */
              $file = $file_storage->create([
                'uri' => $file_uri,
                'uid' => $uid,
                'status' => FileInterface::STATUS_PERMANENT,
                'album_id' => $album_id,
                'nid' => $nid,
                'filename' => $photosName,
                'filesize' => $image->getFileSize(),
                'filemime' => $image->getMimeType(),
              ]);

              if ($file->save()) {
                $context['results']['photo_count']++;
                $photos_upload->saveImage($file);
              }
              $context['results']['count']++;
            }
            else {
              $file_system->delete($file_uri);
              \Drupal::logger('photos')->notice('Wrong file type');
            }
          }
          else {
            \Drupal::logger('photos')->notice('Upload error. Could not move temp file.');
          }
        }
        else {
          if (!$zip_allowed) {
            $context['results']['messages']['error'][] = t('Please set Album photos to open zip uploads.');
          }
          $directory = $photos_upload->path();
          $file_system->prepareDirectory($directory);
          $zip = $file_system->getDestinationFilename($directory . '/' . $uploaded_file['name'], FileSystemInterface::EXISTS_RENAME);
          if ($file_system->move($uploaded_file['tmppath'], $zip)) {
            $params = [];
            $params['album_id'] = $album_id;
            $params['photo_count'] = $context['results']['photo_count'];
            $params['album_photo_limit'] = $album_photo_limit;
            $params['nid'] = $nid;
            $params['title'] = $uploaded_file['name'];
            $params['des'] = '';
            // Unzip it.
            if (!$file_count = $photos_upload->unzip($zip, $params, $scheme)) {
              $context['results']['messages']['error'][] = t('Zip upload failed.');
            }
            else {
              // Update image upload count.
              $context['results']['count'] = $context['results']['count'] + $file_count;
              $context['results']['photo_count'] = $context['results']['photo_count'] + $file_count;
            }
          }
        }
      }
      else {
        $context['results']['messages']['error'][] = t('Error uploading some photos.');
      }
      $context['results']['progress']++;
    }
    $context['message'] = new TranslatableMarkup('Completed @percentage% (@current of @total).', [
      '@percentage' => round(100 * $context['results']['progress'] / $context['results']['max']),
      '@current' => $context['results']['progress'],
      '@total' => $context['results']['max'],
    ]);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Batch results.
   */
  public static function batchFinished($success, $results, $operations) {
    $nid = $results['nid'];
    // Clear node and album page cache.
    Cache::invalidateTags(['node:' . $nid, 'photos:album:' . $nid]);
    // Add any error messages.
    if (isset($results['messages']['error'])) {
      foreach ($results['messages']['error'] as $message) {
        \Drupal::messenger()->addError($message);
      }
    }
    // Add any warning messages.
    if (isset($results['messages']['warning'])) {
      foreach ($results['messages']['warning'] as $message) {
        \Drupal::messenger()->addWarning($message);
      }
    }
    $message = \Drupal::translation()->formatPlural($results['count'], '1 image uploaded.', '@count images uploaded.');
    \Drupal::messenger()->addMessage($message);
  }

}
