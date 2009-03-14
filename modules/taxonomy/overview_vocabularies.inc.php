<?php
function real_taxonomy_overview_vocabularies() {
  $vocabularies = taxonomy_get_vocabularies();
  $rows = array();
  foreach ($vocabularies as $vocabulary) {
    $types = array();
    foreach ($vocabulary->nodes as $type) {
      $node_type = node_get_types('name', $type);
      $types[] = $node_type ? check_plain($node_type) : check_plain($type);
    }
    $rows[] = array('name' => check_plain($vocabulary->name),
      'type' => implode(', ', $types),
      'edit' => l(t('edit vocabulary'), "admin/content/taxonomy/edit/vocabulary/$vocabulary->vid"),
      'list' => l(t('list terms'), "admin/content/taxonomy/$vocabulary->vid"),
      'add' => l(t('add terms'), "admin/content/taxonomy/$vocabulary->vid/add/term")
    );
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No categories available.'), 'colspan' => '5'));
  }
  $header = array(t('Name'), t('Type'), array('data' => t('Operations'), 'colspan' => '3'));

  return theme('table', $header, $rows, array('id' => 'taxonomy'));
}
