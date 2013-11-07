<?php

/**
 * @file
 * Admin callbacks for tiles.module.
 */

/**
 * Form for adding tiles to a context.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 * @param $type
 *   The type of tiles container requesting the tiles add form. Defaults to the
 *   tiles.module provided container of 'region'.
 * @param $context_name
 *   The name of the context in which to add tile to. If not present, a context
 *   will be created (or loaded if already present) based on the destination
 *   path.
 */
function tiles_add_form($form, &$form_state, $type = 'region', $context_name = '') {
  if (empty($form_state['next_step'])) {
    $form['context'] = array(
      '#type' => 'value',
      '#value' => $context_name,
    );
    $form['destination'] = array(
      '#type' => 'value',
      '#value' => $_GET['destination'],
    );

    $tile_options = tiles_get_tile_types_options();
    $form['block_type'] = array(
      '#type' => 'select',
      '#title' => t('Block Type'),
      '#options' => array('' => 'Select One') + $tile_options['bean types'] + $tile_options['blocks'],
      '#description' => t("Choose the type of block you'd like to add"),
      '#required' => TRUE,
    );

    // Type can still come in as an empty string, so default parameter won't
    // work.
    $type = $type ? $type : 'region';
    $regions = _tiles_get_regions($type, $context_name);
    $form['region'] = array(
      '#title' => t('Region'),
      '#type' => 'select',
      '#options' => $regions,
      '#description' => t('Choose a region for the block. Only regions where
      you can add blocks are listed.'),
      '#default_value' => 'content',
    );
    // Hide regions if only one option.
    if (count($regions) <= 1) {
      $form['region']['#access'] = FALSE;
    }

    $form['next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
      '#submit' => array('tiles_add_form_next'),
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
      '#validate' => array('tiles_add_form_add_validate'),
      '#submit' => array('tiles_add_form_add_submit'),
    );
  }
  return $form;
}

function tiles_add_form_next($form, &$form_state) {
  // Only go to the next step if block type is a bean type (i.e. if a bean needs
  // to be created.
  $tile_info = tiles_get_tile_types();
  if (in_array($form_state['values']['block_type'], $tile_info['bean types'])) {
    $form_state['next_step'] = TRUE;
    $form_state['type_values'] = $form_state['values'];
    $form_state['rebuild'] = TRUE;
  }
  // Save static block to selected region in the active page context.
  else {
    // Get the correct context.
    if ($form_state['values']['context']) {
      $context = tiles_get_context('context', $form_state['values']['context']);
    }
    else {
      $context = tiles_get_context('path', $form_state['values']['destination']);
    }

    // Generate proper tiles array.
    list($module, $delta) = explode('__', $form_state['values']['block_type']);
    $tiles = array(
      array(
        'module' => $module,
        'delta' => $delta,
        'region' => $form_state['values']['region'],
        'weight' => 50,
      ),
    );
    tiles_assign_tiles($context, $tiles);

    drupal_set_message(t('Block %title added', array('%title' => $form['block_type']['#options'][$form_state['values']['block_type']])));
  }
}

function tiles_add_form_add_validate($form, &$form_state) {
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

function tiles_add_form_add_submit($form, &$form_state) {
  module_load_include('inc', 'bean', 'includes/bean.pages');
  $bean = $form_state['bean'];
  entity_form_submit_build_entity('bean', $bean, $form, $form_state);

  // Default to use title as label, otherwise use bean type.
  $bean->label = $bean->title ? $bean->title : $bean->type;

  // Create a truly unique delta for new beans. Bean::add() has the
  // possibility of generating deltas that existed in beans that have been
  // previously deleted. This causes problems, especially when a bean has
  // been deleted but not removed from other systems (e.g. context), causing
  // the bean to show up in unintended places.
  $bean->delta = drupal_clean_css_identifier(strtolower($bean->label));

  // Make sure it isn't too long for the database.
  $max_length = 32;
  $identifier = '-' . db_next_id();
  $max_length -= strlen($identifier);
  $bean->delta = substr($bean->delta, 0, $max_length);
  $bean->delta .= $identifier;

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
  drupal_set_message(t('@type %title has been created.', $t_args));

  // Save bean to selected region in the active page context.
  if ($form_state['type_values']['context']) {
    $context = tiles_get_context('context', $form_state['type_values']['context']);
  }
  else {
    $context = tiles_get_context('path', $form_state['type_values']['destination']);
  }

  // Generate proper tiles array.
  $tiles = array(
    array(
      'module' => 'bean',
      'delta' => $bean->delta,
      'region' => $form_state['type_values']['region'],
      'weight' => 50,
    ),
  );
  tiles_assign_tiles($context, $tiles);
}

/**
 * Delete a tile. Renders the block_custom_block_delete form. This
 * function is a placeholder in case we want to do a bit more later.
 *
 * @return form array
 */
function tiles_delete($module, $delta) {
  switch ($module) {
    // Block is a bean, delete bean since it's only on this page.
    case 'bean':
      $bean = bean_load_delta($delta);
      module_load_include('inc', 'bean', 'includes/bean.pages');
      $form = drupal_get_form('bean_delete_confirm', $bean);
      return $form;
      break;

    // Otherwise remove block from context.
    default:
      if (isset($_GET['destination'])) {
        $context = context_load('tiles-' . _tiles_normalize_path($_GET['destination']));
        if ($context) {
          foreach($context->reactions['tiles']['blocks'] as $key => $value) {
            if ($value['module'] == $module && $value['delta'] == $delta) {
              unset($context->reactions['tiles']['blocks'][$key]);
            }
          }
          context_save($context);

          drupal_set_message(t('Block removed'));
        }
      }
      break;
  }

  drupal_goto($_GET['destination']);
}
