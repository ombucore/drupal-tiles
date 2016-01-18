<?php

/**
 * @file
 * Controller for TileLayout entities.
 *
 * Provides additional support for loading block data
 */

class TileLayoutController extends EntityAPIController {
  /**
   * Implements parent::load().
   *
   * Load up block layout info for each entity.
   */
  function load($ids = array(), $conditions = array()) {
    $entities = parent::load($ids, $conditions);

    $tiles = db_query('SELECT * FROM {tile_layout_blocks} WHERE tid in (:tids)', array(
      ':tids' => array_keys($entities),
    ), array(
      'fetch' => 'Tile',
    ));
    foreach ($tiles as $tile) {
      if (isset($entities[$tile->tid])) {
        $entities[$tile->tid]->addTile($tile);
      }
    }

    return $entities;
  }

  /**
   * Load layout(s) by selector.
   *
   * @param string $selector
   *   Selector of the layout to load.
   * @param string $container
   *   Optional tiles container to limit selector to.
   *
   * @return array
   *   Array of loaded TileLayout entities.
   */
  function loadBySelector($selector, $container = NULL) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'tile_layout')
      ->propertyCondition('selector', $selector);

    if ($container) {
      $query->propertyCondition('container', $container);
    }

    $result = $query->execute();
    if (!empty($result['tile_layout'])) {
      return $this->load(array_keys($result['tile_layout']));
    }
  }
}
