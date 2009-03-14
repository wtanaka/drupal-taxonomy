<?php

/**
 * Helper function for autocompletion
 */
function real_taxonomy_autocomplete($vid, $string = '') {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  // This regexp allows the following types of user input:
  // this, "somecmpany, llc", "and ""this"" w,o.rks", foo bar
  $regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
  preg_match_all($regexp, $string, $matches);
  $array = $matches[1];

  // Fetch last tag
  $last_string = trim(array_pop($array));
  if ($last_string != '') {
    $result = db_query_range(db_rewrite_sql("SELECT t.tid, t.name FROM {term_data} t WHERE t.vid = %d AND LOWER(t.name) LIKE LOWER('%%%s%%')", 't', 'tid'), $vid, $last_string, 0, 10);

    $prefix = count($array) ? implode(', ', $array) .', ' : '';

    $matches = array();
    while ($tag = db_fetch_object($result)) {
      $n = $tag->name;
      // Commas and quotes in terms are special cases, so encode 'em.
      if (strpos($tag->name, ',') !== FALSE || strpos($tag->name, '"') !== FALSE) {
        $n = '"'. str_replace('"', '""', $tag->name) .'"';
      }
      $matches[$prefix . $n] = check_plain($tag->name);
    }
    print drupal_to_js($matches);
    exit();
  }
}
