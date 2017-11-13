<?php

namespace Drupal\Tests\od\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Open Data installation profile expectations.
 *
 * @group od
 */
class OpenDataTest extends BrowserTestBase {

  /**
   * Installation profile.
   *
   * @var string
   */
  protected $profile = 'od';

  /**
   * Test for the login.
   */
  public function testOpenDataLogin() {
    // Create a user to check the login.
    $user = $this->createUser();

    // Log in our user.
    $this->drupalLogin($user);

    // Verify that logged in user can access the logout link.
    $this->drupalGet('user');

    $this->assertLinkByHref('/user/logout');
  }

}
