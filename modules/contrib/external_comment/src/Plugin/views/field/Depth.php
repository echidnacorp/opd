<?php

namespace Drupal\external_comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("external_comment_depth")
 */
class Depth extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);

    foreach ($items as &$item) {
      // Work out the depth of this comment.
      $external_comment_thread = $item['rendered']['#markup'];
      $item['rendered']['#markup'] = count(explode('.', $external_comment_thread)) - 1;
    }
    return $items;
  }

}
