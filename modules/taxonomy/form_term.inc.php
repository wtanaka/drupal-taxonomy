<?php

function real_taxonomy_form_term($vocabulary_id, $edit = array()) {
  $vocabulary = taxonomy_get_vocabulary($vocabulary_id);
  drupal_set_title(check_plain($vocabulary->name));

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Term name'),
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#description' => t('The name of this term.'),
    '#required' => TRUE);

  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('A description of the term.'));

  if ($vocabulary->hierarchy) {
    $parent = array_keys(taxonomy_get_parents($edit['tid']));
    $children = taxonomy_get_tree($vocabulary_id, $edit['tid']);

    // A term can't be the child of itself, nor of its children.
    foreach ($children as $child) {
      $exclude[] = $child->tid;
    }
    $exclude[] = $edit['tid'];

    if ($vocabulary->hierarchy == 1) {
      $form['parent'] = _taxonomy_term_select(t('Parent'), 'parent', $parent, $vocabulary_id, l(t('Parent term'), 'admin/help/taxonomy', NULL, NULL, 'parent') .'.', 0, '<'. t('root') .'>', $exclude);
    }
    elseif ($vocabulary->hierarchy == 2) {
      $form['parent'] = _taxonomy_term_select(t('Parents'), 'parent', $parent, $vocabulary_id, l(t('Parent terms'), 'admin/help/taxonomy', NULL, NULL, 'parent') .'.', 1, '<'. t('root') .'>', $exclude);
    }
  }

  if ($vocabulary->relations) {
    $form['relations'] = _taxonomy_term_select(t('Related terms'), 'relations', array_keys(taxonomy_get_related($edit['tid'])), $vocabulary_id, NULL, 1, '<'. t('none') .'>', array($edit['tid']));
  }

  $form['synonyms'] = array(
    '#type' => 'textarea',
    '#title' => t('Synonyms'),
    '#default_value' => implode("\n", taxonomy_get_synonyms($edit['tid'])),
    '#description' => t('<a href="@help-url">Synonyms</a> of this term, one synonym per line.', array('@help-url' => url('admin/help/taxonomy', NULL, NULL, 'synonyms'))));
  $form['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('In listings, the heavier terms will sink and the lighter terms will be positioned nearer the top.'));
  $form['vid'] = array(
    '#type' => 'value',
    '#value' => $vocabulary->vid);
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'));

  if ($edit['tid']) {
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'));
    $form['tid'] = array(
      '#type' => 'value',
      '#value' => $edit['tid']);
  }
  else {
    $form['destination'] = array('#type' => 'hidden', '#value' => $_GET['q']);
  }

  return $form;
}

/**
 * Accept the form submission for a taxonomy term and save the result.
 */
function taxonomy_form_term_submit($form_id, $form_values) {
  switch (taxonomy_save_term($form_values)) {
    case SAVED_NEW:
      drupal_set_message(t('Created new term %term.', array('%term' => $form_values['name'])));
      watchdog('taxonomy', t('Created new term %term.', array('%term' => $form_values['name'])), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/term/'. $form_values['tid']));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('Updated term %term.', array('%term' => $form_values['name'])));
      watchdog('taxonomy', t('Updated term %term.', array('%term' => $form_values['name'])), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/term/'. $form_values['tid']));
      break;
  }
  return 'admin/content/taxonomy';
}

/**
 * Helper function for taxonomy_form_term_submit().
 *
 * @param $form_values
 * @return
 *   Status constant indicating if term was inserted or updated.
 */
function real_taxonomy_save_term(&$form_values) {
  if ($form_values['tid'] && $form_values['name']) {
    db_query("UPDATE {term_data} SET name = '%s', description = '%s', weight = %d WHERE tid = %d", $form_values['name'], $form_values['description'], $form_values['weight'], $form_values['tid']);
    $hook = 'update';
    $status = SAVED_UPDATED;
  }
  else if ($form_values['tid']) {
    return taxonomy_del_term($form_values['tid']);
  }
  else {
    $form_values['tid'] = db_next_id('{term_data}_tid');
    db_query("INSERT INTO {term_data} (tid, name, description, vid, weight) VALUES (%d, '%s', '%s', %d, %d)", $form_values['tid'], $form_values['name'], $form_values['description'], $form_values['vid'], $form_values['weight']);
    $hook = 'insert';
    $status = SAVED_NEW;
  }

  db_query('DELETE FROM {term_relation} WHERE tid1 = %d OR tid2 = %d', $form_values['tid'], $form_values['tid']);
  if ($form_values['relations']) {
    foreach ($form_values['relations'] as $related_id) {
      if ($related_id != 0) {
        db_query('INSERT INTO {term_relation} (tid1, tid2) VALUES (%d, %d)', $form_values['tid'], $related_id);
      }
    }
  }

  db_query('DELETE FROM {term_hierarchy} WHERE tid = %d', $form_values['tid']);
  if (!isset($form_values['parent']) || empty($form_values['parent'])) {
    $form_values['parent'] = array(0);
  }
  if (is_array($form_values['parent'])) {
    foreach ($form_values['parent'] as $parent) {
      if (is_array($parent)) {
        foreach ($parent as $tid) {
          db_query('INSERT INTO {term_hierarchy} (tid, parent) VALUES (%d, %d)', $form_values['tid'], $tid);
        }
      }
      else {
        db_query('INSERT INTO {term_hierarchy} (tid, parent) VALUES (%d, %d)', $form_values['tid'], $parent);
      }
    }
  }
  else {
    db_query('INSERT INTO {term_hierarchy} (tid, parent) VALUES (%d, %d)', $form_values['tid'], $form_values['parent']);
  }

  db_query('DELETE FROM {term_synonym} WHERE tid = %d', $form_values['tid']);
  if ($form_values['synonyms']) {
    foreach (explode ("\n", str_replace("\r", '', $form_values['synonyms'])) as $synonym) {
      if ($synonym) {
        db_query("INSERT INTO {term_synonym} (tid, name) VALUES (%d, '%s')", $form_values['tid'], chop($synonym));
      }
    }
  }

  if (isset($hook)) {
    module_invoke_all('taxonomy', $hook, 'term', $form_values);
  }

  cache_clear_all();

  return $status;
}
