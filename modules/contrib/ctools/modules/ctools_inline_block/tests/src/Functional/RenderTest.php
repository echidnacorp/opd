<?php

namespace Drupal\Tests\ctools_inilne_block\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the block_content and inline_block plugins render identical HTML.
 *
 * @group ctools_inline_block
 */
class RenderTest extends BrowserTestBase {

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

  public function testInlineContentMarkup() {
    $assert = $this->assertSession();

    $block_content = BlockContent::create([
      'type' => 'basic',
      'info' => $this->randomMachineName(16),
      'body' => 'Yeah, I like animals better than people sometimes... Especially dogs.',
    ]);
    $block_content->save();

    // Place the block using the normal block_content plugin and render
    // it out. Nothing special here.
    $block = $this->placeBlock('block_content:' . $block_content->uuid(), [
      'region' => 'content',
    ]);
    $selector = '#block-' . $block->id();

    $this->drupalGet('/user/login');

    $concrete_output = $assert->elementExists('css', $selector)->getOuterHtml();

    // Use the inline_content plugin to display the same block_content entity,
    // serialized and stored in the plugin configuration.
    $settings = $block->get('settings');
    $settings['entity'] = serialize($block_content);
    $block
      ->set('plugin', 'inline_content:basic')
      ->set('settings', $settings)
      ->save();

    $this->getSession()->reload();
    $inline_output = $assert->elementExists('css', $selector)->getOuterHtml();

    $this->assertSame($concrete_output, $inline_output);
  }

}
