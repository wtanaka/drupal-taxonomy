<?php
function taxonomy_menu_may_cache(&$items)
{
$items[] = array('path' => 'admin/content/taxonomy',
  'title' => t('Categories'),
  'description' => t('Create vocabularies and terms to categorize your content.'),
  'callback' => 'taxonomy_overview_vocabularies',
  'access' => user_access('administer taxonomy'));

$items[] = array('path' => 'admin/content/taxonomy/list',
  'title' => t('List'),
  'type' => MENU_DEFAULT_LOCAL_TASK,
  'weight' => -10);

$items[] = array('path' => 'admin/content/taxonomy/add/vocabulary',
  'title' => t('Add vocabulary'),
  'callback' => 'drupal_get_form',
  'callback arguments' => array('taxonomy_form_vocabulary'),
  'access' => user_access('administer taxonomy'),
  'type' => MENU_LOCAL_TASK);

$items[] = array('path' => 'admin/content/taxonomy/edit/vocabulary',
  'title' => t('Edit vocabulary'),
  'callback' => 'taxonomy_admin_vocabulary_edit',
  'access' => user_access('administer taxonomy'),
  'type' => MENU_CALLBACK);

$items[] = array('path' => 'admin/content/taxonomy/edit/term',
  'title' => t('Edit term'),
  'callback' => 'taxonomy_admin_term_edit',
  'access' => user_access('administer taxonomy'),
  'type' => MENU_CALLBACK);

$items[] = array('path' => 'taxonomy/term',
  'title' => t('Taxonomy term'),
  'callback' => 'taxonomy_term_page',
  'access' => user_access('access content'),
  'type' => MENU_CALLBACK);

$items[] = array('path' => 'taxonomy/autocomplete',
  'title' => t('Autocomplete taxonomy'),
  'callback' => 'taxonomy_autocomplete',
  'access' => user_access('access content'),
  'type' => MENU_CALLBACK);
}
