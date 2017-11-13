<?php

namespace Drupal\page_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\page_manager\PageVariantInterface;

/**
 * Controllers for Page Manager.
 */
class PageManagerController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_manager_page_variant
   *
   * @return string
   *   The title for a particular page.
   */
  public function pageTitle(PageVariantInterface $page_manager_page_variant) {
    // Get the variant context.
    $contexts = $page_manager_page_variant->getContexts();
    // Get the variant page entity.

    $tokens = [];

    foreach ($contexts as $key => $context){
      $tokens[$key] = $context->getContextValue();
    }

    // Get the page variant page title setting.
    $variant_title_setting = $page_manager_page_variant->getPageTitle();
    // Load the Token service and run our page title through it.
    $token_service = \Drupal::token();
    return $token_service->replace($variant_title_setting,
      $tokens);
  }

}