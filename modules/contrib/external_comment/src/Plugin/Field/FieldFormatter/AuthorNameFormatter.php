<?php

namespace Drupal\external_comment\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'external_comment_username' formatter.
 *
 * @FieldFormatter(
 *   id = "external_comment_username",
 *   label = @Translation("Author name"),
 *   description = @Translation("Display the author name."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class AuthorNameFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var $comment \Drupal\external_comment\CommentInterface */
      $comment = $item->getEntity();
      $account = $comment->getOwner();
      $elements[$delta] = [
        '#theme' => 'username',
        '#account' => $account,
        '#cache' => [
          'tags' => $account->getCacheTags() + $comment->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === 'name' && $field_definition->getTargetEntityTypeId() === 'external_comment';
  }

}
