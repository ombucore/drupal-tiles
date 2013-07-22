<?php

/**
 * @file
 * Tiles section context condition.
 *
 * Triggered within individual sections in a sectioned field.
 */

class tiles_section_context_condition_section extends context_condition {
  function condition_values() {
    return array();
  }

  /**
   * Implements parent::execute().
   *
   * Return contexts immediately instead of queuing them in order to take action
   * on reactions within a single context.
   */
  function execute($value) {
    return $this->get_contexts($value);
  }
}
