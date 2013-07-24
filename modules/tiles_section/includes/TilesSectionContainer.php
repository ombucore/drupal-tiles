<?php

/**
 * @file
 * Handles moving/sizing tiles within sections.
 */

class TilesSectionContainer extends TilesContainer {
  /**
   * Implements parent::renderManifest().
   */
  protected function renderManifest($page) {
    $manifest = $this->getManifest();

    // Unset currently active conditions
    context_clear();

    // Temporarily set contexts to be active.
    $context = context_load($manifest->activeContext);
    context_condition_met($context, 'tiles_section');

    // Trigger tiles reaction.
    if ($plugin = context_get_plugin('reaction', 'tiles')) {
      // Create a dummy 'page' render array with a single region 'content' so
      // tiles reaction will place blocks within that region.
      $build = array('content' => array());

      drupal_static_reset('context_reaction_block_list');
      $plugin->execute($build);

      $tiles = &$build['content'];

      if ($tiles) {
        // Get tiles width for each block.
        foreach ($manifest->blocks as $block) {
          $block_key = $block->module . '_' . $block->delta;
          $tiles[$block->module . '_' . $block->delta]['#weight'] = $block->weight;
          $tiles[$block->module . '_' . $block->delta]['#block']->width = $block->width;
        }

        // Let tiles wrap blocks.
        tiles_region_wrap($build['content']);

        print drupal_render($build['content']);
      }
    }
  }
}
