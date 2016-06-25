<?php

/**
 * @file
 * Handles moving/sizing tiles within sections.
 */

class TilesSectionContainer extends TilesContainer {
  protected $container = 'section';

  /**
   * Implements parent::getRegions().
   */
  public function getRegions() {
    // Sections are always stored in the content region.
    return array(
      'content' => 'Content',
    );
  }

  /**
   * Implements parent::renderManifest().
   */
  protected function renderManifest($page) {
    $manifest = $this->getManifest();

    // Create a dummy 'page' render array with a single region 'content' so
    // tiles reaction will place blocks within that region.
    $build = array('content' => array());

    $layout = $this->getLayout($manifest->selector);

    // Clear out any current blocks in passed region.
    $layout->clearTiles($manifest->region);

    // Split blocks out by breakpoint.
    $blocks = array();
    foreach ($manifest->blocks as $block) {
      foreach ($block->breakpoints as $key => $width) {
        $new_block = clone $block;
        unset($new_block->breakpoints);
        $new_block->breakpoint = $key;
        $new_block->width = $width;
        $blocks[] = $new_block;
      }
    }

    // Add blocks back to layout.
    foreach ($blocks as $block) {
      $layout->addBlock($block);
    }

    $this->buildPage($build, $layout);

    // Let tiles wrap blocks.
    $this->wrapRegion($build['content']);

    print drupal_render($build['content']);
  }
}
