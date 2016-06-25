<?php

/**
 * @file
 * Admin callbacks for tiles access module.
 */

/**
 * Tiles access form.
 */
function tiles_access_form($form, $form_state, $module, $delta) {
  drupal_set_title('Tile access');

  $form['module'] = array(
    '#type' => 'value',
    '#value' => $module,
  );
  $form['delta'] = array(
    '#type' => 'value',
    '#value' => $delta,
  );

  $form['role_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Role based access control settings'),
    '#description' => t('Select the roles that have access to view this tile. If no roles are selected, then the tile will be visible to all users'),
  );

  $role_options = user_roles(TRUE);
  $form['role_settings']['roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('View tile'),
    '#options' => $role_options,
    '#default_value' => tiles_access_get_block_settings($module, $delta),
  );

  $form['actions'] = array(
    '#type' => 'actions',
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => array('tiles_block_access_submit'),
    ),
  );

  return $form;
}

/**
 * Submit handler for block access form.
 */
function tiles_block_access_submit($form, &$form_state) {
  $values = $form_state['values'];

  tiles_access_save_block_settings($values['module'], $values['delta'], $values['roles']);

  drupal_set_message('Tile access updated');
  cache_clear_all();
}
