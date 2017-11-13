<?php

namespace Drupal\external_comment\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a comment.
 *
 * @Action(
 *   id = "external_comment_unpublish_action",
 *   label = @Translation("Unpublish comment"),
 *   type = "external_comment"
 * )
 */
class UnpublishComment extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $comment->setPublished(FALSE);
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
