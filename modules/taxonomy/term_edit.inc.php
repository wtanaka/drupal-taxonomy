<?php

/**
 * Page to edit a vocabulary term.
 */
function real_taxonomy_admin_term_edit($tid) {
  if ($_POST['op'] == t('Delete') || $_POST['confirm']) {
    return drupal_get_form('taxonomy_term_confirm_delete', $tid);
  }
  if ($term = (array)taxonomy_get_term($tid)) {
    return drupal_get_form('taxonomy_form_term', $term['vid'], $term);
  }
  return drupal_not_found();
}

function taxonomy_term_confirm_delete($tid) {
  $term = taxonomy_get_term($tid);

  $form['type'] = array('#type' => 'value', '#value' => 'term');
  $form['name'] = array('#type' => 'value', '#value' => $term->name);
  $form['tid'] = array('#type' => 'value', '#value' => $tid);
  return confirm_form($form,
                  t('Are you sure you want to delete the term %title?',
                  array('%title' => $term->name)),
                  'admin/content/taxonomy',
                  t('Deleting a term will delete all its children if there are any. This action cannot be undone.'),
                  t('Delete'),
                  t('Cancel'));
}

function taxonomy_term_confirm_delete_submit($form_id, $form_values) {
  taxonomy_del_term($form_values['tid']);
  drupal_set_message(t('Deleted term %name.', array('%name' => $form_values['name'])));
  watchdog('taxonomy', t('Deleted term %name.', array('%name' => $form_values['name'])), WATCHDOG_NOTICE);
  return 'admin/content/taxonomy';
}

