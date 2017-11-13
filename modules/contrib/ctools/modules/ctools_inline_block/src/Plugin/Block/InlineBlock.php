<?php

namespace Drupal\ctools_inline_block\Plugin\Block;

use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a generic custom block type.
 *
 * @Block(
 *  id = "inline_content",
 *  admin_label = @Translation("Inline block"),
 *  category = @Translation("Inline"),
 *  deriver = "Drupal\ctools_inline_block\Plugin\Derivative\InlineBlock"
 * )
 */
class InlineBlock extends BlockContentBlock {

  /**
   * The inline block content entity.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (empty($this->entity) && isset($this->configuration['entity'])) {
      $this->entity = unserialize($this->configuration['entity']);
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = $this->entityManager->getViewModeOptionsByBundle('block_content', $this->getDerivativeId());
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('View mode'),
      '#description' => $this->t('Output the block in this view mode.'),
      '#default_value' => $this->configuration['view_mode'],
      '#access' => (count($options) > 1),
    );
    $form['title']['#description'] = $this->t('The title of the block as shown to the user.');
    $form['entity'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'inline_block_content',
      '#bundle' => $this->getDerivativeId(),
      // If the #default_value is NULL, a new entity will be created.
      '#default_value' => $this->getEntity(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
    /** @var \Drupal\block_content\BlockContentInterface $entity */
    $entity = !empty($form['settings']['entity']['#entity']) ? $form['settings']['entity']['#entity'] : $form['entity']['#entity'];
    $this->configuration['label'] = $entity->label();
    $this->configuration['langcode'] = $form_state->getValue('langcode');

    $this->entity = $entity;
    $this->configuration['entity'] = serialize($entity);
  }

}
