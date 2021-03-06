<?php

namespace Drupal\external_comment;

use Drupal\external_comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\external_comment\Entity\CommentType;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\RoleInterface;

/**
 * Comment manager contains common functions to manage comment fields.
 */
class CommentManager implements CommentManagerInterface {
  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Whether the \Drupal\user\RoleInterface::AUTHENTICATED_ID can post external comments.
   *
   * @var bool
   */
  protected $authenticatedCanPostComments;

  /**
   * The user settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $userConfig;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Construct the CommentManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   *  @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->entityManager = $entity_manager;
    $this->userConfig = $config_factory->get('user.settings');
    $this->stringTranslation = $string_translation;
    $this->urlGenerator = $url_generator;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_type_id) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return [];
    }

    $map = $this->entityManager->getFieldMapByFieldType('external_comment');
    return isset($map[$entity_type_id]) ? $map[$entity_type_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function addBodyField($external_comment_type_id) {
    if (!FieldConfig::loadByName('external_comment', $external_comment_type_id, 'comment_body')) {
      // Attaches the body field by default.
      $field = $this->entityManager->getStorage('field_config')->create([
        'label' => 'Comment',
        'bundle' => $external_comment_type_id,
        'required' => TRUE,
        'field_storage' => FieldStorageConfig::loadByName('external_comment', 'comment_body'),
      ]);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('external_comment', $external_comment_type_id, 'default')
        ->setComponent('comment_body', [
          'type' => 'text_textarea',
        ])
        ->save();

      // Assign display settings for the 'default' view mode.
      entity_get_display('external_comment', $external_comment_type_id, 'default')
        ->setComponent('comment_body', [
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ])
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntityField($external_comment_type_id) {
    $external_comment_type = CommentType::load($external_comment_type_id);
    $field_name = $external_comment_type->getEntityReferenceFieldName();
    $field_storage = FieldStorageConfig::loadByName('external_comment', $field_name);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'module' => ['external_comment', 'entity_reference'],
        ],
        'id' => 'external_comment.' . $field_name,
        'field_name' => $field_name,
        'entity_type' => 'external_comment',
        'type' => 'entity_reference',
        'settings' => [
          'target_type' => $external_comment_type->getTargetEntityTypeId(),
          'handler_settings' => [],
          'handler' => 'default:' . $external_comment_type->getTargetEntityTypeId(),
        ],
        'module' => 'entity_reference',
        'locked' => TRUE,
        'cardinality' => 1,
        'translatable' => FALSE,
        'indexes' => [],
        'persist_with_no_fields' => TRUE,
      ]);
      $field_storage->save();
    }
    if (!FieldConfig::loadByName('external_comment', $external_comment_type_id, $field_name)) {
      $target_entity_type = $this->entityManager->getDefinition($external_comment_type->getTargetEntityTypeId());
      $external_comment_type = CommentType::load($external_comment_type_id);
      // Attaches the body field by default.
      $field = $this->entityManager->getStorage('field_config')->create([
        'label' => 'Commented ' . $target_entity_type->getLabel(),
        'bundle' => $external_comment_type_id,
        'required' => TRUE,
        'field_storage' => $field_storage,
        'settings' => [
          'target_type' => $external_comment_type->getTargetEntityTypeId(),
          'handler_settings' => [],
          'handler' => 'default:' . $external_comment_type->getTargetEntityTypeId(),
        ],
      ]);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('external_comment', $external_comment_type_id, 'default')
        ->removeComponent($field_name)
        ->save();

      // Assign display settings for the 'default' view mode.
      entity_get_display('external_comment', $external_comment_type_id, 'default')
        ->removeComponent($field_name)
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function forbiddenMessage(EntityInterface $entity, $field_name) {
    if (!isset($this->authenticatedCanPostComments)) {
      // We only output a link if we are certain that users will get the
      // permission to post external comments by logging in.
      $this->authenticatedCanPostComments = $this->entityManager
        ->getStorage('user_role')
        ->load(RoleInterface::AUTHENTICATED_ID)
        ->hasPermission('post external comments');
    }

    if ($this->authenticatedCanPostComments) {
      // We cannot use the redirect.destination service here because these links
      // sometimes appear on /node and taxonomy listing pages.
      if ($entity->get($field_name)->getFieldDefinition()->getSetting('form_location') == CommentItemInterface::FORM_SEPARATE_PAGE) {
        $external_comment_reply_parameters = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity' => $entity->id(),
          'field_name' => $field_name,
        ];
        $destination = ['destination' => $this->url('external_comment.reply', $external_comment_reply_parameters, ['fragment' => 'comment-form'])];
      }
      else {
        $destination = ['destination' => $entity->url('canonical', ['fragment' => 'comment-form'])];
      }

      if ($this->userConfig->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
        // Users can register themselves.
        return $this->t('<a href=":login">Log in</a> or <a href=":register">register</a> to post external comments', [
          ':login' => $this->urlGenerator->generateFromRoute('user.login', [], ['query' => $destination]),
          ':register' => $this->urlGenerator->generateFromRoute('user.register', [], ['query' => $destination]),
        ]);
      }
      else {
        // Only admins can add new users, no public registration.
        return $this->t('<a href=":login">Log in</a> to post external comments', [
          ':login' => $this->urlGenerator->generateFromRoute('user.login', [], ['query' => $destination]),
        ]);
      }
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCountNewComments(EntityInterface $entity, $field_name = NULL, $timestamp = 0) {
    // @todo Replace module handler with optional history service injection
    //   after https://www.drupal.org/node/2081585.
    if ($this->currentUser->isAuthenticated() && $this->moduleHandler->moduleExists('history')) {
      // Retrieve the timestamp at which the current user last viewed this entity.
      if (!$timestamp) {
        if ($entity->getEntityTypeId() == 'node') {
          $timestamp = history_read($entity->id());
        }
        else {
          $function = $entity->getEntityTypeId() . '_last_viewed';
          if (function_exists($function)) {
            $timestamp = $function($entity->id());
          }
          else {
            // Default to 30 days ago.
            // @todo Remove once https://www.drupal.org/node/1029708 lands.
            $timestamp = EXTERNAL_COMMENT_NEW_LIMIT;
          }
        }
      }
      $timestamp = ($timestamp > HISTORY_READ_LIMIT ? $timestamp : HISTORY_READ_LIMIT);

      // Use the timestamp to retrieve the number of new comments.
      $query = $this->entityManager->getStorage('external_comment')->getQuery()
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('commented_' . $entity->getEntityTypeId() . '.target_id', $entity->id())
        ->condition('created', $timestamp, '>')
        ->condition('status', CommentInterface::PUBLISHED);
      if ($field_name) {
        // Limit to a particular field.
        $query->condition('field_name', $field_name);
      }

      return $query->count()->execute();
    }
    return FALSE;
  }

}
