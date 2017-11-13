<?php

namespace Drupal\ctools_inline_block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\panelizer\Panelizer;

class PanelsBlockLoader implements BlockLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The panelizer service.
   *
   * @var \Drupal\panelizer\Panelizer
   */
  protected $panelizer;

  /**
   * BlockLoader constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\panelizer\Panelizer $panelizer
   *   The panelizer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Panelizer $panelizer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->panelizer = $panelizer;
  }

  /**
   * {@inheritdoc}
   */
  public function load($uuid, $data = NULL) {
    // $data is expected to be storage type and storage id.
    if ($data) {
      list($storage_type, $storage_id) = explode(':', $data, 2);
      if ($storage_type == 'panelizer_default') {
        list($entity_type, $bundle, $view_mode, $default) = explode(':', $storage_id);
        $display = $this->panelizer->getDefaultPanelsDisplay($default, $entity_type, $bundle, $view_mode);
        return $display->getBlock($uuid);
      }
      if ($storage_type == 'panelizer_field') {
        list($entity_type, $entity_id, $view_mode, $revision_id) = explode(':', $storage_id);
        /** @var FieldableEntityInterface $entity */
        if ($revision_id) {
          $entity = $this->entityTypeManager->getStorage($entity_type)->loadRevision($revision_id);
        }
        else {
          $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
        }
        $display = $this->panelizer->getPanelsDisplay($entity, $view_mode);
        return $display->getBlock($uuid);
      }
    }
    else {
      throw new \InvalidArgumentException('No entity ID was specified');
    }
  }

}
