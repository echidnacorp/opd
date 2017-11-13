<?php

namespace Drupal\external_comment\Plugin\Field\FieldType;

use Drupal\external_comment\CommentManagerInterface;
use Drupal\external_comment\Entity\CommentType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Plugin implementation of the 'external_comment' field type.
 *
 * @FieldType(
 *   id = "external_comment",
 *   label = @Translation("External Comments"),
 *   description = @Translation("This field manages configuration and presentation of comments on an entity."),
 *   list_class = "\Drupal\external_comment\CommentFieldItemList",
 *   default_widget = "external_comment_default",
 *   default_formatter = "external_comment_default"
 * )
 */
class CommentItem extends FieldItemBase implements CommentItemInterface {
  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'external_comment_type' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'default_mode' => CommentManagerInterface::EXTERNAL_COMMENT_MODE_THREADED,
      'per_page' => 50,
      'form_location' => CommentItemInterface::FORM_BELOW,
      'anonymous' => EXTERNAL_COMMENT_ANONYMOUS_MAYNOT_CONTACT,
      'preview' => DRUPAL_OPTIONAL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Comment status'))
      ->setRequired(TRUE);

    $properties['cid'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment ID'));

    $properties['last_external_comment_timestamp'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment timestamp'))
      ->setDescription(t('The time that the last comment was created.'));

    $properties['last_external_comment_name'] = DataDefinition::create('string')
      ->setLabel(t('Last comment name'))
      ->setDescription(t('The name of the user posting the last comment.'));

    $properties['last_external_comment_uid'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment user ID'));

    $properties['external_comment_count'] = DataDefinition::create('integer')
      ->setLabel(t('Number of comments'))
      ->setDescription(t('The number of comments.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'status' => [
          'description' => 'Whether comments are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $settings = $this->getSettings();

    $anonymous_user = new AnonymousUserSession();

    $element['default_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Threading'),
      '#default_value' => $settings['default_mode'],
      '#description' => t('Show comment replies in a threaded list.'),
    ];
    $element['per_page'] = [
      '#type' => 'number',
      '#title' => t('Comments per page'),
      '#default_value' => $settings['per_page'],
      '#required' => TRUE,
      '#min' => 10,
      '#max' => 1000,
      '#step' => 10,
    ];
    $element['anonymous'] = [
      '#type' => 'select',
      '#title' => t('Anonymous commenting'),
      '#default_value' => $settings['anonymous'],
      '#options' => [
        EXTERNAL_COMMENT_ANONYMOUS_MAYNOT_CONTACT => t('Anonymous posters may not enter their contact information'),
        EXTERNAL_COMMENT_ANONYMOUS_MAY_CONTACT => t('Anonymous posters may leave their contact information'),
        EXTERNAL_COMMENT_ANONYMOUS_MUST_CONTACT => t('Anonymous posters must leave their contact information'),
      ],
      '#access' => $anonymous_user->hasPermission('post external comments'),
    ];
    $element['form_location'] = [
      '#type' => 'checkbox',
      '#title' => t('Show reply form on the same page as comments'),
      '#default_value' => $settings['form_location'],
    ];
    $element['preview'] = [
      '#type' => 'radios',
      '#title' => t('Preview comment'),
      '#default_value' => $settings['preview'],
      '#options' => [
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // There is always a value for this field, it is one of
    // CommentItemInterface::OPEN, CommentItemInterface::CLOSED or
    // CommentItemInterface::HIDDEN.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    // @todo Inject entity storage once typed-data supports container injection.
    //   See https://www.drupal.org/node/2053415 for more details.
    $external_comment_types = CommentType::loadMultiple();
    $options = [];
    $entity_type = $this->getEntity()->getEntityTypeId();
    foreach ($external_comment_types as $external_comment_type) {
      if ($external_comment_type->getTargetEntityTypeId() == $entity_type) {
        $options[$external_comment_type->id()] = $external_comment_type->label();
      }
    }
    $element['external_comment_type'] = [
      '#type' => 'select',
      '#title' => t('Comment type'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Select the Comment type to use for this comment field. Manage the comment types from the <a href=":url">administration overview page</a>.', [':url' => $this->url('entity.external_comment_type.collection')]),
      '#default_value' => $this->getSetting('external_comment_type'),
      '#disabled' => $has_data,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $statuses = [
      CommentItemInterface::HIDDEN,
      CommentItemInterface::CLOSED,
      CommentItemInterface::OPEN,
    ];
    return [
      'status' => $statuses[mt_rand(0, count($statuses) - 1)],
    ];
  }

}
