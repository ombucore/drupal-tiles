<?php

/**
 * @file
 * Tiles layout entity class.
 */

class TilesLayoutEntity extends Entity {
  public $id;
  public $label;
  public $context;

  /**
   * Implements parent::defaultUri().
   */
  protected function defaultUri() {
    return array('path' => 'admin/structure/tiles-layout/view/' . $this->identifier());
  }

  /**
   * Implements parent::save().
   *
   * Create a new context when a new layout is created.
   */
  public function save() {
    $is_new = empty($this->{$this->idKey});

    parent::save();

    if ($is_new) {
      $this->createContext();
    }
  }

  /**
   * Implements parent::delete().
   *
   * Delete associated context when layout is deleted.
   */
  public function delete() {
    parent::delete();

  }

  /**
   * Creates a new context based on id.
   *
   * @return bool
   *   TRUE if context was successfully created.
   */
  protected function createContext() {
    // A context can't be created if id is empty.
    if (empty($this->{$this->idKey})) {
      return FALSE;
    }

    // Create a new context.
    $context = new stdClass();

    // Set context meta data.
    $context->name = $this->getContextName();
    $context->tag = 'tiles-layout';
    $context->description = '';

    // Set the path as the only condition.
    $path = $this->defaultUri();
    $context->conditions = array(
      'path' => array(
        'values' => array(
          $path['path'] => $path['path'],
        ),
      ),
    );

    return context_save($context);
  }

  /**
   * Loads context associated with this layout.
   *
   * @return object
   *   Fully loaded context object.
   */
  protected function loadContext() {
    return context_load($this->getContextName());
  }

  /**
   * Deletes associated context.
   *
   * @return bool
   *   TRUE if context was successfully deleted.
   */
  protected function deleteContext() {
    // A context can't be created if id is empty.
    if (empty($this->{$this->idKey})) {
      return FALSE;
    }

    return context_save($this->getContextName());
  }

  /**
   * Returns normalized context name for this layout.
   *
   * @return string
   *   String representing context name for this layout.
   */
  protected function getContextName() {
    return 'tiles-layout-' . $this->{$this->idKey};
  }
}
