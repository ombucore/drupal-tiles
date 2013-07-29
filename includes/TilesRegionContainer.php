<?php

/**
 * @file
 * Default Drupal region container.
 */

class TilesRegionContainer extends TilesContainer {
  /**
   * Implements parent:getRegions().
   */
  public function getRegions($context_name = '') {
    // @todo this should be hookable or configurable.
    return system_region_list(variable_get('theme_default', NULL));
  }

  /**
   * Implements parent:renderManifest().
   */
  protected function renderManifest($page) {
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
