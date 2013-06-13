<?php

/**
 * @file
 * Admin callbacks for tiles container.
 */

/**
 * Manage container children form.
 */
function tiles_container_manage_children($form, &$form_state, $tiles_container) {
  drupal_set_title('Container children');

  if (!isset($form['#tiles_container'])) {
    $form['#tiles_container'] = $tiles_container;
  }

  if (!empty($tiles_container->children)) {
    $weight = 0;
    $form['children'] = array(
      '#tree' => TRUE,
    );
    foreach ($tiles_container->children as $child) {
      $child_bean = bean_load($child);
      if (!$child_bean) {
        continue;
      }
      $form['children'][$child]['#bean'] = $child_bean;
      $form['children'][$child]['#weight'] = $weight;
      $form['children'][$child]['weight'] = array(
        '#type' => 'textfield',
        '#title' => t('Weight for @title', array('@title' => $child_bean->title)),
        '#title_display' => 'invisible',
        '#size' => 4,
        '#default_value' => $weight++,
        '#attributes' => array('class' => array('tiles-container-weight')),
      );
    }
    $form['children']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
  }

  return $form;
}

/**
 * Submit handler for manage children form.
 */
function tiles_container_manage_children_submit($form, &$form_state) {
  $children = array();
  uasort($form_state['values']['children'], 'drupal_sort_weight');
  foreach ($form_state['values']['children'] as $bid => $values) {
    if (is_int($bid)) {
      $children[] = $bid;
    }
  }
  $form['#tiles_container']->data['children'] = $children;
  $form['#tiles_container']->save();

  drupal_set_message(t('Container children weights updated.'));
}

/**
 * Form for adding a block to a container bean.
 */
function tiles_container_add_block($form, &$form_state, $tiles_container) {
  // During initial form build, add the container entity to the form state for
  // useduring form building and processing. During a rebuild, use what is in
  // the form state.
  if (!isset($form_state['tiles_container'])) {
    $form_state['tiles_container'] = $tiles_container;
  }
  else {
    $tiles_container = $form_state['tiles_container'];
  }

  if (empty($form_state['next_step'])) {
    $form['block_type'] = array(
      '#type' => 'select',
      '#title' => t('Block Type'),
      '#options' => array('' => 'Select One') + tiles_containers_get_types(),
      '#description' => t("Choose the type of block you'd like to add"),
      '#required' => TRUE,
    );

    $form['next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
      '#submit' => array('tiles_container_add_form_next'),
    );
  }
  else {
    $bean = bean_create(array('type' => $form_state['type_values']['block_type']));
    $form_state['bean'] = $bean;
    module_load_include('inc', 'bean', 'includes/bean.pages');
    $form['bean_form'] = bean_form(array(), $form_state, $bean, $form_state['type_values']['block_type']);
    unset($form['bean_form']['actions']);

    // Hide the label.
    $form['bean_form']['label']['#access'] = FALSE;
    $form['bean_form']['label']['#default_value'] = 'ombucleanup-tempkey';

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Add'),
      '#validate' => array('tiles_container_add_form_add_validate'),
      '#submit' => array('tiles_container_add_form_add_submit'),
    );
  }

  return $form;
}

/**
 * Advance to the next step for the add a block form for tiles containers.
 */
function tiles_container_add_form_next($form, &$form_state) {
    $form_state['next_step'] = TRUE;
    $form_state['type_values'] = $form_state['values'];
    $form_state['rebuild'] = TRUE;
}

/**
 * Validate add a block form.
 */
function tiles_container_add_form_add_validate($form, &$form_state) {
  if (isset($form_state['values']['bean'])) {
    $bean = $form_state['values']['bean'];

    $bean->label = $form_state['values']['label'];
    $bean->title = $form_state['values']['title'];

    if (isset($form_state['values']['view_mode'])) {
      $bean->view_mode = $form_state['values']['view_mode'];
    }

    field_attach_form_validate('bean', $bean, $form, $form_state);
    $form_state['values']['bean'] = $bean;

    $bean->validate($form_state['values'], $form_state);
  }
}

/**
 * Submit add a block form.
 */
function tiles_container_add_form_add_submit($form, &$form_state) {
  module_load_include('inc', 'bean', 'includes/bean.pages');
  $bean = $form_state['bean'];
  entity_form_submit_build_entity('bean', $bean, $form, $form_state);

  // Transform temporary delta key into a readable key.
  if ($bean->label == 'ombucleanup-tempkey') {
    if ($bean->title) {
      $bean->label = $bean->title;
    }
    else {
      $bean->label = $bean->type;
    }
  }

  $max_length = 32;
  // Base it on the label and make sure it isn't too long for the database.
  $bean->delta = drupal_clean_css_identifier(strtolower($bean->label));
  $bean->delta = substr($bean->delta, 0, $max_length);

  // Check if delta is unique.
  if (bean_load_delta($bean->delta)) {
    $i = 0;
    $separator = '-';
    $original_delta = $bean->delta;
    do {
      $unique_suffix = $separator . $i++;
      $bean->delta = substr($original_delta, 0, $max_length - drupal_strlen($unique_suffix)) . $unique_suffix;
    } while (bean_load_delta($bean->delta));
  }

  foreach (module_implements('bean_submit') as $module) {
    $function = $module . '_bean_submit';
    $function($bean, $form, $form_state);
  }

  $bean->setValues($form_state['values']);
  field_attach_submit('bean', $bean, $form, $form_state);
  $bean->save();

  $watchdog_args = array('@type' => $bean->type, '%title' => $bean->label);
  $t_args = array('@type' => $bean->typeName(), '%title' => $bean->label);

  watchdog('bean', '@type: added %title.', $watchdog_args, WATCHDOG_NOTICE, $bean->viewURL());
  drupal_set_message(t('@type %title has been added to the container.', $t_args));

  // Assign new bean to tiles container.
  $tiles_container = $form_state['tiles_container'];
  $tiles_container->data['children'][] = $bean->bid;
  $tiles_container->save();
}

/**
 * Theme container children as a reorderable list in the container bean edit
 * form.
 */
function theme_tiles_container_manage_children($variables) {
  $form = $variables['form'];
  $output = '';

  if (isset($form['children'])) {
    $header = array(
      t('Title'),
      t('Type'),
      t('Weight'),
      array('data' => t('Operations'), 'colspan' => 2),
    );
    $rows = array();
    $destination = drupal_get_destination();
    foreach (element_children($form['children']) as $key) {
      if (isset($form['children'][$key]['#bean'])) {
        $bean = $form['children'][$key]['#bean'];
        $row = array();
        $row[] = check_plain($bean->title);
        $row[] = check_plain($bean->typeName());
        $row[] = drupal_render($form['children'][$key]['weight']);
        $row[] = l(t('edit'), $bean->editURL(), array('query' => $destination)) . ' ' . l(t('delete'), $bean->deleteURL(), array('query' => $destination));
        $rows[] = array('data' => $row, 'class' => array('draggable'));
      }
    }

    drupal_add_tabledrag('tiles-container-children', 'order', 'sibling', 'tiles-container-weight');

    $output = theme('table', array(
      'header' => $header,
      'rows' => $rows,
      'attributes' => array('id' => 'tiles-container-children'),
    ));
  }
  $output .= drupal_render_children($form);

  return $output;
}
