<?php

/**
 * Create a hierarchical representation of a vocabulary.
 *
 * @param $vid
 *   Which vocabulary to generate the tree for.
 *
 * @param $parent
 *   The term ID under which to generate the tree. If 0, generate the tree
 *   for the entire vocabulary.
 *
 * @param $depth
 *   Internal use only.
 *
 * @param $max_depth
 *   The number of levels of the tree to return. Leave NULL to return all levels.
 *
 * @return
 *   An array of all term objects in the tree. Each term object is extended
 *   to have "depth" and "parents" attributes in addition to its normal ones.
 *   Results are statically cached.
 */
function real_taxonomy_get_tree($vid, $parent = 0, $depth = -1, $max_depth = NULL) {
  static $children, $parents, $terms;

  $depth++;

  // We cache trees, so it's not CPU-intensive to call get_tree() on a term
  // and its children, too.
  if (!isset($children[$vid])) {
    $children[$vid] = array();

    $result = db_query(db_rewrite_sql('SELECT t.tid, t.*, parent FROM {term_data} t INNER JOIN  {term_hierarchy} h ON t.tid = h.tid WHERE t.vid = %d ORDER BY weight, name', 't', 'tid'), $vid);
    while ($term = db_fetch_object($result)) {
      $children[$vid][$term->parent][] = $term->tid;
      $parents[$vid][$term->tid][] = $term->parent;
      $terms[$vid][$term->tid] = $term;
    }
  }

  $max_depth = (is_null($max_depth)) ? count($children[$vid]) : $max_depth;
  if ($children[$vid][$parent]) {
    foreach ($children[$vid][$parent] as $child) {
      if ($max_depth > $depth) {
        $term = drupal_clone($terms[$vid][$child]);
        $term->depth = $depth;
        // The "parent" attribute is not useful, as it would show one parent only.
        unset($term->parent);
        $term->parents = $parents[$vid][$child];
        $tree[] = $term;

        if ($children[$vid][$child]) {
          $tree = array_merge($tree, taxonomy_get_tree($vid, $child, $depth, $max_depth));
        }
      }
    }
  }

  return $tree ? $tree : array();
}
