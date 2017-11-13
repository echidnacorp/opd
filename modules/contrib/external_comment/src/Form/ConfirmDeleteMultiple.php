<?php

namespace Drupal\external_comment\Form;

use Drupal\external_comment\CommentStorageInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the comment multiple delete confirmation form.
 */
class ConfirmDeleteMultiple extends ConfirmFormBase {

  /**
   * The comment storage.
   *
   * @var \Drupal\external_comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * An array of comments to be deleted.
   *
   * @var \Drupal\external_comment\CommentInterface[]
   */
  protected $comments;

  /**
   * Creates an new ConfirmDeleteMultiple form.
   *
   * @param \Drupal\external_comment\CommentStorageInterface $external_comment_storage
   *   The comment storage.
   */
  public function __construct(CommentStorageInterface $external_comment_storage) {
    $this->commentStorage = $external_comment_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('external_comment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_comment_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete these comments and all their children?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('external_comment.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete comments');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $edit = $form_state->getUserInput();

    $form['external_comments'] = [
      '#prefix' => '<ul>',
      '#suffix' => '</ul>',
      '#tree' => TRUE,
    ];
    // array_filter() returns only elements with actual values.
    $external_comment_counter = 0;
    $this->comments = $this->commentStorage->loadMultiple(array_keys(array_filter($edit['external_comments'])));
    foreach ($this->comments as $comment) {
      $cid = $comment->id();
      $form['external_comments'][$cid] = [
        '#type' => 'hidden',
        '#value' => $cid,
        '#prefix' => '<li>',
        '#suffix' => Html::escape($comment->label()) . '</li>'
      ];
      $external_comment_counter++;
    }
    $form['operation'] = ['#type' => 'hidden', '#value' => 'delete'];

    if (!$external_comment_counter) {
      drupal_set_message($this->t('There do not appear to be any comments to delete, or your selected comment was deleted by another administrator.'));
      $form_state->setRedirect('external_comment.admin');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      $this->commentStorage->delete($this->comments);
      $count = count($form_state->getValue('external_comments'));
      $this->logger('external_comment')->notice('Deleted @count comments.', ['@count' => $count]);
      drupal_set_message($this->formatPlural($count, 'Deleted 1 comment.', 'Deleted @count comments.'));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
