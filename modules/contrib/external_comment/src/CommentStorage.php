<?php

namespace Drupal\external_comment;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines the storage handler class for comments.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for comment entities.
 */
class CommentStorage extends SqlContentEntityStorage implements CommentStorageInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a CommentStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, AccountInterface $current_user, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThread(CommentInterface $comment) {
    $field_storage = FieldStorageConfig::loadByName('external_comment', $this->entityManager->getStorage('external_comment_type')->load($comment->bundle())->getEntityReferenceFieldName());
    $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
    $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
    $query = $this->database->select('external_comment_field_data', 'c');
    $query->join($external_comment_entity_table, 'ce', 'c.cid = %alias.entity_id AND %alias.deleted = 0');
    $query->condition('ce.' . $target_id_column, $comment->getCommentedEntityId())
      ->condition('c.field_name', $comment->getFieldName())
      ->condition('c.entity_type', $comment->getCommentedEntityTypeId())
      ->condition('c.default_langcode', 1);
    $query->addExpression('MAX(c.thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThreadPerThread(CommentInterface $comment) {
    $field_storage = FieldStorageConfig::loadByName('external_comment', $this->entityManager->getStorage('external_comment_type')->load($comment->bundle())->getEntityReferenceFieldName());
    $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
    $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
    $query = $this->database->select('external_comment_field_data', 'c');
    $query->join($external_comment_entity_table, 'ce', 'c.cid = %alias.entity_id AND %alias.deleted = 0');
    $query->condition('ce.' . $target_id_column, $comment->getCommentedEntityId())
      ->condition('c.field_name', $comment->getFieldName())
      ->condition('c.entity_type', $comment->getCommentedEntityTypeId())
      ->condition('c.thread', $comment->getParentComment()->getThread() . '.%', 'LIKE')
      ->condition('c.default_langcode', 1);
    $query->addExpression('MAX(c.thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOrdinal(CommentInterface $comment, $external_comment_mode, $divisor = 1) {
    // Count how many comments (c1) are before $comment (c2) in display order.
    // This is the 0-based display ordinal.
    $field_storage = FieldStorageConfig::loadByName('external_comment', $this->entityManager->getStorage('external_comment_type')->load($comment->bundle())->getEntityReferenceFieldName());
    $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
    $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
    $query = $this->database->select('external_comment_field_data', 'c1');
    $query->join($external_comment_entity_table, 'ce1', 'c1.cid = %alias.entity_id AND %alias.deleted = 0');
    $query->innerJoin($external_comment_entity_table, 'ce2', 'ce2.' . $target_id_column . ' = ce1.' . $target_id_column . ' AND ce2.bundle = ce1.bundle');
    $query->join('external_comment_field_data', 'c2', '%alias.cid = ce2.entity_id AND ce2.deleted = 0');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('c2.cid', $comment->id());
    if (!$this->currentUser->hasPermission('administer external comments')) {
      $query->condition('c1.status', CommentInterface::PUBLISHED);
    }

    if ($external_comment_mode == CommentManagerInterface::EXTERNAL_COMMENT_MODE_FLAT) {
      // For rendering flat comments, cid is used for ordering comments due to
      // unpredictable behavior with timestamp, so we make the same assumption
      // here.
      $query->condition('c1.cid', $comment->id(), '<');
    }
    else {
      // For threaded comments, the c.thread column is used for ordering. We can
      // use the sorting code for comparison, but must remove the trailing
      // slash.
      $query->where('SUBSTRING(c1.thread, 1, (LENGTH(c1.thread) - 1)) < SUBSTRING(c2.thread, 1, (LENGTH(c2.thread) - 1))');
    }

    $query->condition('c1.default_langcode', 1);
    $query->condition('c2.default_langcode', 1);

    $ordinal = $query->execute()->fetchField();

    return ($divisor > 1) ? floor($ordinal / $divisor) : $ordinal;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewCommentPageNumber($total_comments, $new_comments, FieldableEntityInterface $entity, $field_name) {
    $field = $entity->getFieldDefinition($field_name);
    $comments_per_page = $field->getSetting('per_page');

    if ($total_comments <= $comments_per_page) {
      // Only one page of comments.
      $count = 0;
    }
    elseif ($field->getSetting('default_mode') == CommentManagerInterface::EXTERNAL_COMMENT_MODE_FLAT) {
      // Flat comments.
      $count = $total_comments - $new_comments;
    }
    else {
      // Threaded comments.

      // 1. Find all the threads with a new comment.
      $field_storage = FieldStorageConfig::loadByName('external_comment', 'commented_' . $entity->getEntityTypeId());
      $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
      $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
      $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
      $unread_threads_query = $this->database->select('external_comment_field_data', 'external_comment');
      $unread_threads_query->join($external_comment_entity_table, 'ce', 'external_comment.cid = %alias.entity_id AND %alias.deleted = 0');
      $unread_threads_query->fields('external_comment', ['thread'])
        ->condition('ce.' . $target_id_column, $entity->id())
        ->condition('external_comment.entity_type', $entity->getEntityTypeId())
        ->condition('external_comment.field_name', $field_name)
        ->condition('external_comment.status', CommentInterface::PUBLISHED)
        ->condition('external_comment.default_langcode', 1)
        ->orderBy('external_comment.created', 'DESC')
        ->orderBy('external_comment.cid', 'DESC')
        ->range(0, $new_comments);

      // 2. Find the first thread.
      $first_thread_query = $this->database->select($unread_threads_query, 'thread');
      $first_thread_query->addExpression('SUBSTRING(thread, 1, (LENGTH(thread) - 1))', 'torder');
      $first_thread = $first_thread_query
        ->fields('thread', ['thread'])
        ->orderBy('torder')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      // Remove the final '/'.
      $first_thread = substr($first_thread, 0, -1);

      // Find the number of the first comment of the first unread thread.
      $count = $this->database->query('SELECT COUNT(*) FROM {external_comment_field_data} c
                        INNER JOIN {' . $external_comment_entity_table . '} ce
                        ON c.cid = ce.entity_id AND ce.deleted = 0
                        WHERE ce.' . $target_id_column . ' = :entity_id
                        AND c.entity_type = :entity_type
                        AND c.field_name = :field_name
                        AND c.status = :status
                        AND SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1)) < :thread
                        AND c.default_langcode = 1', [
        ':status' => CommentInterface::PUBLISHED,
        ':entity_id' => $entity->id(),
        ':field_name' => $field_name,
        ':entity_type' => $entity->getEntityTypeId(),
        ':thread' => $first_thread,
      ])->fetchField();
    }

    return $comments_per_page > 0 ? (int) ($count / $comments_per_page) : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCids(array $comments) {
    return $this->database->select('external_comment_field_data', 'c')
      ->fields('c', ['cid'])
      ->condition('pid', array_keys($comments), 'IN')
      ->condition('default_langcode', 1)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   *
   * To display threaded comments in the correct order we keep a 'thread' field
   * and order by that value. This field keeps this data in
   * a way which is easy to update and convenient to use.
   *
   * A "thread" value starts at "1". If we add a child (A) to this comment,
   * we assign it a "thread" = "1.1". A child of (A) will have "1.1.1". Next
   * brother of (A) will get "1.2". Next brother of the parent of (A) will get
   * "2" and so on.
   *
   * First of all note that the thread field stores the depth of the comment:
   * depth 0 will be "X", depth 1 "X.X", depth 2 "X.X.X", etc.
   *
   * Now to get the ordering right, consider this example:
   *
   * 1
   * 1.1
   * 1.1.1
   * 1.2
   * 2
   *
   * If we "ORDER BY thread ASC" we get the above result, and this is the
   * natural order sorted by time. However, if we "ORDER BY thread DESC"
   * we get:
   *
   * 2
   * 1.2
   * 1.1.1
   * 1.1
   * 1
   *
   * Clearly, this is not a natural way to see a thread, and users will get
   * confused. The natural order to show a thread by time desc would be:
   *
   * 2
   * 1
   * 1.2
   * 1.1
   * 1.1.1
   *
   * which is what we already did before the standard pager patch. To achieve
   * this we simply add a "/" at the end of each "thread" value. This way, the
   * thread fields will look like this:
   *
   * 1/
   * 1.1/
   * 1.1.1/
   * 1.2/
   * 2/
   *
   * we add "/" since this char is, in ASCII, higher than every number, so if
   * now we "ORDER BY thread DESC" we get the correct order. However this would
   * spoil the reverse ordering, "ORDER BY thread ASC" -- here, we do not need
   * to consider the trailing "/" so we use a substring only.
   */
  public function loadThread(EntityInterface $entity, $field_name, $mode, $comments_per_page = 0, $pager_id = 0) {
    $field_storage = FieldStorageConfig::loadByName('external_comment', 'commented_' . $entity->getEntityTypeId());
    $table_mapping = \Drupal::entityManager()->getStorage('external_comment')->getTableMapping();
    $external_comment_entity_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $target_id_column = $table_mapping->getFieldColumnName($field_storage, 'target_id');
    $query = $this->database->select('external_comment_field_data', 'c');
    $query->join($external_comment_entity_table, 'ce', 'c.cid = %alias.entity_id AND %alias.deleted = 0');
    $query->addField('c', 'cid');
    $query
      ->condition('ce.' . $target_id_column, $entity->id())
      ->condition('c.entity_type', $entity->getEntityTypeId())
      ->condition('c.field_name', $field_name)
      ->condition('c.default_langcode', 1)
      ->addTag('entity_access')
      ->addTag('external_comment_filter')
      ->addMetaData('base_table', 'external_comment')
      ->addMetaData('entity', $entity)
      ->addMetaData('field_name', $field_name);

    if ($comments_per_page) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($comments_per_page);
      if ($pager_id) {
        $query->element($pager_id);
      }

      $count_query = $this->database->select('external_comment_field_data', 'c');
      $count_query->join($external_comment_entity_table, 'ce', 'c.cid = %alias.entity_id AND %alias.deleted = 0');
      $count_query->addExpression('COUNT(*)');
      $count_query
        ->condition('ce.' . $target_id_column, $entity->id())
        ->condition('c.entity_type', $entity->getEntityTypeId())
        ->condition('c.field_name', $field_name)
        ->condition('c.default_langcode', 1)
        ->addTag('entity_access')
        ->addTag('external_comment_filter')
        ->addMetaData('base_table', 'external_comment')
        ->addMetaData('entity', $entity)
        ->addMetaData('field_name', $field_name);
      $query->setCountQuery($count_query);
    }

    if (!$this->currentUser->hasPermission('administer external comments')) {
      $query->condition('c.status', CommentInterface::PUBLISHED);
      if ($comments_per_page) {
        $count_query->condition('c.status', CommentInterface::PUBLISHED);
      }
    }
    if ($mode == CommentManagerInterface::EXTERNAL_COMMENT_MODE_FLAT) {
      $query->orderBy('c.cid', 'ASC');
    }
    else {
      // See comment above. Analysis reveals that this doesn't cost too
      // much. It scales much much better than having the whole comment
      // structure.
      $query->addExpression('SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1))', 'torder');
      $query->orderBy('torder', 'ASC');
    }

    $cids = $query->execute()->fetchCol();

    $comments = [];
    if ($cids) {
      $comments = $this->loadMultiple($cids);
    }

    return $comments;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnapprovedCount() {
    return  $this->database->select('external_comment_field_data', 'c')
      ->condition('status', CommentInterface::NOT_PUBLISHED, '=')
      ->condition('default_langcode', 1)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
