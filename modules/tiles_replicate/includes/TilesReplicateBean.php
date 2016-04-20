<?php

/**
 * @file
 * Override for Bean class.
 *
 * Exists mainly to prevent cache clearing during bean replicate, which can be a
 * huge performance hit.
 */

class TilesReplicateBean extends Bean {
  /**
   * Implements parent::save().
   *
   * Since multiple beans are replicated at the same time (e.g. during a node
   * replication that contains many tile sections), there's no need to do a block
   * rehash every time. That can be done instead at the end of the replication
   * process.
   */
  public function save() {
    if (!empty($this->replicated)) {
      $this->setUid()->checkDelta();

      if (empty($this->created)) {
        $this->created = REQUEST_TIME;
      }

      $this->changed = REQUEST_TIME;

      $this->plugin->submit($this);

      bean_reset();

      return entity_get_controller($this->entityType)->save($this);
    }
    else {
      return parent::save();
    }
  }
}
