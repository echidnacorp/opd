<?php

namespace Drupal\external_comment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\external_comment\CommentTypeInterface;

/**
 * Defines the external comment type entity.
 *
 * @ConfigEntityType(
 *   id = "external_comment_type",
 *   label = @Translation("External comment type"),
 *   label_singular = @Translation("External comment type"),
 *   label_plural = @Translation("External comment types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count external comment type",
 *     plural = "@count external comment types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\external_comment\CommentTypeForm",
 *       "add" = "Drupal\external_comment\CommentTypeForm",
 *       "edit" = "Drupal\external_comment\CommentTypeForm",
 *       "delete" = "Drupal\external_comment\Form\CommentTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\external_comment\CommentTypeListBuilder"
 *   },
 *   admin_permission = "administer external comment types",
 *   config_prefix = "type",
 *   bundle_of = "external_comment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/external/comment/manage/{external_comment_type}/delete",
 *     "edit-form" = "/admin/structure/external/comment/manage/{external_comment_type}",
 *     "add-form" = "/admin/structure/external/comment/types/add",
 *     "collection" = "/admin/structure/external/comment/types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "target_entity_type_id",
 *     "description",
 *   }
 * )
 */
class CommentType extends ConfigEntityBundleBase implements CommentTypeInterface {

  /**
   * The comment type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The comment type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the comment type.
   *
   * @var string
   */
  protected $description;

  /**
   * The target entity type.
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceFieldName() {
    return 'commented_' . $this->getTargetEntityTypeId();
  }

}
