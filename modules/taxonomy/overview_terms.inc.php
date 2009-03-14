<?php

/**
 * Display a tree of all the terms in a vocabulary, with options to edit
 * each one.
 */
function real_taxonomy_overview_terms($vid) {
  $destination = drupal_get_destination();

  $header = array(t('Name'), t('Operations'));
  $vocabulary = taxonomy_get_vocabulary($vid);
  if (!$vocabulary) {
    return drupal_not_found();
  }

  drupal_set_title(check_plain($vocabulary->name));
  $start_from      = $_GET['page'] ? $_GET['page'] : 0;
  $total_entries   = 0;  // total count for pager
  $page_increment  = 25; // number of tids per page
  $displayed_count = 0;  // number of tids shown

  if ($vocabulary->tags) {
    // We are not calling taxonomy_get_tree because that might fail with a big
    // number of tags in the freetagging vocabulary.
    $results = pager_query(db_rewrite_sql('SELECT t.*, h.parent FROM {term_data} t INNER JOIN  {term_hierarchy} h ON t.tid = h.tid WHERE t.vid = %d ORDER BY weight, name', 't', 'tid'), $page_increment, 0, NULL, $vid);
    while ($term = db_fetch_object($results)) {
      $rows[] = array(
        l($term->name, "taxonomy/term/$term->tid"),
        l(t('edit'), "admin/content/taxonomy/edit/term/$term->tid", array(), $destination),
      );
    }
  }
  else {
    $tree = taxonomy_get_tree($vocabulary->vid);
    foreach ($tree as $term) {
      $total_entries++; // we're counting all-totals, not displayed
      if (($start_from && ($start_from * $page_increment) >= $total_entries) || ($displayed_count == $page_increment)) {
        continue;
      }
      $rows[] = array(str_repeat('--', $term->depth) .' '. l($term->name, "taxonomy/term/$term->tid"), l(t('edit'), "admin/content/taxonomy/edit/term/$term->tid", array(), $destination));
      $displayed_count++; // we're counting tids displayed
    }

    if (!$rows) {
      $rows[] = array(array('data' => t('No terms available.'), 'colspan' => '2'));
    }

    $GLOBALS['pager_page_array'][] = $start_from;  // FIXME
    $GLOBALS['pager_total'][] = intval($total_entries / $page_increment) + 1; // FIXME
  }

  $output .= theme('table', $header, $rows, array('id' => 'taxonomy'));
  if ($vocabulary->tags || $total_entries >= $page_increment) {
    $output .= theme('pager', NULL, $page_increment);
  }

  return $output;
}
