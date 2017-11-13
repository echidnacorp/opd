<?php

namespace Drupal\external_comment\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating comment views with the wizard.
 *
 * @ViewsWizard(
 *   id = "external_comment",
 *   base_table = "external_comment_field_data",
 *   title = @Translation("External Comments")
 * )
 */
class Comment extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';

  /**
   * Set default values for the filters.
   */
  protected $filters = [
    'status' => [
      'value' => TRUE,
      'table' => 'external_comment_field_data',
      'field' => 'status',
      'plugin_id' => 'boolean',
      'entity_type' => 'external_comment',
      'entity_field' => 'status',
    ],
    'status_node' => [
      'value' => TRUE,
      'table' => 'node_field_data',
      'field' => 'status',
      'plugin_id' => 'boolean',
      'relationship' => 'commented_node',
      'entity_type' => 'node',
      'entity_field' => 'status',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function rowStyleOptions() {
    $options = [];
    $options['entity:external_comment'] = $this->t('external comments');
    $options['fields'] = $this->t('fields');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access external comments';

    // Add a relationship to nodes.
    $display_options['relationships']['commented_node']['id'] = 'commented_node';
    $display_options['relationships']['commented_node']['table'] = 'external_comment__commented_node';
    $display_options['relationships']['commented_node']['field'] = 'commented_node';
    $display_options['relationships']['commented_node']['required'] = 1;
    $display_options['relationships']['commented_node']['plugin_id'] = 'standard';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Comment: Title */
    $display_options['fields']['subject']['id'] = 'subject';
    $display_options['fields']['subject']['table'] = 'external_comment_field_data';
    $display_options['fields']['subject']['field'] = 'subject';
    $display_options['fields']['subject']['entity_type'] = 'external_comment';
    $display_options['fields']['subject']['entity_field'] = 'subject';
    $display_options['fields']['subject']['label'] = '';
    $display_options['fields']['subject']['alter']['alter_text'] = 0;
    $display_options['fields']['subject']['alter']['make_link'] = 0;
    $display_options['fields']['subject']['alter']['absolute'] = 0;
    $display_options['fields']['subject']['alter']['trim'] = 0;
    $display_options['fields']['subject']['alter']['word_boundary'] = 0;
    $display_options['fields']['subject']['alter']['ellipsis'] = 0;
    $display_options['fields']['subject']['alter']['strip_tags'] = 0;
    $display_options['fields']['subject']['alter']['html'] = 0;
    $display_options['fields']['subject']['hide_empty'] = 0;
    $display_options['fields']['subject']['empty_zero'] = 0;
    $display_options['fields']['subject']['plugin_id'] = 'field';
    $display_options['fields']['subject']['type'] = 'string';
    $display_options['fields']['subject']['settings'] = ['link_to_entity' => TRUE];

    return $display_options;
  }

}
