<?php

/**
 * Save term associations for a given node.
 */
function real_taxonomy_node_save($nid, $terms) {
  taxonomy_node_delete($nid);

  // Free tagging vocabularies do not send their tids in the form,
  // so we'll detect them here and process them independently.
  if (isset($terms['tags'])) {
    $typed_input = $terms['tags'];
    unset($terms['tags']);

    foreach ($typed_input as $vid => $vid_value) {
      // This regexp allows the following types of user input:
      // this, "somecmpany, llc", "and ""this"" w,o.rks", foo bar
      $regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
      preg_match_all($regexp, $vid_value, $matches);
      $typed_terms = array_unique($matches[1]);

      $inserted = array();
      foreach ($typed_terms as $typed_term) {
        // If a user has escaped a term (to demonstrate that it is a group,
        // or includes a comma or quote character), we remove the escape
        // formatting so to save the term into the database as the user intends.
        $typed_term = str_replace('""', '"', preg_replace('/^"(.*)"$/', '\1', $typed_term));
        $typed_term = trim($typed_term);
        if ($typed_term == "") { continue; }

        // See if the term exists in the chosen vocabulary
        // and return the tid; otherwise, add a new record.
        $possibilities = taxonomy_get_term_by_name($typed_term);
        $typed_term_tid = NULL; // tid match, if any.
        foreach ($possibilities as $possibility) {
          if ($possibility->vid == $vid) {
            $typed_term_tid = $possibility->tid;
          }
        }

        if (!$typed_term_tid) {
          $edit = array('vid' => $vid, 'name' => $typed_term);
          $status = taxonomy_save_term($edit);
          $typed_term_tid = $edit['tid'];
        }

        // Defend against duplicate, differently cased tags
        if (!isset($inserted[$typed_term_tid])) {
          db_query('INSERT INTO {term_node} (nid, tid) VALUES (%d, %d)', $nid, $typed_term_tid);
          $inserted[$typed_term_tid] = TRUE;
        }
      }
    }
  }

  if (is_array($terms)) {
    foreach ($terms as $term) {
      if (is_array($term)) {
        foreach ($term as $tid) {
          if ($tid) {
            db_query('INSERT INTO {term_node} (nid, tid) VALUES (%d, %d)', $nid, $tid);
          }
        }
      }
      else if (is_object($term)) {
        db_query('INSERT INTO {term_node} (nid, tid) VALUES (%d, %d)', $nid, $term->tid);
      }
      else if ($term) {
        db_query('INSERT INTO {term_node} (nid, tid) VALUES (%d, %d)', $nid, $term);
      }
    }
  }
}
