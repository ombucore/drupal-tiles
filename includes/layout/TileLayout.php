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
   * Tiles handled by this layout.
   *
   * There will be a element for each tile within each breakpoint that the
   * tile has a unique width for. This means there might be duplicate
   * tile/delta elements that store different widths for different breakpoints.
   * These get transformed to a single element within $this->sortedTiles using
   * $this->sortTiles().
   *
   * @param array
   */
  protected $tiles = array();

  /**
   * Sorted tiles within regions.
   *
   * @param array
   */
  protected $sortedTiles;

  /**
   * Block info from hook_block_info().
   *
   * @param array
   */
  protected $blockInfo = array();

  /**
   * Add new tile via Block array definition.
   *
   * This preserves the legacy way of adding block, e.g.:
   *
   * @code
   * $layout->addBlock(array(
   *  'region' => 'content',
   *  'module' => 'bean',
   *  'delta' => 'delta',
   *  'weight' => 1,
   * ));
   * @endcode
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
   */
  public function addBlock($block) {
    $tile = new Tile();
    $tile->loadUp($block);
    $this->addTile($tile);
  }

  /**
   * Set tile in this layout.
   *
   * @param Object $tile
   *   Tile definition containing the following keys:
   *     - region
   *     - module
   *     - delta
   *     - breakpoint (defaults to greatest breakpoint defined by theme
   *       breakpoints)
   *     - width (defaults to max step from tiles_get_max_step())
   *     - weight
   *     - indexable (should this tile be indexed along with parent layout)
   */
  public function addTile(Tile $tile) {
    $this->tiles[] = $tile;
    $this->sortedTiles = NULL;
    $this->blockInfo = array();
  }

  /**
   * Get all tiles.
   */
  public function getTiles() {
    return $this->tiles;
  }

  /**
   * Get all sorted tiles.
   *
   * @return array
   *   All tiles sorted and keyed by region.
   */
  public function getAllSortedTiles() {
    if ($this->sortedTiles === NULL) {
      $this->sortTiles();
    }

    return $this->sortedTiles;
  }

  /**
   * Get sorted tiles by a specific region.
   *
   * @param string $region
   *   Region key to find tiles for.
   *
   * @return array
   *   Tiles for specified region.
   */
  public function getSortedTilesByRegion($region) {
    if ($this->sortedTiles === NULL) {
      $this->sortTiles();
    }

    return isset($this->sortedTiles[$region]) ? $this->sortedTiles[$region] : FALSE;
  }

  /**
   * Get renderable tiles for a region.
   *
   * @param string $region
   *   Region key to find tiles for.
   *
   * @return array
   *   Tiles for specified region suitable for drupal_render().
   */
  public function getRenderTiles($region) {
    $tiles = $this->getSortedTilesByRegion($region);
    if ($tiles) {
      // Merge tile layout tile info with info from hook_tile_info().
      $info = $this->blockInfo();
      foreach ($tiles as $tile) {
        $tile->title = isset($info[$tile->module][$tile->delta]->title) ? $info[$tile->module][$tile->delta]->title : NULL;
        $tile->cache = isset($info[$tile->module][$tile->delta]->cache) ? $info[$tile->module][$tile->delta]->cache : DRUPAL_NO_CACHE;
      }

      $tiles = _block_render_blocks($tiles);

      return _block_get_renderable_array($tiles);
    }
  }

  /**
   * Clear tiles from a given region, or all tiles.
   *
   * @param string $region
   *   Given region to clear tiles from. If no region is given, all tiles from
   *   this layout will be cleared.
   */
  public function clearTiles($region = NULL) {
    if (!$region) {
      $this->tiles = array();
    }
    else {
      foreach ($this->tiles as $i => $tile) {
        if ($tile->region == $region) {
          unset($this->tiles[$i]);
        }
      }
    }
  }

  /**
   * Implements parent::save().
   */
  public function save() {
    $result = parent::save();

    // Save all tiles. In order to save tiles, clear out all existing tile
    // references for layout. This means that a layout always needs to have
    // tiles attached (which it does if loaded via entity_load()).
    db_delete('tile_layout_blocks')
      ->condition('tid', $this->tid)
      ->execute();

    $this->sortTiles();
    if ($this->sortedTiles) {
      foreach ($this->sortedTiles as $region => $tiles) {
        $weight = 0;
        foreach ($tiles as $tile) {
          $tile->tid = $this->tid;
          $tile->weight = $weight++;
          $tile->save();
        }
      }
    }

    return $result;
  }

  /**
   * Transform stored tiles into sorted array, keyed by region.
   *
   * There might be multiple tile definitions in $this->tiles that store
   * different breakpoint data. This method condenses that information into
   * a property array called "breakpoints". The width of the tile will be set
   * to the default breakpoint width.
   */
  protected function sortTiles() {
    $this->sortedTiles = array();

    foreach ($this->tiles as $tile) {
      if (!isset($this->sortedTiles[$tile->region])) {
        $this->sortedTiles[$tile->region] = array();
      }

      if (!isset($this->sortedTiles[$tile->region][$tile->module . '-' . $tile->delta])) {
        $this->sortedTiles[$tile->region][$tile->module . '-' . $tile->delta] = $tile;
      }

      $this->sortedTiles[$tile->region][$tile->module . '-' . $tile->delta]->breakpoints[$tile->breakpoint] = $tile->width;
    }

    $default_breakpoint = tiles_get_default_breakpoint();
    foreach ($this->sortedTiles as $region => $tiles) {
      uasort($this->sortedTiles[$region], function($a, $b) {
        $a_weight = (is_object($a) && isset($a->weight)) ? $a->weight : 0;
        $b_weight = (is_object($b) && isset($b->weight)) ? $b->weight : 0;
        if ($a_weight == $b_weight) {
          return 0;
        }
        return ($a_weight < $b_weight) ? -1 : 1;
      });

      // Make sure width for tile is set to default breakpoint width. If not
      // set, default to max width.
      foreach ($tiles as $key => $tile) {
        $tile->breakpoint = $default_breakpoint;
        $tile->width = isset($tile->breakpoints[$default_breakpoint]) ? $tile->breakpoints[$default_breakpoint] : tiles_get_max_step();
      }
    }
  }

  /**
   * Get info arrays for all tiles stored by this layout.
   *
   * Determines all unique modules for tiles on page.
   */
  protected function blockInfo() {
    if (empty($this->blockInfo)) {
      foreach ($this->tiles as $tile) {
        if (!isset($this->blockInfo[$tile->module])) {
          $this->blockInfo[$tile->module] = module_invoke($tile->module, 'block_info');
        }
      }
    }

    return $this->blockInfo;
  }
}
