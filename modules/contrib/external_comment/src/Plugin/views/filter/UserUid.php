<?php

namespace Drupal\external_comment\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Filter handler to accept a user id to check for nodes that user posted or
 * commented on.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("external_comment_user_uid")
 */
class UserUid extends FilterPluginBase {

  public function query() {
    $this->ensureMyTable();

    $field_storage = FieldStorageConfig::loadByName('external_comment', 'commented_' . $this->definition['entity_type']);
    $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
    $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
    $subselect = db_select('external_comment_field_data', 'c');
    $subselect->join($external_comment_entity_table, 'ce', 'c.cid = %alias.entity_id AND %alias.deleted = 0');
    $subselect->addField('c', 'cid');
    $subselect->condition('c.uid', $this->value, $this->operator);

    $entity_id = $this->definition['entity_id'];
    $entity_type = $this->definition['entity_type'];
    $subselect->where("ce.$target_id_column = $this->tableAlias.$entity_id");
    $subselect->condition('c.entity_type', $entity_type);

    $condition = (new Condition('OR'))
      ->condition("$this->tableAlias.uid", $this->value, $this->operator)
      ->exists($subselect);

    $this->query->addWhere($this->options['group'], $condition);
  }

}
