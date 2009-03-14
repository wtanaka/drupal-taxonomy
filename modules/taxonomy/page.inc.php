<?php
/**
 * Finds all nodes that match selected taxonomy conditions.
 *
 * @param $tids
 *   An array of term IDs to match.
 * @param $operator
 *   How to interpret multiple IDs in the array. Can be "or" or "and".
 * @param $depth
 *   How many levels deep to traverse the taxonomy tree. Can be a nonnegative
 *   integer or "all".
 * @param $pager
 *   Whether the nodes are to be used with a pager (the case on most Drupal
 *   pages) or not (in an XML feed, for example).
 * @param $order
 *   The order clause for the query that retrieve the nodes.
 * @return
 *   A resource identifier pointing to the query results.
 */
function real_taxonomy_select_nodes($tids = array(), $operator = 'or', $depth = 0, $pager = TRUE, $order = 'n.sticky DESC, n.created DESC') {
  if (count($tids) > 0) {
    // For each term ID, generate an array of descendant term IDs to the right depth.
    $descendant_tids = array();
    if ($depth === 'all') {
      $depth = NULL;
    }
    foreach ($tids as $index => $tid) {
      $term = taxonomy_get_term($tid);
      $tree = taxonomy_get_tree($term->vid, $tid, -1, $depth);
      $descendant_tids[] = array_merge(array($tid), array_map('_taxonomy_get_tid_from_term', $tree));
    }

    if ($operator == 'or') {
      $str_tids = implode(',', call_user_func_array('array_merge', $descendant_tids));
      $sql = 'SELECT DISTINCT(n.nid), n.sticky, n.title, n.created FROM {node} n INNER JOIN {term_node} tn ON n.nid = tn.nid WHERE tn.tid IN ('. $str_tids .') AND n.status = 1 ORDER BY '. $order;
      $sql_count = 'SELECT COUNT(DISTINCT(n.nid)) FROM {node} n INNER JOIN {term_node} tn ON n.nid = tn.nid WHERE tn.tid IN ('. $str_tids .') AND n.status = 1';
    }
    else {
      $joins = '';
      $wheres = '';
      foreach ($descendant_tids as $index => $tids) {
        $joins .= ' INNER JOIN {term_node} tn'. $index .' ON n.nid = tn'. $index .'.nid';
        $wheres .= ' AND tn'. $index .'.tid IN ('. implode(',', $tids) .')';
      }
      $sql = 'SELECT DISTINCT(n.nid), n.sticky, n.title, n.created FROM {node} n '. $joins .' WHERE n.status = 1 '. $wheres .' ORDER BY '. $order;
      $sql_count = 'SELECT COUNT(DISTINCT(n.nid)) FROM {node} n '. $joins .' WHERE n.status = 1 '. $wheres;
    }
    $sql = db_rewrite_sql($sql);
    $sql_count = db_rewrite_sql($sql_count);
    if ($pager) {
      $result = pager_query($sql, variable_get('default_nodes_main', 10), 0, $sql_count);
    }
    else {
      $result = db_query_range($sql, 0, variable_get('feed_default_items', 10));
    }
  }

  return $result;
}

/**
 * Menu callback; displays all nodes associated with a term.
 */
function real_taxonomy_term_page($str_tids = '', $depth = 0, $op = 'page') {
  $terms = taxonomy_terms_parse_string($str_tids);
  if ($terms['operator'] != 'and' && $terms['operator'] != 'or') {
    drupal_not_found();
  }

  if ($terms['tids']) {
    $placeholders = implode(',', array_fill(0, count($terms['tids']), '%d'));
    $result = db_query(db_rewrite_sql('SELECT t.tid, t.name FROM {term_data} t WHERE t.tid IN ('. $placeholders .')', 't', 'tid'), $terms['tids']);
    $tids = array(); // we rebuild the $tids-array so it only contains terms the user has access to.
    $names = array();
    while ($term = db_fetch_object($result)) {
      $tids[] = $term->tid;
      $names[] = $term->name;
    }

    if ($names) {
      $title = check_plain(implode(', ', $names));
      drupal_set_title($title);

      switch ($op) {
        case 'page':
          // Build breadcrumb based on first hierarchy of first term:
          $current->tid = $tids[0];
          $breadcrumbs = array(array('path' => $_GET['q'], 'title' => $names[0]));
          while ($parents = taxonomy_get_parents($current->tid)) {
            $current = array_shift($parents);
            $breadcrumbs[] = array('path' => 'taxonomy/term/'. $current->tid, 'title' => $current->name);
          }
          $breadcrumbs = array_reverse($breadcrumbs);
          menu_set_location($breadcrumbs);

          $output = taxonomy_render_nodes(taxonomy_select_nodes($tids, $terms['operator'], $depth, TRUE));
          drupal_add_feed(url('taxonomy/term/'. $str_tids .'/'. $depth .'/feed'), 'RSS - '. $title);
          return $output;
          break;

        case 'feed':
          $term = taxonomy_get_term($tids[0]);
          $channel['link'] = url('taxonomy/term/'. $str_tids .'/'. $depth, NULL, NULL, TRUE);
          $channel['title'] = variable_get('site_name', 'Drupal') .' - '. $title;
          $channel['description'] = $term->description;

          $result = taxonomy_select_nodes($tids, $terms['operator'], $depth, FALSE);
          node_feed($result, $channel);
          break;
        default:
          drupal_not_found();
      }
    }
    else {
      drupal_not_found();
    }
  }
}

/**
 * Helper function for array_map purposes.
 */
function _taxonomy_get_tid_from_term($term) {
  return $term->tid;
}

