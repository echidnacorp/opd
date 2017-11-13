<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

use Drupal\external_comment\CommentInterface;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links of a comment.
 *
 * @param array &$links
 *   A renderable array representing the comment links.
 * @param \Drupal\external_comment\CommentInterface $entity
 *   The comment being rendered.
 * @param array &$context
 *   Various aspects of the context in which the comment links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the comment is being viewed
 *   - 'langcode': the language in which the comment is being viewed
 *   - 'commented_entity': the entity to which the comment is attached
 *
 * @see \Drupal\external_comment\CommentViewBuilder::renderLinks()
 * @see \Drupal\external_comment\CommentViewBuilder::buildLinks()
 */
function hook_external_comment_links_alter(array &$links, CommentInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__external_comment__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'comment-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('external_comment_test.report', ['external_comment' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("external/comment/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
