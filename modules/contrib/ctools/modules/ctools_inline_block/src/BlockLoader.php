<?php

namespace Drupal\ctools_inline_block;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class BlockLoader implements BlockLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BlockLoader constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function load($uuid, $data = NULL) {
    // $data is expected to be the ID of the block config entity.
    if ($data) {
      return $this->entityTypeManager->getStorage('block')->load($data)->getPlugin()->getEntity();
    }
    else {
      throw new \InvalidArgumentException('No block entity ID was specified');
    }
  }

}
