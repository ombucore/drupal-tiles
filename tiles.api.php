<?php

/**
 * @file
 * Hooks provided by the Tiles module.
 */

/**
 * Tell Tiles which bean types and blocks can be added to a page as a tile.
 * @TODO JD to document after implementation.
 */
function hook_tiles_info() {
  return array(
    'bean types' => array(
      'ombuslide'
    ),
    'blocks' => array(
      'user__login',
    )
  );
}

/**
 * Alter hook for hook_tiles_info.
 */
function hook_tiles_info_alter(&$tiles) {
}

/**
 * Tell Tiles which regions it can manage blocks in.
 */
function hook_tiles_regions(&$regions) {
  return array('content');
}
