<?php

namespace Drupal\external_comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to display the timestamp of a comment with the count of comments.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("external_comment_last_timestamp")
 */
class LastTimestamp extends Date {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['external_comment_count'] = 'external_comment_count';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $external_comment_count = $this->getValue($values, 'external_comment_count');
    if (empty($this->options['empty_zero']) || $external_comment_count) {
      return parent::render($values);
    }
    else {
      return NULL;
    }
  }

}
