<?php

/**
 * @file
 * Tile layout entity class.
 */

class TileLayout extends Entity {
  /**
   * Primary key for layout.
   *
   * @param int
   */
  protected $tid;

  /**
   * The container type for this layout.
   *
   * @param string
   */
  protected $container;

  /**
   * The selector used by TileContainer to determine layout.
   *
   * @param string
   */
  protected $selector;

  /**
   * Blocks handled by this layout.
   *
   * Keyed by breakpoint.
   *
   * @param array
   */
  protected $blocks = array();

  /**
   * Sorted blocks within regions and breakpoints.
   *
   * @param array
   */
  protected $sortedBlocks;


  /**
   * Set block in this layout.
   *
   * @param Object $block
   *   Block definition containing the following keys:
   *     - region
   *     - module
   *     - delta
   *     - breakpoint
   *     - width
   *     - weight
   */
  public function addBlock($block) {
    if (!isset($block->breakpoint)) {
      $block->breakpoint = 'default';
    }

    if (!isset($this->block[$block->breakpoint])) {
      $this->block[$block->breakpoint] = array();
    }

    $this->blocks[$block->breakpoint][] = $block;
    $this->sortedBlocks = NULL;
  }

  /**
   * Get all sorted blocks.
   *
   * @return array
   *   All blocks sorted and keyed by region and breakpoint.
   */
  public function getAllSortedBlocks() {
    if ($this->sortedBlocks === NULL) {
      $this->sortBlocks();
    }

    return $this->sortedBlocks;
  }

  /**
   * Get sorted blocks by a specific region and/or breakpoint.
   *
   * @return array
   *   Blocks for specified region.
   */
  public function getSortedBlocksByRegion($region, $breakpoint = NULL) {
    if ($this->sortedBlocks === NULL) {
      $this->sortBlocks();
    }

    if ($breakpoint) {
      return isset($this->sortedBlocks[$region][$breakpoint]) ? $this->sortedBlocks[$region][$breakpoint] : FALSE;
    }
    else {
      return isset($this->sortedBlocks[$region]) ? $this->sortedBlocks[$region] : FALSE;
    }
  }

  /**
   * Transform stored blocks into sorted array, keyed by region and breakpoint.
   */
  protected function sortBlocks() {
    $this->sortedBlocks = array();

    foreach ($this->blocks as $block) {
      if (!isset($this->sortedBlocks[$block->region])) {
        $this->sortedBlocks[$block->region] = array();
      }

      if (!isset($this->sortedBlocks[$block->region][$block->breakpoint])) {
        $this->sortedBlocks[$block->region][$block->breakpoint] = array();
      }

      $this->sortedBlocks[$block->region][$block->breakpoint][] = $block;
    }

    foreach ($this->sortedBlocks as $region => $breakpoints) {
      foreach ($breakpoints as $breakpoint => $blocks) {
        usort($this->sortedBlocks[$region][$breakpoint], function($a, $b) {
          $a_weight = (is_array($a) && isset($a->weight)) ? $a->weight : 0;
          $b_weight = (is_array($b) && isset($b->weight)) ? $b->weight : 0;
          if ($a_weight == $b_weight) {
            return 0;
          }
          return ($a_weight < $b_weight) ? -1 : 1;
        });
      }
    }
  }
}
