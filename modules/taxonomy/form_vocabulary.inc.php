<?php
function real_taxonomy_form_vocabulary($edit = array()) {
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Vocabulary name'),
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#description' => t('The name for this vocabulary. Example: "Topic".'),
    '#required' => TRUE,
  );
  $form['description'] = array('#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('Description of the vocabulary; can be used by modules.'),
  );
  $form['help'] = array('#type' => 'textfield',
    '#title' => t('Help text'),
    '#maxlength' => 255,
    '#default_value' => $edit['help'],
    '#description' => t('Instructions to present to the user when choosing a term.'),
  );
  $form['nodes'] = array('#type' => 'checkboxes',
    '#title' => t('Types'),
    '#default_value' => $edit['nodes'],
    '#options' => array_map('check_plain', node_get_types('names')),
    '#description' => t('A list of node types you want to associate with this vocabulary.'),
    '#required' => TRUE,
  );
  $form['hierarchy'] = array('#type' => 'radios',
    '#title' => t('Hierarchy'),
    '#default_value' => $edit['hierarchy'],
    '#options' => array(t('Disabled'), t('Single'), t('Multiple')),
    '#description' => t('Allows <a href="@help-url">a tree-like hierarchy</a> between terms of this vocabulary.', array('@help-url' => url('admin/help/taxonomy', NULL, NULL, 'hierarchy'))),
  );
  $form['relations'] = array('#type' => 'checkbox',
    '#title' => t('Related terms'),
    '#default_value' => $edit['relations'],
    '#description' => t('Allows <a href="@help-url">related terms</a> in this vocabulary.', array('@help-url' => url('admin/help/taxonomy', NULL, NULL, 'related-terms'))),
  );
  $form['tags'] = array('#type' => 'checkbox',
    '#title' => t('Free tagging'),
    '#default_value' => $edit['tags'],
    '#description' => t('Content is categorized by typing terms instead of choosing from a list.'),
  );
  $form['multiple'] = array('#type' => 'checkbox',
    '#title' => t('Multiple select'),
    '#default_value' => $edit['multiple'],
    '#description' => t('Allows nodes to have more than one term from this vocabulary (always true for free tagging).'),
  );
  $form['required'] = array('#type' => 'checkbox',
    '#title' => t('Required'),
    '#default_value' => $edit['required'],
    '#description' => t('If enabled, every node <strong>must</strong> have at least one term in this vocabulary.'),
  );
  $form['weight'] = array('#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('In listings, the heavier vocabularies will sink and the lighter vocabularies will be positioned nearer the top.'),
  );

  $form['submit'] = array('#type' => 'submit', '#value' => t('Submit'));
  if ($edit['vid']) {
    $form['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['vid'] = array('#type' => 'value', '#value' => $edit['vid']);
    $form['module'] = array('#type' => 'value', '#value' => $edit['module']);
  }
  return $form;
}

/**
 * Accept the form submission for a vocabulary and save the results.
 */
function taxonomy_form_vocabulary_submit($form_id, $form_values) {
  // Fix up the nodes array to remove unchecked nodes.
  $form_values['nodes'] = array_filter($form_values['nodes']);
  switch (taxonomy_save_vocabulary($form_values)) {
    case SAVED_NEW:
      drupal_set_message(t('Created new vocabulary %name.', array('%name' => $form_values['name'])));
      watchdog('taxonomy', t('Created new vocabulary %name.', array('%name' => $form_values['name'])), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/vocabulary/'. $form_values['vid']));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('Updated vocabulary %name.', array('%name' => $form_values['name'])));
      watchdog('taxonomy', t('Updated vocabulary %name.', array('%name' => $form_values['name'])), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/vocabulary/'. $form_values['vid']));
      break;
  }
  return 'admin/content/taxonomy';
}

function real_taxonomy_save_vocabulary(&$edit) {
  $edit['nodes'] = empty($edit['nodes']) ? array() : $edit['nodes'];

  if ($edit['vid'] && $edit['name']) {
    db_query("UPDATE {vocabulary} SET name = '%s', description = '%s', help = '%s', multiple = %d, required = %d, hierarchy = %d, relations = %d, tags = %d, weight = %d, module = '%s' WHERE vid = %d", $edit['name'], $edit['description'], $edit['help'], $edit['multiple'], $edit['required'], $edit['hierarchy'], $edit['relations'], $edit['tags'], $edit['weight'], isset($edit['module']) ? $edit['module'] : 'taxonomy', $edit['vid']);
    db_query("DELETE FROM {vocabulary_node_types} WHERE vid = %d", $edit['vid']);
    foreach ($edit['nodes'] as $type => $selected) {
      db_query("INSERT INTO {vocabulary_node_types} (vid, type) VALUES (%d, '%s')", $edit['vid'], $type);
    }
    module_invoke_all('taxonomy', 'update', 'vocabulary', $edit);
    $status = SAVED_UPDATED;
  }
  else if ($edit['vid']) {
    $status = taxonomy_del_vocabulary($edit['vid']);
  }
  else {
    $edit['vid'] = db_next_id('{vocabulary}_vid');
    db_query("INSERT INTO {vocabulary} (vid, name, description, help, multiple, required, hierarchy, relations, tags, weight, module) VALUES (%d, '%s', '%s', '%s', %d, %d, %d, %d, %d, %d, '%s')", $edit['vid'], $edit['name'], $edit['description'], $edit['help'], $edit['multiple'], $edit['required'], $edit['hierarchy'], $edit['relations'], $edit['tags'], $edit['weight'], isset($edit['module']) ? $edit['module'] : 'taxonomy');
    foreach ($edit['nodes'] as $type => $selected) {
      db_query("INSERT INTO {vocabulary_node_types} (vid, type) VALUES (%d, '%s')", $edit['vid'], $type);
    }
    module_invoke_all('taxonomy', 'insert', 'vocabulary', $edit);
    $status = SAVED_NEW;
  }

  cache_clear_all();

  return $status;
}
