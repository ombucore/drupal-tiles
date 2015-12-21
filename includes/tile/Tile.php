<?php

/**
 * @file
 * Tile object.
 *
 * Holds metadata about how to render blocks in a tile grid system, e.g. width,
 * weight, etc.
 */

class Tile {
  /**
   * The primary identifier for a tile layout.
   */
  public $tid;

  /**
   * The module from which the block originates.
   *
   * For example, 'user' for the Who's Online block, and 'block' for any custom
   * blocks.
   */
  public $module;

  /**
   * Unique ID for block within a module.
   */
  public $delta;

  /**
   * Theme region within which the block is set.
   */
  public $region;

  /**
   * Breakpoint at which width and weight apply for this block.
   */
  public $breakpoint;

  /**
   * The weight of block.
   */
  public $weight = 0;

  /**
   * The width of block.
   */
  public $width;

  /**
   * The index status of a block.
   */
  public $indexable;

  /**
   * The grid offset of block.
   */
  public $offset;

  /**
   * Constructor.
   */
  public function __construct() {
    // Set defaults.
    if (!isset($this->breakpoint)) {
      $this->breakpoint = tiles_get_default_breakpoint();
    }
    if (!isset($this->width)) {
      $this->width = tiles_get_max_step();
    }
    if (!isset($this->offset)) {
      $this->offset = 0;
    }
  }

  /**
   * Helper function to load up a new Tile object given an array of values.
   *
   * @param Array $block
   *   Block definition containing the following keys:
   *     - region
   *     - module
   *     - delta
   *     - breakpoint (defaults to greatest breakpoint defined by theme
   *       breakpoints)
   *     - width (defaults to max step from tiles_get_max_step())
   *     - weight
   *     - indexable (should this tile be indexed along with parent layout)
   *     - offset
   */
  public function loadUp($block = array()) {
    $block = (array) $block;

    foreach (array_keys(get_object_vars($this)) as $property) {
      if (!empty($block[$property])) {
        $this->{$property} = $block[$property];
      }
    }
  }

  /**
   * Save tile object into database.
   */
  public function save() {
    $query = db_insert('tile_layout_blocks')
      ->fields(array(
        'tid',
        'module',
        'delta',
        'region',
        'breakpoint',
        'weight',
        'width',
        'indexable',
        'offset',
      ))
      ->values(array(
        'tid' => $this->tid,
        'module' => $this->module,
        'delta' => $this->delta,
        'region' => $this->region,
        'breakpoint' => $this->breakpoint,
        'weight' => $this->weight,
        'width' => $this->width,
        'indexable' => (int) $this->indexable,
        'offset' => $this->offset,
      ))
      ->execute();
  }
}
