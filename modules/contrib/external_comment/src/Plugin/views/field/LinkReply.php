<?php

namespace Drupal\external_comment\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to reply to a comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("external_comment_link_reply")
 */
class LinkReply extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\external_comment\CommentInterface $comment */
    $comment = $this->getEntity($row);
    return Url::fromRoute('external_comment.reply', [
      'entity_type' => $comment->getCommentedEntityTypeId(),
      'entity' => $comment->getCommentedEntityId(),
      'field_name' => $comment->getFieldName(),
      'pid' => $comment->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Reply');
  }

}
