<?php

/**
 * @file
 * Batch functions to delete entities.
 */

use Drupal\node\Entity\Node;

/**
 * Function to delete content using Batch API.
 *
 * @param array $nodes
 *   Array of nodes to delete.
 * @param mixed &$context
 *   Sandbox context array.
 */
function delete_all_content_batch_delete(array $nodes, &$context) {
  $db = \Drupal::database();

  // Initialize sandbox.
  if ($nodes === NULL) {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_nid'] = 0;
      $context['sandbox']['max'] = $db->select('node', 'n')
        ->fields('n')
        ->countQuery()
        ->accessCheck(TRUE)
        ->execute()
        ->fetchField();

      // Collect results to process in the finished callback.
      $context['results']['node_count'] = $context['sandbox']['max'];
    }

    $nodes_to_delete = $db->select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('nid', $context['sandbox']['current_nid'], '>')
      ->range(0, 100)
      ->execute()
      ->fetchCol();
  }
  else {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($nodes);

      // Collect results to process in the finished callback.
      $context['results']['node_count'] = $context['sandbox']['max'];
    }

    // Get a batch of 100 nodes to delete.
    $nodes_to_delete = array_slice($nodes, $context['sandbox']['progress'], 100);
  }

  if ($context['sandbox']['max'] + 1 > 0) {
    if (!empty($nodes_to_delete)) {
      foreach ($nodes_to_delete as $nid) {
        // Delete node.
        $node = Node::load($nid);
        $node->delete();

        $context['message'] = t('Deleting node with nid %nid', ['%nid' => $nid]);
        $context['sandbox']['current_nid'] = $nid;
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
function delete_all_content_batch_delete_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addMessage(t('Deleted @node_count nodes.', ['@node_count' => $results['node_count']]));
  }
}
