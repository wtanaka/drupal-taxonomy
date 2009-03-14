<?php

/**
 * Delete a vocabulary.
 *
 * @param $vid
 *   A vocabulary ID.
 * @return
 *   Constant indicating items were deleted.
 */
function real_taxonomy_del_vocabulary($vid) {
  $vocabulary = (array) taxonomy_get_vocabulary($vid);

  db_query('DELETE FROM {vocabulary} WHERE vid = %d', $vid);
  db_query('DELETE FROM {vocabulary_node_types} WHERE vid = %d', $vid);
  $result = db_query('SELECT tid FROM {term_data} WHERE vid = %d', $vid);
  while ($term = db_fetch_object($result)) {
    taxonomy_del_term($term->tid);
  }

  module_invoke_all('taxonomy', 'delete', 'vocabulary', $vocabulary);

  cache_clear_all();

  return SAVED_DELETED;
}

/**
 * Delete a term.
 *
 * @param $tid
 *   The term ID.
 * @return
 *   Status constant indicating deletion.
 */
function real_taxonomy_del_term($tid) {
  $tids = array($tid);
  while ($tids) {
    $children_tids = $orphans = array();
    foreach ($tids as $tid) {
      // See if any of the term's children are about to be become orphans:
      if ($children = taxonomy_get_children($tid)) {
        foreach ($children as $child) {
          // If the term has multiple parents, we don't delete it.
          $parents = taxonomy_get_parents($child->tid);
          if (count($parents) == 1) {
            $orphans[] = $child->tid;
          }
        }
      }

      $term = (array) taxonomy_get_term($tid);

      db_query('DELETE FROM {term_data} WHERE tid = %d', $tid);
      db_query('DELETE FROM {term_hierarchy} WHERE tid = %d', $tid);
      db_query('DELETE FROM {term_relation} WHERE tid1 = %d OR tid2 = %d', $tid, $tid);
      db_query('DELETE FROM {term_synonym} WHERE tid = %d', $tid);
      db_query('DELETE FROM {term_node} WHERE tid = %d', $tid);

      module_invoke_all('taxonomy', 'delete', 'term', $term);
    }

    $tids = $orphans;
  }

  cache_clear_all();

  return SAVED_DELETED;
}
