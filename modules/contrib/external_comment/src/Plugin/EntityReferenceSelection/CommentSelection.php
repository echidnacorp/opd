<?php

namespace Drupal\external_comment\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\external_comment\CommentInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides specific access control for the external comment entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:external_comment",
 *   label = @Translation("External comment selection"),
 *   entity_types = {"external_comment"},
 *   group = "default",
 *   weight = 1
 * )
 */
class CommentSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Adding the 'external_comment_access' tag is sadly insufficient for comments:
    // core requires us to also know about the concept of 'published' and
    // 'unpublished'.
    if (!$this->currentUser->hasPermission('administer external comments')) {
      $query->condition('status', CommentInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $comment = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable comment, it needs to published.
    /** @var \Drupal\external_comment\CommentInterface $comment */
    $comment->setPublished(TRUE);

    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer external comments')) {
      $entities = array_filter($entities, function ($comment) {
        /** @var \Drupal\external_comment\CommentInterface $comment */
        return $comment->isPublished();
      });
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    $tables = $query->getTables();
    $data_table = 'external_comment_field_data';
    $field_storage = FieldStorageConfig::loadByName('external_comment', 'commented_node');
    $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
    $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
    if (!isset($tables['external_comment_field_data']['alias'])) {
      // If no conditions join against the comment data table, it should be
      // joined manually to allow node access processing.
      $query->innerJoin($data_table, NULL, "base_table.cid = $data_table.cid AND $data_table.default_langcode = 1");
    }
    $query->condition($data_table . '.entity_type', 'node');
    $query->join($external_comment_entity_table, 'ce', $data_table . '.cid = %alias.entity_id AND %alias.deleted = 0');
    // The Comment module doesn't implement any proper comment access,
    // and as a consequence doesn't make sure that comments cannot be viewed
    // when the user doesn't have access to the node.
    $node_alias = $query->innerJoin('node_field_data', 'n', '%alias.nid = ce.' . $target_id_column);
    // Pass the query to the node access control.
    $this->reAlterQuery($query, 'node_access', $node_alias);

    // Passing the query to node_query_node_access_alter() is sadly
    // insufficient for nodes.
    // @see SelectionEntityTypeNode::entityQueryAlter()
    if (!$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'))) {
      $query->condition($node_alias . '.status', 1);
    }
  }

}
