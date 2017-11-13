<?php

namespace Drupal\external_comment\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this comment is a reply.
    return $this->entity->getCommentedEntity()->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any replies to this comment will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The comment and all its replies have been deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('external_comment')->notice('Deleted comment @cid and its replies.', ['@cid' => $this->entity->id()]);
  }

}
