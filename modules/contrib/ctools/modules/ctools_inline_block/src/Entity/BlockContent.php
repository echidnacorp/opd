<?php

namespace Drupal\ctools_inline_block\Entity;

use Drupal\block_content\Entity\BlockContent as CoreBlockContent;

/**
 * Defines the inline custom block entity class.
 *
 * @ContentEntityType(
 *   id = "inline_block_content",
 *   label = @Translation("Custom block"),
 *   bundle_label = @Translation("Custom block type"),
 *   handlers = {
 *     "storage" = "Drupal\ctools_inline_block\Entity\InlineBlockStorage",
 *     "access" = "Drupal\block_content\BlockContentAccessControlHandler",
 *     "view_builder" = "Drupal\block_content\BlockContentViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\block_content\BlockContentForm",
 *       "edit" = "Drupal\block_content\BlockContentForm",
 *       "default" = "Drupal\block_content\BlockContentForm"
 *     },
 *     "translation" = "Drupal\block_content\BlockContentTranslationHandler"
 *   },
 *   admin_permission = "administer blocks",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "info",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "block_content_type",
 *   render_cache = FALSE,
 *   mask = "block_content",
 * )
 */
class BlockContent extends CoreBlockContent {

  /**
   * {@inheritdoc}
   */
  public function id() {
    // What, I hear you asking, is this unholy jiu-jitsu?
    //
    // Inline blocks do not save to the database, so a serial integer ID is
    // never generated. But in order to be loadable, inline blocks must have an
    // identifier of some kind. Therefore, the UUID is dual-purposed as the
    // canonical identifier.
    //
    // This can't be formally done in the entity type definition because,
    // between CoreBlockContent and ContentEntityBase's respective
    // implementations of ::baseFieldDefinitions(), the 'id' field will never
    // be defined and a fatal error will happen. So this is the easiest way
    // to ensure that the ID and UUID are identical at all times.
    return $this->uuid();
  }

}
