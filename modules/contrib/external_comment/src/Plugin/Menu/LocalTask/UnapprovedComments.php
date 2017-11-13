<?php

namespace Drupal\external_comment\Plugin\Menu\LocalTask;

use Drupal\external_comment\CommentStorageInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a local task that shows the amount of unapproved comments.
 */
class UnapprovedComments extends LocalTaskDefault implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The comment storage service.
   *
   * @var \Drupal\external_comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * Construct the UnapprovedComments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\external_comment\CommentStorageInterface $external_comment_storage
   *   The comment storage service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CommentStorageInterface $external_comment_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->commentStorage = $external_comment_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('external_comment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Unapproved comments (@count)', ['@count' => $this->commentStorage->getUnapprovedCount()]);
  }

}
