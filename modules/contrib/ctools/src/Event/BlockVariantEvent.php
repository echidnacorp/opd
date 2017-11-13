<?php


namespace Drupal\ctools\Event;


use Drupal\Core\Block\BlockPluginInterface;
use Drupal\ctools\Plugin\BlockVariantInterface;
use Symfony\Component\EventDispatcher\Event;

class BlockVariantEvent extends Event {

  /**
   * The block being acted upon.
   *
   * @var BlockPluginInterface
   */
  protected $block;

  /**
   * The variant acting on the block.
   *
   * @var BlockVariantInterface
   */
  protected $variant;

  /**
   * BlockVariantEvent constructor.
   *
   * @param BlockPluginInterface $block
   *   The block plugin.
   * @param BlockVariantInterface $variant
   *   The variant plugin.
   */
  public function __construct(BlockPluginInterface $block, BlockVariantInterface $variant) {
    $this->block = $block;
    $this->variant = $variant;
  }

  /**
   * Gets the block plugin.
   *
   * @return BlockPluginInterface
   */
  public function getBlock() {
    return $this->block;
  }

  /**
   * Gets the variant plugin.
   *
   * @return BlockVariantInterface
   */
  public function getVariant() {
    return $this->variant;
  }

}
