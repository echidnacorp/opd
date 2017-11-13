<?php

namespace Drupal\external_comment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a comment type entity.
 */
interface CommentTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the comment type description.
   *
   * @return string
   *   The comment-type description.
   */
  public function getDescription();

  /**
   * Sets the description of the comment type.
   *
   * @param string $description
   *   The new description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the target entity type id for this comment type.
   *
   * @return string
   *   The target entity type id.
   */
  public function getTargetEntityTypeId();

  /**
   * Gets the entity reference field name for this comment type.
   *
   * @return string
   *   The field name of the entity reference field referencing the commented
   *   entity.
   */
  public function getEntityReferenceFieldName();

}
