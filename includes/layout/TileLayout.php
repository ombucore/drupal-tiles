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
   * There will be a element for each block within each breakpoint that the
   * block has a unique width for. This means there might be duplicate
   * block/delta elements that store different widths for different breakpoints.
   * These get transformed to a single element within $this->sortedBlocks using
   * $this->sortBlocks().
   *
   * @param array
   */
  protected $blocks = array();

  /**
   * Sorted blocks within regions.
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
   *     - breakpoint (defaults to greatest breakpoint defined by theme
   *       breakpoints)
   *     - width (defaults to max step from tiles_get_max_step())
   *     - weight
   *     - indexable (should this block be indexed along with parent layout)
   */
  public function addBlock($block) {
    if (!is_object($block)) {
      $block = (object) $block;
    }

    // Force bid to be {module}-{delta}. This is in part to be compatible with
    // block reactions in context, but also provides a quick way to determine
    // block source.
    $block->bid = $block->module . '-' . $block->delta;

    if (!isset($block->breakpoint)) {
      $block->breakpoint = tiles_get_default_breakpoint();
    }

    // Set default width to 100%.
    if (!isset($block->width)) {
      $block->width = tiles_get_max_step();
    }

    if (!isset($block->indexable)) {
      $block->indexable = tiles_get_indexable($block->module, $block->delta, $this->tid);
    }

    $this->blocks[] = $block;
    $this->sortedBlocks = NULL;
    $this->blockInfo = array();
  }

  /**
   * Get all sorted blocks.
   *
   * @return array
   *   All blocks sorted and keyed by region.
   */
  public function getAllSortedBlocks() {
    if ($this->sortedBlocks === NULL) {
      $this->sortBlocks();
    }

    return $this->sortedBlocks;
  }

  /**
   * Get sorted blocks by a specific region.
   *
   * @param string $region
   *   Region key to find blocks for.
   *
   * @return array
   *   Blocks for specified region.
   */
  public function getSortedBlocksByRegion($region) {
    if ($this->sortedBlocks === NULL) {
      $this->sortBlocks();
    }

    return isset($this->sortedBlocks[$region]) ? $this->sortedBlocks[$region] : FALSE;
  }

  /**
   * Get renderable blocks for a region.
   *
   * @param string $region
   *   Region key to find blocks for.
   *
   * @return array
   *   Blocks for specified region suitable for drupal_render().
   */
  public function getRenderBlocks($region) {
    $blocks = $this->getSortedBlocksByRegion($region);
    if ($blocks) {
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

    $this->sortBlocks();
    if ($this->sortedBlocks) {
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
        ));
      foreach ($this->sortedBlocks as $region => $blocks) {
        $weight = 0;
        foreach ($blocks as $block) {
          $query->values(array(
            'tid' => $this->tid,
            'module' => $block->module,
            'delta' => $block->delta,
            'region' => $block->region,
            'breakpoint' => $block->breakpoint,
            'weight' => $weight++,
            'width' => $block->width,
            'indexable' => (int) $block->indexable,
          ));
        }
      }
      $query->execute();
    }

    return $result;
  }

  /**
   * Transform stored blocks into sorted array, keyed by region.
   *
   * There might be multiple block definitions in $this->blocks that store
   * different breakpoint data. This method condenses that information into
   * a property array called "breakpoints". The width of the block will be set
   * to the default breakpoint width.
   */
  protected function sortBlocks() {
    $this->sortedBlocks = array();

    foreach ($this->blocks as $block) {
      if (!isset($this->sortedBlocks[$block->region])) {
        $this->sortedBlocks[$block->region] = array();
      }

      if (!isset($this->sortedBlocks[$block->region][$block->module . '-' . $block->delta])) {
        $this->sortedBlocks[$block->region][$block->module . '-' . $block->delta] = $block;
      }

      $this->sortedBlocks[$block->region][$block->module . '-' . $block->delta]->breakpoints[$block->breakpoint] = $block->width;
    }

    $default_breakpoint = tiles_get_default_breakpoint();
    foreach ($this->sortedBlocks as $region => $blocks) {
      uasort($this->sortedBlocks[$region], function($a, $b) {
        $a_weight = (is_object($a) && isset($a->weight)) ? $a->weight : 0;
        $b_weight = (is_object($b) && isset($b->weight)) ? $b->weight : 0;
        if ($a_weight == $b_weight) {
          return 0;
        }
        return ($a_weight < $b_weight) ? -1 : 1;
      });

      // Make sure width for block is set to default breakpoint width. If not
      // set, default to max width.
      foreach ($blocks as $key => $block) {
        $block->breakpoint = $default_breakpoint;
        $block->width = isset($block->breakpoints[$default_breakpoint]) ? $block->breakpoints[$default_breakpoint] : tiles_get_max_step();
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
