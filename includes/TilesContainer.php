<?php

/**
 * @file
 * Base class for Tiles container objects.
 *
 * Tiles containers are helper classes that provide the code necessary to
 * generate tile blocks into content.
 */

class TilesContainer {
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

    if (!empty($manifest->activeContext)) {
      $context = tiles_get_context('context', $manifest->activeContext);

      $return = tiles_assign_tiles($context, $manifest->blocks);

      return drupal_json_output($return);
    }
  }

  /**
   * Render the appropriate section for Tiles requests.
   *
   * @param array $page
   *   The result of a page callback.  Should be a renderable array of content.
   */
  protected function renderManifest($page) {
    print drupal_render($region);
  }

  /**
   * Returns a manifest pushed by tiles.js.
   *
   * @return stdClass
   *   Class representing manifest from frontend.
   */
  protected function getManifest() {
    return json_decode(file_get_contents('php://input'));
  }
}
