<?php

namespace Drupal\Tests\ctools_inline_block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ctools_inline_block\Entity\BlockContent as InlineBlockContent;

/**
 * Tests the storage layer of inline blocks.
 *
 * @group ctools_inline_block
 */
class StorageTest extends KernelTestBase {

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
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('block_content');
    $this->installConfig('ctools_inline_block_test');

    $this->installEntitySchema('block_content');
    $this->installEntitySchema('file');

    $this->installSchema('ctools_inline_block', ['inline_block']);
  }

  /**
   * Tests that inline blocks go to /dev/null if saved on their own.
   */
  public function testLoadWithoutBlock() {
    $block_content = InlineBlockContent::create([
      'type' => 'basic',
      'info' => $this->randomMachineName(),
      'body' => 'Pasta ipsum dolor sit amet tripoline farfalle croxetti mezzelune spirali manicotti ditalini spaghetti calamarata ricciolini ciriole spirali cencioni.',
    ]);
    $block_content->save();

    $id = $block_content->id();
    $this->assertNotEmpty($id);
    $this->assertEquals($id, $block_content->uuid());

    $this->assertNull(InlineBlockContent::load($id));
    $this->assertEmpty(InlineBlockContent::loadMultiple([$id]));
    $this->assertEmpty(InlineBlockContent::loadMultiple());
  }

  /**
   * Tests loading inline blocks by UUID.
   *
   * @depends testLoadWithoutBlock
   */
  public function testLoadWithBlock() {
    $block_content = InlineBlockContent::create([
      'type' => 'basic',
      'info' => $this->randomMachineName(),
      'body' => 'Pasta ipsum dolor sit amet tripoline farfalle croxetti mezzelune spirali manicotti ditalini spaghetti calamarata ricciolini ciriole spirali cencioni.',
    ]);
    $block_content->save();
    $id = $block_content->id();

    $block = Block::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'inline_content:basic',
      'settings' => [
        'entity' => serialize($block_content),
      ],
    ]);
    $block->save();

    $this->assertInstanceOf(InlineBlockContent::class, InlineBlockContent::load($id));

    $loaded = InlineBlockContent::loadMultiple([$id]);
    $this->assertInstanceOf(InlineBlockContent::class, $loaded[$id]);
  }

  /**
   * Tests that inline blocks can be created like normal custom blocks.
   */
  public function testCreate() {
    $body = 'Pasta ipsum dolor sit amet quadrefiore vermicelli rigatoncini fettuccine timpano. Mezzelune penne trofie penne lisce trenne trennette timpano corzetti tagliatelle calamaretti capunti fedelini corzetti farfalline.';

    $block_content = InlineBlockContent::create([
      'type' => 'basic',
      'body' => $body,
    ]);

    // Assert that it's an inline block.
    $this->assertInstanceOf(InlineBlockContent::class, $block_content);
    $this->assertSame('inline_block_content', $block_content->getEntityTypeId());
    // Like any other entity, inline blocks should be new if unsaved.
    $this->assertTrue($block_content->isNew());

    // Assert that it has the same fields.
    $this->assertTrue($block_content->hasField('body'));
    $this->assertEquals($body, $block_content->body->value);
    $this->assertTrue($block_content->hasField('field_puppies'));
    $this->assertTrue($block_content->get('field_puppies')->isEmpty());
  }

  /**
   * Tests that inline blocks don't put anything in the database when saved.
   *
   * @depends testCreate
   */
  public function testSave() {
    $block_content = InlineBlockContent::create([
      'type' => 'test_type',
      'info' => $this->randomMachineName(),
      'body' => 'Creste di galli spaghettoni trennette pennette trennette penne zita linguettine orecchiette pappardelle gramigna casarecce.',
    ]);
    $block_content->save();

    // Inline blocks use their UUID as their ID.
    $this->assertEquals($block_content->id(), $block_content->uuid());
    // Like any other entity, inline blocks with an ID should not be new.
    $this->assertFalse($block_content->isNew());

    // Ensure that no concrete block_content entities were created.
    $count = \Drupal::entityQuery('block_content')->count()->execute();
    $this->assertNumeric($count);
    $this->assertEmpty($count);

    // Nothing should be written to the field tables.
    $this->assertTableIsEmpty('block_content__body');
    $this->assertTableIsEmpty('block_content_revision__body');

    // Inline blocks are invisible to entity queries.
    $count = \Drupal::entityQuery('inline_block_content')->count()->execute();
    $this->assertNumeric($count);
    $this->assertEmpty($count);

    // Nothing should be in the tracking table if saved directly.
    $this->assertTableIsEmpty('inline_block');
  }

  /**
   * Tests deleting an inline block directly.
   *
   * @depends testLoadWithBlock
   */
  public function testDirectDelete() {
    $block_content = InlineBlockContent::create([
      'type' => 'basic',
      'info' => $this->randomMachineName(),
      'body' => 'Pasta ipsum dolor sit amet tripoline farfalle croxetti mezzelune spirali manicotti ditalini spaghetti calamarata ricciolini ciriole spirali cencioni.',
    ]);
    $block_content->save();

    $block = Block::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'inline_content:basic',
      'settings' => [
        'entity' => serialize($block_content),
      ],
    ]);
    $block->save();

    // Because the inline block is associated with a real block, there should
    // be an entry in the tracking table.
    $this->assertTableIsNotEmpty('inline_block');

    // Deleting the inline block should clear it from the tracking table...
    $block_content->delete();
    $this->assertTableIsEmpty('inline_block');

    // ...which means it should no longer be loadable.
    $id = $block_content->id();
    $this->assertNull(InlineBlockContent::load($id));
  }

  /**
   * Tests deleting a block that contains an inline block.
   *
   * @depends testDirectDelete
   */
  public function testDeleteBlock() {
    $block_content = InlineBlockContent::create([
      'type' => 'basic',
      'info' => $this->randomMachineName(),
      'body' => 'Pasta ipsum dolor sit amet tripoline farfalle croxetti mezzelune spirali manicotti ditalini spaghetti calamarata ricciolini ciriole spirali cencioni.',
    ]);
    $block_content->save();

    $block = Block::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'inline_content:basic',
      'settings' => [
        'entity' => serialize($block_content),
      ],
    ]);
    $block->save();

    // Because the inline block is associated with a real block, there should
    // be an entry in the tracking table.
    $this->assertTableIsNotEmpty('inline_block');

    // Deleting the block should clear the inline block from the tracking table.
    $block->delete();
    $this->assertTableIsEmpty('inline_block');

    // ...which means it should no longer be loadable.
    $id = $block_content->id();
    $this->assertNull(InlineBlockContent::load($id));
  }

  /**
   * Asserts that a value is numeric.
   *
   * @param mixed $value
   *   The value to check.
   */
  protected function assertNumeric($value) {
    $this->assertTrue(is_numeric($value));
  }

  /**
   * Counts the rows in a database table.
   *
   * @param string $table
   *   The table to count.
   *
   * @return int
   *   The number of rows in the table.
   */
  protected function countTable($table) {
    $count = \Drupal::database()
      ->select($table)
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertNumeric($count);
    return (int) $count;
  }

  /**
   * Asserts that a database table is empty.
   *
   * @param string $table
   *   The table to check.
   */
  protected function assertTableIsEmpty($table) {
    $this->assertEmpty($this->countTable($table));
  }

  /**
   * Asserts that a database table is not empty.
   *
   * @param string $table
   *   The table to check.
   */
  protected function assertTableIsNotEmpty($table) {
    $this->assertNotEmpty($this->countTable($table));
  }

}
