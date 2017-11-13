<?php

namespace Drupal\ctools_inline_block\Entity;

use Drupal\ctools_inline_block\InlineStorage;

/**
 * A content entity storage handler that does not save to the database.
 */
class InlineBlockStorage extends InlineStorage {

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    parent::delete($entities);

    $this->database
      ->delete('inline_block')
      ->condition('uuid', array_keys($entities), 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanIds(array $ids) {
    return array_map('strval', $ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStorage(array $ids = NULL) {
    $id_key = $this->idKey;
    $this->idKey = 'uuid';
    $entities = parent::getFromStorage($ids);
    $this->idKey = $id_key;
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = $this->database->select('inline_block', 'ib')->fields('ib');

    if ($ids) {
      $query->condition('uuid', $ids, 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    $mapped = [];
    foreach ($records as $uuid => $record) {
      // @TODO: Inject the service as a dependency.
      $mapped[$uuid] = \Drupal::service($record->loader)->load($uuid, $record->data);
    }
    return $mapped;
  }

}
