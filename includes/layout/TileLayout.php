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
  public $tid;

  /**
   * The container type for this layout.
   *
   * @param string
   */
  public $container;

  /**
   * The selector used by TileContainer to determine layout.
   *
   * @param string
   */
  public $selector;

  /**
   * Blocks handled by this layout.
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
   * Block info from hook_block_info().
   *
   * @param array
   */
  protected $blockInfo = array();


  /**
   * Set block in this layout.
   *
   * @param Object $block
   *   Block definition containing the following keys:
   *     - region
   *     - module
   *     - delta
   *     - breakpoint (defaults to 'default')
   *     - width (defaults to max ste from tiles_get_max_step())
   *     - weight
   */
  public function addBlock($block) {
    if (!isset($block->breakpoint)) {
      $block->breakpoint = 'default';
    }

    if (!isset($block->width)) {
      $block->width = tiles_get_max_step();
    }

    $this->blocks[] = $block;
    $this->sortedBlocks = NULL;
    $this->bockInfo = array();
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
   * @param string $region
   *   Region key to find blocks for.
   * @param string $breakpoint
   *   Breakpoint key to find blocks for.
   *
   * @return array
   *   Blocks for specified region/breakpoint.
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
   * Get renderable blocks for a region and/or breakpoint.
   *
   * @param string $region
   *   Region key to find blocks for.
   * @param string $breakpoint
   *   Breakpoint key to find blocks for.
   *
   * @return array
   *   Blocks for specified region/breakpoint suitable for drupal_render().
   */
  public function getRenderBlocks($region, $breakpoint = NULL) {
    $blocks = $this->getSortedBlocksByRegion($region, $breakpoint);
    if ($blocks) {
      // If no breakpoint has been set, use default breakpoint.
      if (!$breakpoint) {
        $blocks = $blocks['default'];
      }

      // Merge tile layout block info with info from hook_block_info().
      $info = $this->blockInfo();
      foreach ($blocks as $block) {
        $block->title = isset($info[$block->module][$block->delta]->title) ? $info[$block->module][$block->delta]->title : NULL;
        $block->cache = isset($info[$block->module][$block->delta]->cache) ? $info[$block->module][$block->delta]->cache : DRUPAL_NO_CACHE;
      }

      $blocks = _block_render_blocks($blocks);

      return _block_get_renderable_array($blocks);
    }
  }

  /**
   * Clear blocks from a given region, or all blocks.
   *
   * @param string $region
   *   Given region to clear blocks from. If no region is given, all blocks from
   *   this layout will be cleared.
   */
  public function clearBlocks($region = NULL) {
    if (!$region) {
      $this->blocks = array();
    }
    else {
      foreach ($this->blocks as $i => $block) {
        if ($block->region == $region) {
          unset($this->blocks[$i]);
        }
      }
    }
  }

  /**
   * Implements parent::save().
   */
  public function save() {
    $result = parent::save();

    // Save all blocks. In order to save blocks, clear out all existing block
    // references for layout. This means that a layout always needs to have
    // blocks attached (which it does if loaded via entity_load()).
    db_delete('tile_layout_blocks')
      ->condition('tid', $this->tid)
      ->execute();

    if ($this->blocks) {
      $query = db_insert('tile_layout_blocks')
        ->fields(array(
          'tid',
          'module',
          'delta',
          'region',
          'breakpoint',
          'weight',
          'width',
        ));
      foreach ($this->blocks as $block) {
        $query->values(array(
          'tid' => $this->tid,
          'module' => $block->module,
          'delta' => $block->delta,
          'region' => $block->region,
          'breakpoint' => $block->breakpoint,
          'weight' => $block->weight,
          'width' => $block->width,
        ));
      }
      $query->execute();
    }

    return $result;
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

  /**
   * Get info arrays for all blocks stored by this layout.
   *
   * Determines all unique modules for blocks on page.
   */
  protected function blockInfo() {
    if (empty($this->blockInfo)) {
      foreach ($this->blocks as $block) {
        if (!isset($this->blockInfo[$block->module])) {
          $this->blockInfo[$block->module] = module_invoke($block->module, 'block_info');
        }
      }
    }

    return $this->blockInfo;
  }
}
