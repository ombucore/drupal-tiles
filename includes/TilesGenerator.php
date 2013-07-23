<?php

/**
 * @file
 * Base class for Tiles generator objects.
 *
 * Tiles generators are helper classes that provide the code necessary to
 * generate tile blocks into content.
 */

class TilesGenerator {
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
  public function handleDelivery($page_callback_result) {}
}
