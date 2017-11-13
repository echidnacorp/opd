<?php

namespace Drupal\ctools_inline_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves bundle types for inline blocks.
 */
class InlineBlock extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The custom block type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentTypeStorage;

  /**
   * Constructs a BlockContentType object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_type_storage
   *   The custom block storage.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(EntityStorageInterface $block_content_type_storage, TranslationInterface $translation) {
    $this->blockContentTypeStorage = $block_content_type_storage;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('block_content_type'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_types = $this->blockContentTypeStorage->loadMultiple();
    // Reset the discovered definitions.
    $this->derivatives = [];
    /** @var $type \Drupal\block_content\Entity\BlockContent */
    foreach ($block_types as $id => $type) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['admin_label'] = $this->t('Inline @type', ['@type' => $type->label()]);
      $this->derivatives[$id]['config_dependencies']['content'] = array(
        $type->getConfigDependencyName()
      );
    }
    return $this->derivatives;
  }

}
