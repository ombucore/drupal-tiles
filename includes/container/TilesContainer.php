<?php

/**
 * @file
 * Base class for Tiles container objects.
 *
 * Tiles containers are helper classes that provide the code necessary to
 * generate tile blocks into content.
 */

abstract class TilesContainer {
  /**
   * Short name for container.
   *
   * @param string
   */
  protected $container;

  /**
   * Get a tile layout for a given selector for the current container.
   *
   * If no layout entity exists, then a new one will be created.
   *
   * @param string $selector
   *   Selector of the layout to load.
   *
   * @return TileLayout
   *   TileLayout entity for the given selector.
   */
  public function getLayout($selector) {
    $layouts = entity_get_controller('tile_layout')->loadBySelector($selector, $this->container);
    if (!empty($layouts)) {
      $layout = array_shift($layouts);
      if ($this->hasAccess($layout)) {
        return $layout;
      }
      else {
        return FALSE;
      }
    }
    else {
      $layout = entity_create('tile_layout', array(
        'selector' => $selector,
        'container' => $this->container,
      ));
      return $layout;
    }
  }

  /**
   * Return a list of "regions" provided by this container.
   *
   * Regions in a tiles sense are just areas of content that a tile can be added
   * to. This does include, but not limited to, Drupal regions.
   *
   * @return array
   *   Array of regions appropriate for this container.
   */
  public function getRegions() {
  }

  /**
   * Get a list of block/bean types available for adding to this container.
   *
   * Defaults to all tile types provided by tiles_get_tile_types().
   *
   * @return array
   *   Array of blocks and bean types available for tiles in the form of
   *   MODULE__DELTA.
   */
  public function getTileTypes() {
    return tiles_get_tile_types();
  }

  /**
   * Checks if current user has access to view layout.
   *
   * @param TileLayout $layout
   *   The tile layout holding block references.
   *
   * @return bool
   *   TRUE if user has access to view layout, meaning blocks should be visible.
   */
  public function hasAccess($layout) {
    return TRUE;
  }

  /**
   * Place blocks from a layout into regions.
   *
   * @param array $page
   *   Page render array.
   * @param TileLayout $layout
   *   The tile layout holding block references.
   */
  public function buildPage(&$page, $layout) {
    // Load all region content assigned via blocks.
    $regions = $this->getRegions();
    foreach (array_keys($regions) as $region) {
      if ($blocks = $layout->getRenderTiles($region)) {

        // Are the blocks already sorted.
        $blocks_sorted = TRUE;

        // If blocks have already been placed in this region (most likely by
        // Block module), then merge in blocks from layout.
        if (isset($page[$region])) {
          $page[$region] = array_merge($page[$region], $blocks);

          // Restore the weights that Block module manufactured
          // @see _block_get_renderable_array()
          foreach ($page[$region] as &$block) {
            if (isset($block['#block']->weight)) {
              $block['#weight'] = $block['#block']->weight;
              $blocks_sorted = FALSE;
            }
          }
        }
        else {
          $page[$region] = $blocks;
        }

        $page[$region]['#sorted'] = $blocks_sorted;
      }
    }
  }

  /**
   * Handle page callback delivery from a Tiles request.
   *
   * Called by drupal_deliver_page when a request for a width/position change
   * from tiles.js is made.
   *
   * @param array $page_callback_result
   *   The result of a page callback. Can be one of:
   *   - NULL: to indicate no content.
   *   - An integer menu status constant: to indicate an error condition.
   *   - A string of HTML content.
   *   - A renderable array of content.
   *
   * @see drupal_deliver_html_page()
   */
  public function handleDelivery($page_callback_result) {
    // Emit the correct charset HTTP header, but not if the page callback
    // result is NULL, since that likely indicates that it printed something
    // in which case, no further headers may be sent, and not if code running
    // for this page request has already set the content type header.
    if (isset($page_callback_result) && is_null(drupal_get_http_header('Content-Type'))) {
      drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    }

    // Menu status constants are integers; page content is a string or array.
    if (is_int($page_callback_result)) {
      // Let drupal_deliver_html_page() handle errors.  The calling code should
      // check the response header.
      return drupal_deliver_html_page($page_callback_result);
    }
    elseif (isset($page_callback_result)) {
      // Print anything besides a menu constant, assuming it's not NULL or
      // undefined.
      print $this->renderManifest($page_callback_result);
    }

    // Perform end-of-request tasks.
    drupal_page_footer();
  }

  /**
   * Saves a manifest to appropriate context.
   */
  public function save() {
    $manifest = $this->getManifest();

    if (!empty($manifest->selector)) {
      $layout = $this->getLayout($manifest->selector);

      // Clear out any current blocks in passed region.
      $layout->clearTiles($manifest->region);

      // Split blocks out by breakpoint.
      $blocks = array();
      foreach ($manifest->blocks as $block) {
        foreach ($block->breakpoints as $key => $width) {
          $new_block = clone $block;
          // @todo: This needs to be properly integrated into an array in order
          // for TileLayout::save() to work correctly.
          $new_block->breakpoints = (array) $new_block->breakpoints;
          $new_block->breakpoint = $key;
          $new_block->width = $width;
          $blocks[] = $new_block;
        }
      }

      // Add blocks back to layout.
      foreach ($blocks as $block) {
        $layout->addBlock($block);
      }

      // Clear caches.
      cache_clear_all();

      return $layout->save();
    }
  }

  /**
   * Wraps blocks that belong to page regions in tile specific wrappers.
   *
   * @param array $region
   *   Region array.
   */
  public function wrapRegion(&$region) {
    $max_cols_per_row = tiles_get_max_step();

    // Make sure blocks are properly sorted.
    unset($region['#sorted']);
    $region_children = element_children($region, TRUE);

    $col_count = $row = 0;
    $row_key = 'row_' . $row;

    $region['#original'] = array();
    $region['rows'] = array('#theme_wrappers' => array('tiles_region'));
    $region['rows'][$row_key] = array('#theme_wrappers' => array('tiles_row'));

    foreach ($region_children as $delta) {

      // Only operate on blocks.
      if (!array_key_exists('#block', $region[$delta])) {
        continue;
      }

      $block = $region[$delta]['#block'];
      $width = $block->width + $block->offset;
      while ($width > $max_cols_per_row || $block->width == 1) {
        $block->width--;
        $width = $block->width + $block->offset;

        // @todo: for now set default breakpoint to same width. Need to revisit
        // once breakpoint behavior gets flushed out,
        $block->breakpoints['default'] = $block->width;
      }

      if (($col_count + $width) <= $max_cols_per_row) {
        $col_count += $width;
      }
      else {
        $col_count = $width;
        $row_key = 'row_' . ++$row;
        $region['rows'][$row_key] = array('#theme_wrappers' => array('tiles_row'));
      }

      // Add block to current row.
      $region['rows'][$row_key][$delta] = $region[$delta];
      $region['rows'][$row_key][$delta]['#theme_wrappers'][] = 'tiles_tile';

      // Stash the block in the #original key.
      $region['#original'][$delta] = $region[$delta];

      // Remove block from old position in region.
      unset($region[$delta]);
    }
  }

  /**
   * Render the appropriate section for Tiles requests.
   *
   * @param array $page
   *   The result of a page callback.  Should be a renderable array of content.
   */
  protected function renderManifest($page) {
    print drupal_render($page);
  }

  /**
   * Returns a manifest pushed by tiles.js.
   *
   * @return Object
   *   Class representing manifest from frontend.
   */
  protected function getManifest() {
    return json_decode(file_get_contents('php://input'));
  }
}
