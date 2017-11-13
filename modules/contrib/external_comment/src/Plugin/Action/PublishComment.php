<?php

namespace Drupal\external_comment\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a comment.
 *
 * @Action(
 *   id = "external_comment_publish_action",
 *   label = @Translation("Publish comment"),
 *   type = "external_comment"
 * )
 */
class PublishComment extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $comment->setPublished(TRUE);
    $comment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\external_comment\CommentInterface $object */
    $result = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}