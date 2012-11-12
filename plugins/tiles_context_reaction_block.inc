<?php

/**
 * Expose blocks with proper weights as context reactions.
 *
 * This context gets triggered after context_reaction_block, so weights of
 * blocks assigned to this context will take precedence.
 */
class tiles_context_reaction_block extends context_reaction_block {
  /**
   * Extends context_reaction_block::execute() to add widths on blocks.
   */
  public function execute(&$page) {
    parent::execute($page);
  }
}
