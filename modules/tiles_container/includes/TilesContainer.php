<?php

/**
 * @file
 * Bean plugin object for bean container.
 */

class TilesContainer extends BeanPlugin {
  /**
   * @see: parent::values().
   */
  public function values() {
    $values = parent::values();

    $values['children'] = array();
    $values['display_type'] = 'simple';

    return $values;
  }

  /**
   * @see: parent::form().
   */
  public function form($bean, $form, &$form_state) {
    $form = parent::form($bean, $form, $form_state);

    $form['children'] = array(
      '#type' => 'value',
      '#value' => $bean->children,
    );

    // @todo: make this pluggable.
    $form['display_type'] = array(
      '#type' => 'select',
      '#title' => t('Display type'),
      '#options' => array(
        'simple' => t('Invisible Container'),
        'tab' => t('Tabbed Panel'),
      ),
      '#default_value' => $bean->display_type,
    );

    return $form;
  }

  /**
   * Return the block content.
   *
   * @param $bean
   *   The bean object being viewed.
   * @param $content
   *   The default content array created by Entity API.  This will include any
   *   fields attached to the entity.
   * @param $view_mode
   *   The view mode passed into $entity->view().
   * @return
   *   Return a renderable content array.
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = NULL) {
    $children = array();

    if (!empty($bean->children)) {
      $i = 1;
      foreach ($bean->children as $child) {
        $child_bean = bean_load($child);
        if ($child_bean && bean_access('view', $child_bean)) {
          $children[] = $child_bean;
        }
      }
    }

    // @todo: implement different themes based on display_type.
    $content['bean'][$bean->bid]['children'] = array(
      '#theme' => 'tiles_container',
      '#children' => $children,
      '#display_type' => $bean->display_type,
      '#parent' => $bean,
    );

    return $content;
  }
}
