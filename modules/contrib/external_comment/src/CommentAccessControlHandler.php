<?php

namespace Drupal\external_comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\external_comment\Entity\CommentType;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\external_comment\Entity\Comment
 */
class CommentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\external_comment\CommentInterface|\Drupal\user\EntityOwnerInterface $entity */

    $external_comment_admin = $account->hasPermission('administer external comments');
    if ($operation == 'approve') {
      return AccessResult::allowedIf($external_comment_admin && !$entity->isPublished())
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    if ($external_comment_admin) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));
    }

    switch ($operation) {
      case 'view':
        $access_result = AccessResult::allowedIf($account->hasPermission('access external comments') && $entity->isPublished())->cachePerPermissions()->addCacheableDependency($entity)
          ->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));
        if (!$access_result->isAllowed()) {
          $access_result->setReason("The 'access external comments' permission is required and the comment must be published.");
        }

        return $access_result;

      case 'update':
        return AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $account->hasPermission('edit own external comments'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'post external comments');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($operation == 'edit') {
      // Only users with the "administer external comments" permission can edit
      // administrative fields.
      $administrative_fields = [
        'uid',
        'status',
        'created',
        'date',
      ];
      if (in_array($field_definition->getName(), $administrative_fields, TRUE)) {
        return AccessResult::allowedIfHasPermission($account, 'administer external comments');
      }

      /** @var \Drupal\external_comment\CommentInterface $entity */
      $entity = $items->getEntity();

      // No user can change read-only fields.
      $read_only_fields = [
        'hostname',
        'changed',
        'cid',
        'thread',
      ];
      // These fields can be edited during comment creation.
      $create_only_fields = [
        'external_comment_type',
        'uuid',
        CommentType::load($entity->bundle())->getEntityReferenceFieldName(),
        'entity_type',
        'field_name',
        'pid',
      ];
      if ($items && ($entity = $items->getEntity()) && $entity->isNew() && in_array($field_definition->getName(), $create_only_fields, TRUE)) {
        // We are creating a new comment, user can edit create only fields.
        return AccessResult::allowedIfHasPermission($account, 'post external comments')->addCacheableDependency($entity);
      }
      // We are editing an existing comment - create only fields are now read
      // only.
      $read_only_fields = array_merge($read_only_fields, $create_only_fields);
      if (in_array($field_definition->getName(), $read_only_fields, TRUE)) {
        return AccessResult::forbidden();
      }

      // If the field is configured to accept anonymous contact details - admins
      // can edit name, homepage and mail. Anonymous users can also fill in the
      // fields on comment creation.
      if (in_array($field_definition->getName(), ['name', 'mail', 'homepage'], TRUE)) {
        if (!$items) {
          // We cannot make a decision about access to edit these fields if we
          // don't have any items and therefore cannot determine the Comment
          // entity. In this case we err on the side of caution and prevent edit
          // access.
          return AccessResult::forbidden();
        }
        $is_name = $field_definition->getName() === 'name';
        $commented_entity = $entity->getCommentedEntity();
        $anonymous_contact = $commented_entity->get($entity->getFieldName())->getFieldDefinition()->getSetting('anonymous');
        $admin_access = AccessResult::allowedIfHasPermission($account, 'administer external comments');
        $anonymous_access = AccessResult::allowedIf($entity->isNew() && $account->isAnonymous() && ($anonymous_contact != EXTERNAL_COMMENT_ANONYMOUS_MAYNOT_CONTACT || $is_name) && $account->hasPermission('post external comments'))
          ->cachePerPermissions()
          ->addCacheableDependency($entity)
          ->addCacheableDependency($field_definition->getConfig($commented_entity->bundle()))
          ->addCacheableDependency($commented_entity);
        return $admin_access->orIf($anonymous_access);
      }
    }

    if ($operation == 'view') {
      // Nobody has access to the hostname.
      if ($field_definition->getName() == 'hostname') {
        return AccessResult::forbidden();
      }
      // The mail field is hidden from non-admins.
      if ($field_definition->getName() == 'mail') {
        return AccessResult::allowedIfHasPermission($account, 'administer external comments');
      }
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
