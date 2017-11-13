<?php

/**
 * @file
 * Provide views data for comment.module.
 */

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implements hook_views_data_alter().
 */
function external_comment_views_data_alter(&$data) {
  // New comments are only supported for node table because it requires the
  // history table.
  $data['node']['new_comments'] = [
    'title' => t('New comments'),
    'help' => t('The number of new comments on the node.'),
    'field' => [
      'id' => 'node_new_comments',
      'no group by' => TRUE,
    ],
  ];

  // Provide a integration for each entity type except comment.
  foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type_id == 'external_comment' || !$entity_type->entityClassImplements(ContentEntityInterface::class) || !$entity_type->getBaseTable()) {
      continue;
    }
    $fields = \Drupal::service('external_comment.manager')->getFields($entity_type_id);
    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $args = ['@entity_type' => $entity_type_id];

    if ($fields) {
      $data[$base_table]['comments_link'] = [
        'field' => [
          'title' => t('Add comment link'),
          'help' => t('Display the standard add comment link used on regular @entity_type, which will only display if the viewing user has access to add a comment.', $args),
          'id' => 'external_comment_entity_link',
        ],
      ];

      // Multilingual properties are stored in data table.
      if (!($table = $entity_type->getDataTable())) {
        $table = $entity_type->getBaseTable();
      }
      $data[$table]['uid_touch'] = [
        'title' => t('User posted or commented'),
        'help' => t('Display nodes only if a user posted the @entity_type or commented on the @entity_type.', $args),
        'argument' => [
          'field' => 'uid',
          'name table' => 'users_field_data',
          'name field' => 'name',
          'id' => 'argument_external_comment_user_uid',
          'no group by' => TRUE,
          'entity_type' => $entity_type_id,
          'entity_id' => $entity_type->getKey('id'),
        ],
        'filter' => [
          'field' => 'uid',
          'name table' => 'users_field_data',
          'name field' => 'name',
          'id' => 'external_comment_user_uid',
          'entity_type' => $entity_type_id,
          'entity_id' => $entity_type->getKey('id'),
        ],
      ];
    }
  }
}
