<?php

namespace Drupal\external_comment;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form handler for comment type edit forms.
 */
class CommentTypeForm extends EntityForm {

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The comment manager.
   *
   * @var \Drupal\external_comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('logger.factory')->get('external_comment'),
      $container->get('external_comment.manager')
    );
  }

  /**
   * Constructs a CommentTypeFormController
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\external_comment\CommentManagerInterface $external_comment_manager
   *   The comment manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, LoggerInterface $logger, CommentManagerInterface $external_comment_manager) {
    $this->entityManager = $entity_manager;
    $this->logger = $logger;
    $this->commentManager = $external_comment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $external_comment_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $external_comment_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $external_comment_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\external_comment\Entity\CommentType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$external_comment_type->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $external_comment_type->getDescription(),
      '#description' => t('Describe this comment type. The text will be displayed on the <em>Comment types</em> administration overview page.'),
      '#title' => t('Description'),
    ];

    if ($external_comment_type->isNew()) {
      $options = [];
      foreach ($this->entityManager->getDefinitions() as $entity_type) {
        // Only expose entities that have field UI enabled, only those can
        // get comment fields added in the UI.
        if ($entity_type->get('field_ui_base_route')) {
          $options[$entity_type->id()] = $entity_type->getLabel();
        }
      }
      $form['target_entity_type_id'] = [
        '#type' => 'select',
        '#default_value' => $external_comment_type->getTargetEntityTypeId(),
        '#title' => t('Target entity type'),
        '#options' => $options,
        '#description' => t('The target entity type can not be changed after the comment type has been created.')
      ];
    }
    else {
      $form['target_entity_type_id_display'] = [
        '#type' => 'item',
        '#markup' => $this->entityManager->getDefinition($external_comment_type->getTargetEntityTypeId())->getLabel(),
        '#title' => t('Target entity type'),
      ];
    }

    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('external_comment', $external_comment_type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'external_comment',
          'bundle' => $external_comment_type->id(),
        ],
        '#default_value' => $language_configuration,
      ];

      $form['#submit'][] = 'language_configuration_element_submit';
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $external_comment_type = $this->entity;
    $status = $external_comment_type->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Comment type %label has been updated.', ['%label' => $external_comment_type->label()]));
      $this->logger->notice('Comment type %label has been updated.', ['%label' => $external_comment_type->label(), 'link' => $edit_link]);
    }
    else {
      $this->commentManager->addBodyField($external_comment_type->id());
      $this->commentManager->addEntityField($external_comment_type->id());
      drupal_set_message(t('Comment type %label has been added.', ['%label' => $external_comment_type->label()]));
      $this->logger->notice('Comment type %label has been added.', ['%label' => $external_comment_type->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirectUrl($external_comment_type->urlInfo('collection'));
  }

}
