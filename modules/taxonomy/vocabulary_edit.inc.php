<?php

/**
 * Page to edit a vocabulary.
 */
function real_taxonomy_admin_vocabulary_edit($vid = NULL) {
  if ($_POST['op'] == t('Delete') || $_POST['confirm']) {
    return drupal_get_form('taxonomy_vocabulary_confirm_delete', $vid);
  }
  if ($vocabulary = (array)taxonomy_get_vocabulary($vid)) {
    return drupal_get_form('taxonomy_form_vocabulary', $vocabulary);
  }
  return drupal_not_found();
}

function taxonomy_vocabulary_confirm_delete($vid) {
  $vocabulary = taxonomy_get_vocabulary($vid);

  $form['type'] = array('#type' => 'value', '#value' => 'vocabulary');
  $form['vid'] = array('#type' => 'value', '#value' => $vid);
  $form['name'] = array('#type' => 'value', '#value' => $vocabulary->name);
  return confirm_form($form,
                  t('Are you sure you want to delete the vocabulary %title?',
                  array('%title' => $vocabulary->name)),
                  'admin/content/taxonomy',
                  t('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.'),
                  t('Delete'),
                  t('Cancel'));
}

function taxonomy_vocabulary_confirm_delete_submit($form_id, $form_values) {
  $status = taxonomy_del_vocabulary($form_values['vid']);
  drupal_set_message(t('Deleted vocabulary %name.', array('%name' => $form_values['name'])));
  watchdog('taxonomy', t('Deleted vocabulary %name.', array('%name' => $form_values['name'])), WATCHDOG_NOTICE);
  return 'admin/content/taxonomy';
}
