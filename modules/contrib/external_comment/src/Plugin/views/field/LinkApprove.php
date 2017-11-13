<?php

namespace Drupal\external_comment\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Provides a comment approve link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("external_comment_link_approve")
 */
class LinkApprove extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    return Url::fromRoute('external_comment.approve', ['external_comment' => $this->getEntity($row)->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Approve');
  }

}
