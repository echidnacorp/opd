<?php

namespace Drupal\Tests\facets_range_widget\Unit\Plugin\widget;

use Drupal\facets_range_widget\Plugin\facets\widget\RangeSliderWidget;
use Drupal\Tests\facets\Unit\Plugin\widget\WidgetTestBase;

/**
 * Unit test for widget.
 *
 * @group facets
 */
class RangeSliderWidgetTest extends WidgetTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->widget = new RangeSliderWidget();
  }

  /**
   * {@inheritdoc}
   */
  public function testGetQueryType() {
    $result = $this->widget->getQueryType($this->queryTypes);
    $this->assertEquals('range', $result);
  }

  /**
   * {@inheritdoc}
   */
  public function testDefaultConfiguration() {
    $default_config = $this->widget->defaultConfiguration();
    $expected = [
      'show_numbers' => FALSE,
      'prefix' => '',
      'suffix' => '',
      'min_type' => 'search_result',
      'min_value' => 0,
      'max_type' => 'search_result',
      'max_value' => 10,
      'step' => 1,
    ];
    $this->assertEquals($expected, $default_config);
  }

}
