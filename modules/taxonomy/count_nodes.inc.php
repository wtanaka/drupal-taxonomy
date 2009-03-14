<?php
/**
 * Count the number of published nodes classified by a term.
 *
 * @param $tid
 *   The term's ID
 *
 * @param $type
 *   The $node->type. If given, taxonomy_term_count_nodes only counts
 *   nodes of $type that are classified with the term $tid.
 *
 * @return int
 *   An integer representing a number of nodes.
 *   Results are statically cached.
 */
function real_taxonomy_term_count_nodes($tid, $type = 0) {
  static $count;

  if (!isset($count[$type])) {
    // $type == 0 always evaluates TRUE if $type is a string
    if (is_numeric($type)) {
      $result = db_query(db_rewrite_sql('SELECT t.tid, COUNT(n.nid) AS c FROM {term_node} t INNER JOIN {node} n ON t.nid = n.nid WHERE n.status = 1 GROUP BY t.tid'));
    }
    else {
      $result = db_query(db_rewrite_sql("SELECT t.tid, COUNT(n.nid) AS c FROM {term_node} t INNER JOIN {node} n ON t.nid = n.nid WHERE n.status = 1 AND n.type = '%s' GROUP BY t.tid"), $type);
    }
    while ($term = db_fetch_object($result)) {
      $count[$type][$term->tid] = $term->c;
    }
  }

  foreach (_taxonomy_term_children($tid) as $c) {
    $children_count += taxonomy_term_count_nodes($c, $type);
  }
  return $count[$type][$tid] + $children_count;
}

/**
 * Helper for taxonomy_term_count_nodes(). Used to find out
 * which terms are children of a parent term.
 *
 * @param $tid
 *   The parent term's ID
 *
 * @return array
 *   An array of term IDs representing the children of $tid.
 *   Results are statically cached.
 *
 */
function _taxonomy_term_children($tid) {
  static $children;

  if (!isset($children)) {
    $result = db_query('SELECT tid, parent FROM {term_hierarchy}');
    while ($term = db_fetch_object($result)) {
      $children[$term->parent][] = $term->tid;
    }
  }
  return $children[$tid] ? $children[$tid] : array();
}
