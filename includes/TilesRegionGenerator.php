<?php

/**
 * @file
 * Default Drupal region generator.
 */

class TilesRegionGenerator extends TilesGenerator {
  /**
   * Implements parent::handleDelivery().
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
      print $this->renderRegion($page_callback_result);
    }

    // Perform end-of-request tasks.
    drupal_page_footer();
  }

  /**
   * Render the appropriate section for Tiles requests.
   *
   * @param array $page
   *   The result of a page callback.  Should be a renderable array of content.
   */
  protected function renderRegion($page) {
    $params = json_decode(file_get_contents('php://input'));

    // Build up proper regions.
    $this->prerenderPage($page);

    // Focus on a specific region.
    $region = $page[$params->region];

    // Pull out old build array before blocks have been added to rows.
    $region = $region['#original'];
    foreach ($params->blocks as $block) {
      $region[$block->module . '_' . $block->delta]['#weight'] = $block->weight;
      $region[$block->module . '_' . $block->delta]['#block']->width = $block->width;
    }

    // Rebuild blocks into tile rows.
    tiles_region_wrap($region);

    print drupal_render($region);
  }

  /**
   * Prerenders a drupal page array.
   *
   * Copied from drupal_render_page(), executes everything up to the actual
   * rendering of the page array.
   *
   * @param $page
   *   A string or array representing the content of a page. The array consists of
   *   the following keys:
   *
   * @see drupal_render_page()
   */
  protected function prerenderPage(&$page) {
    $main_content_display = &drupal_static('system_main_content_added', FALSE);

    // Allow menu callbacks to return strings or arbitrary arrays to render.
    // If the array returned is not of #type page directly, we need to fill
    // in the page with defaults.
    if (is_string($page) || (is_array($page) && (!isset($page['#type']) || ($page['#type'] != 'page')))) {
      drupal_set_page_content($page);
      $page = element_info('page');
    }

    // Modules can add elements to $page as needed in hook_page_build().
    foreach (module_implements('page_build') as $module) {
      $function = $module . '_page_build';
      $function($page);
    }
    // Modules alter the $page as needed. Blocks are populated into regions like
    // 'sidebar_first', 'footer', etc.
    drupal_alter('page', $page);

    // If no module has taken care of the main content, add it to the page now.
    // This allows the site to still be usable even if no modules that
    // control page regions (for example, the Block module) are enabled.
    if (!$main_content_display) {
      $page['content']['system_main'] = drupal_set_page_content();
    }
  }
}
