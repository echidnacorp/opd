<?php


namespace Drupal\ctools_inline_block\EventSubscriber;


use Drupal\Core\Database\Connection;
use Drupal\ctools\Event\BlockVariantEvent;
use Drupal\ctools\Plugin\BlockVariantInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InlineBlock implements EventSubscriberInterface {

  /**
   * @var Connection
   */
  protected $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  public static function getSubscribedEvents() {
    $events[BlockVariantInterface::ADD_BLOCK][] = ['onVariantBlockAdd'];
    $events[BlockVariantInterface::DELETE_BLOCK][] = ['onVariantBlockDelete'];
    return $events;
  }

  public function onVariantBlockAdd(BlockVariantEvent $event) {
    $block = $event->getBlock();
    $block_plugin_id = $block->getPluginId();
    if ($block_plugin_id && explode(':', $block_plugin_id)[0] == 'inline_content') {
      /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display_variant */
      $panels_display_variant = $event->getVariant();
      // Panelizer Default Display
      $loader = 'ctools_inline_block.panels_block_loader';
      $data = "{$panels_display_variant->getStorageType()}:{$panels_display_variant->getStorageId()}";
      $this->connection
        ->insert('inline_block')
        ->fields([
          'uuid' => $block->getConfiguration()['uuid'],
          'loader' => $loader,
          'data' => $data,
        ])
        ->execute();
    }
  }

  public function onVariantBlockDelete(BlockVariantEvent $event) {
    $block = $event->getBlock();
    $block_plugin_id = $block->getPluginId();
    if ($block_plugin_id && explode(':', $block_plugin_id)[0] == 'inline_content') {
      $this->connection
        ->delete('inline_block')
        ->condition('uuid', $block->getConfiguration()['uuid'])
        ->execute();
    }
  }

}
