<?php

namespace Drupal\Tests\ctools_inline_block\FunctionalJavascript;

use Drupal\block\Entity\Block;
use Drupal\ctools_inline_block\Entity\BlockContent as InlineBlockContent;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * @group ctools_inline_block
 */
class InlineBlockFieldTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'block_content',
    'ctools_inline_block',
    'ctools_inline_block_test',
    'field',
    'field_ui',
    'file',
    'filter',
    'image',
    'text',
    'user',
  ];

  /**
   * Tests creating, editing and deleting an inline block with an image field.
   */
  public function testImageField() {
    $account = $this->createUser(['administer blocks']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/block/add/inline_content:basic/classy');

    $page = $this->getSession()->getPage();

    $image = \Drupal::moduleHandler()->getModule('image')->getPath() . '/sample.png';
    $page->attachFileToField('Puppies', \Drupal::root() . '/' . $image);

    $block_id = strtolower($this->randomMachineName(16));

    // Wait for the file element to do its AJAX business.
    $result = $this->getSession()->wait(10000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$result) {
      $this->fail('Timed out waiting for AJAX upload to complete.');
    }
    $page->fillField('Block description', $this->randomMachineName());
    $page->fillField('id', $block_id);
    $page->fillField('region', 'content');
    $page->pressButton('Save block');

    $block = Block::load($block_id);
    $block_content = $block->getPlugin()->getEntity();
    $this->assertInstanceOf(InlineBlockContent::class, $block_content);
    $this->assertFalse($block_content->field_puppies->isEmpty());

    /** @var \Drupal\file\FileInterface $image */
    $image = $block_content->field_puppies->entity;
    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $this->assertTrue($image->isPermanent());
    $this->assertNotEmpty($file_usage->listUsage($image));

    // Deleting the block should delete the inline block, which in turn should
    // cause the image to be marked as temporary since nothing will be using it.
    $block->delete();
    $this->assertEmpty($file_usage->listUsage($image));
    $image = \Drupal::entityTypeManager()->getStorage('file')->loadUnchanged($image->id());
    $this->assertTrue($image->isTemporary());
  }

}
