<?php

namespace Drupal\external_comment;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\external_comment\Entity\CommentType;

/**
 * Defines the comment schema handler.
 */
class CommentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['external_comment_field_data']['indexes'] += [
      'external_comment__status_pid' => ['pid', 'status'],
      'external_comment__num_new' => [
        'entity_type',
        'external_comment_type',
        'status',
        'created',
        'cid',
        ['thread', 191],
      ],
      'external_comment__entity_langcode' => [
        'entity_type',
        'external_comment_type',
        'default_langcode',
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'external_comment_field_data') {
      // Remove unneeded indexes.
      unset($schema['indexes']['external_comment_field__pid__target_id']);
      unset($schema['indexes']['external_comment_field__entity_id__target_id']);

      switch ($field_name) {
        case 'thread':
          // Improves the performance of the external_comment__num_new index defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'created':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
      }
    }

    return $schema;
  }

  public function onEntityTypeUpdate(\Drupal\Core\Entity\EntityTypeInterface $entity_type, \Drupal\Core\Entity\EntityTypeInterface $original) {
    $storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type->id());
    $original_storage_definitions = $this->entityManager->getLastInstalledFieldStorageDefinitions($original->id());
    if (isset($original_storage_definitions['entity_id']) && !isset($storage_definitions['entity_id'])) {
      // Create a table to temporarily store the data until
      // external_comment_update_8302() is executed.
      db_create_table('external_comment_update_8302', [
        'fields' => [
          'cid' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The {comment}.cid.',
          ],
          'entity_id' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The entity id.',
          ],
        ],
      ]);
      db_insert('external_comment_update_8302')->from(
        db_select('external_comment_field_data', 'c')->fields('c', ['cid', 'entity_id'])
      )->execute();
      db_change_field(
        'external_comment_field_data',
        'entity_id',
        'entity_id',
        [
          'type' => 'int',
          'not null' => FALSE,
          'default' => NULL,
          'description' => 'The entity id.',
        ]
      );
      db_update('external_comment_field_data')->fields(['entity_id' => NULL])->execute();
      // Create the reference fields for each comment bundle.
      $external_comment_types = CommentType::loadMultiple();
      foreach ($external_comment_types as $external_comment_type) {
        \Drupal::service('external_comment.manager')->addEntityField($external_comment_type->id());
      }

      // Drop the indexes that use the deleted field.
      db_drop_index('external_comment_field_data', 'external_comment__num_new');
      db_drop_index('external_comment_field_data', 'external_comment__entity_langcode');
    }
    parent::onEntityTypeUpdate($entity_type, $original);
  }

}
