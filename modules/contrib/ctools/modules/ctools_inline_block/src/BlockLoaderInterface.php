<?php

namespace Drupal\ctools_inline_block;

/**
 * Defines an interface for loading inline blocks in a standard way.
 */
interface BlockLoaderInterface {

  /**
   * Loads an inline block by UUID.
   *
   * @param string $uuid
   *   The inline block UUID.
   * @param mixed $data
   *   (optional) Additional data needed to load the block.
   *
   * @return \Drupal\ctools_inline_block\Entity\BlockContent
   *   The inline block, or NULL if it could not be loaded.
   */
  public function load($uuid, $data = NULL);

}
