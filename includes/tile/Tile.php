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
  public $weight;

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
    $this->weight = 0;
    $this->breakpoint = tiles_get_default_breakpoint();
    $this->width = tiles_get_max_step();
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
    foreach (array_keys(get_object_vars($this)) as $property) {
      if (!empty($block[$property])) {
        $this->{$property} = $block[$property];
      }
    }
  }
}
