<?php

/**
 * @file
 * Batch functions to delete entities.
 */

/**
 * Function to delete content using Batch API.
 *
 * @param array $entities
 *   Array of entities to delete.
 * @param string $entity_type
 *   Entity type to delete.
 * @param mixed &$context
 *   Sandbox context array.
 */
function delete_all_entities_batch_delete(array $entities, $entity_type, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($entities);

    // Collect results to process in the finished callback.
    $context['results']['count'] = $context['sandbox']['max'];
  }

  // Get a batch of 100 nodes to delete.
  $to_delete = array_slice($entities, $context['sandbox']['progress'], 100);

  if ($context['sandbox']['max'] + 1 > 0) {
    if (!empty($to_delete)) {
      foreach ($to_delete as $id) {

        $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        $entity = $storage->load($id);

        // Taxonomy terms and other entities are hierarchic, causing some
        // entities to be deleted when their parents are deleted.
        if ($entity) {
          $entity->delete();
        }

        $context['message'] = t('Deleting entity with id %id', ['%id' => $id]);
        $context['sandbox']['current_nid'] = $id;
        $context['sandbox']['progress']++;
      }
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }
}

/**
 * Finished callback for the user deletion batch.
 *
 * @param int $success
 *   Equals 1 if batch is successfull else equals 0.
 * @param mixed $results
 *   List of results parameter collected during batch processing.
 * @param mixed $operations
 *   List of operations.
 */
function delete_all_entities_batch_delete_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addMessage(t('Deleted @count entities.', ['@count' => $results['count']]));
  }
}
