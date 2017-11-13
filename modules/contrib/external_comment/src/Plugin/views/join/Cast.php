<?php

/**
 * @file
 * Contains Drupal\external_comment\Plugin\views\join\Cast.
 */

namespace Drupal\external_comment\Plugin\views\join;

use Drupal\views\Plugin\views\join\JoinPluginBase;

/**
 * Join handler for joins that join with a cast expression as the left field.
 *
 * For example:
 * @code
 * LEFT JOIN my_entity e ON (CAST(base_table.entity_id as CHAR(255)) = e.id
 * @endcode
 *
 * Join definition: same as \Drupal\views\Plugin\views\join\JoinPluginBase,
 * except:
 * - cast_as: The type to cast left side of the join clause to.
 *
 * @ingroup views_join_handlers
 * @ViewsJoin("cast")
 */
class Cast extends JoinPluginBase {
  /**
   * The type to cast left side of the join clause to.
   *
   * @var string
   */
  public $cast_as;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cast_as = $this->configuration['cast_as'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    if (empty($this->configuration['table formula'])) {
      $right_table = $this->table;
    }
    else {
      $right_table = $this->configuration['table formula'];
    }

    if ($this->leftTable) {
      $left = $view_query->getTableInfo($this->leftTable);
      $left_field = "$left[alias].$this->leftField";
    }
    else {
      // This can be used if left_field is a formula or something. It should be used only *very* rarely.
      $left_field = $this->leftField;
    }

    $condition = "CAST($left_field AS $this->cast_as) = $table[alias].$this->field";
    $arguments = [];

    // Tack on the extra.
    if (isset($this->extra)) {
      if (is_array($this->extra)) {
        $extras = [];
        foreach ($this->extra as $info) {
          // Do not require 'value' to be set; allow for field syntax instead.
          $info += [
            'value' => NULL,
          ];
          // Figure out the table name. Remember, only use aliases provided
          // if at all possible.
          $join_table = '';
          if (!array_key_exists('table', $info)) {
            $join_table = $table['alias'] . '.';
          }
          elseif (isset($info['table'])) {
            // If we're aware of a table alias for this table, use the table
            // alias instead of the table name.
            if (isset($left) && $left['table'] == $info['table']) {
              $join_table = $left['alias'] . '.';
            }
            else {
              $join_table = $info['table'] . '.';
            }
          }

          // Convert a single-valued array of values to the single-value case,
          // and transform from IN() notation to = notation
          if (is_array($info['value']) && count($info['value']) == 1) {
            $info['value'] = array_shift($info['value']);
          }

          if (is_array($info['value'])) {
            // With an array of values, we need multiple placeholders and the
            // 'IN' operator is implicit.
            $local_arguments = [];
            foreach ($info['value'] as $value) {
              $placeholder_i = ':views_join_condition_' . $select_query->nextPlaceholder();
              $local_arguments[$placeholder_i] = $value;
            }

            $operator = !empty($info['operator']) ? $info['operator'] : 'IN';
            $placeholder = '( ' . implode(', ', array_keys($local_arguments)) . ' )';
            $arguments += $local_arguments;
          }
          else {
            // With a single value, the '=' operator is implicit.
            $operator = !empty($info['operator']) ? $info['operator'] : '=';
            $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();
          }
          // Set 'field' as join table field if available or set 'left field' as
          // join table field is not set.
          if (isset($info['field'])) {
            $join_table_field = "$join_table$info[field]";
            // Allow the value to be set either with the 'value' element or
            // with 'left_field'.
            if (isset($info['left_field'])) {
              $placeholder = "$left[alias].$info[left_field]";
            }
            else {
              $arguments[$placeholder] = $info['value'];
            }
          }
          // Set 'left field' as join table field is not set.
          else {
            $join_table_field = "$left[alias].$info[left_field]";
            $arguments[$placeholder] = $info['value'];
          }
          $extras[] = "$join_table_field $operator $placeholder";
        }

        if ($extras) {
          if (count($extras) == 1) {
            $condition .= ' AND ' . array_shift($extras);
          }
          else {
            $condition .= ' AND (' . implode(' ' . $this->extraOperator . ' ', $extras) . ')';
          }
        }
      }
      elseif ($this->extra && is_string($this->extra)) {
        $condition .= " AND ($this->extra)";
      }
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }
}
