<?php

/**
 * @file
 * Admin callbacks for tiles_layout module.
 */

/**
 * Generates the tiles layout editing form.
 */
function tiles_layout_form($form, &$form_state, $layout, $op = 'edit') {
  if ($op == 'clone') {
    $layout->label .= ' (cloned)';
    $layout->type .= '_clone';
  }

  $form['label'] = array(
    '#title' => t('Label'),
    '#type' => 'textfield',
    '#default_value' => isset($layout->label) ? $layout->label : '',
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save layout'),
    '#weight' => 40,
  );

  return $form;
}

/**
 * Form API submit callback for the type form.
 */
function tiles_layout_form_submit(&$form, &$form_state) {
  $layout = entity_ui_form_submit_build_entity($form, $form_state);

  // Save and go back.
  $layout->save();

  $form_state['redirect'] = 'admin/structure/tiles-layout';
}
