<?php

/**
 * Generate a form element for selecting terms from a vocabulary.
 */
function real_taxonomy_form($vid, $value = 0, $help = NULL, $name = 'taxonomy') {
  $vocabulary = taxonomy_get_vocabulary($vid);
  $help = ($help) ? $help : $vocabulary->help;
  $blank = 0;

  if (!$vocabulary->multiple) {
    $blank = ($vocabulary->required) ? t('- Please choose -') : t('- None selected -');
  }

  return _taxonomy_term_select(check_plain($vocabulary->name), $name, $value, $vid, $help, intval($vocabulary->multiple), $blank);
}

/**
 * Implementation of hook_form_alter().
 * Generate a form for selecting terms to associate with a node.
 */
function real_taxonomy_form_alter($form_id, &$form) {
  if (isset($form['type']) && $form['type']['#value'] .'_node_form' == $form_id) {
    $node = $form['#node'];

    if (!isset($node->taxonomy)) {
      if ($node->nid) {
        $terms = taxonomy_node_get_terms($node->nid);
      }
      else {
        $terms = array();
      }
    }
    else {
      $terms = $node->taxonomy;
    }

    $c = db_query(db_rewrite_sql("SELECT v.* FROM {vocabulary} v INNER JOIN {vocabulary_node_types} n ON v.vid = n.vid WHERE n.type = '%s' ORDER BY v.weight, v.name", 'v', 'vid'), $node->type);

    while ($vocabulary = db_fetch_object($c)) {
      if ($vocabulary->tags) {
        $typed_terms = array();
        foreach ($terms as $term) {
          // Extract terms belonging to the vocabulary in question.
          if ($term->vid == $vocabulary->vid) {

            // Commas and quotes in terms are special cases, so encode 'em.
            if (strpos($term->name, ',') !== FALSE || strpos($term->name, '"') !== FALSE) {
              $term->name = '"'.str_replace('"', '""', $term->name).'"';
            }

            $typed_terms[] = $term->name;
          }
        }
        $typed_string = implode(', ', $typed_terms) . (array_key_exists('tags', $terms) ? $terms['tags'][$vocabulary->vid] : NULL);

        if ($vocabulary->help) {
          $help = $vocabulary->help;
        }
        else {
          $help = t('A comma-separated list of terms describing this content. Example: funny, bungee jumping, "Company, Inc.".');
        }
        $form['taxonomy']['tags'][$vocabulary->vid] = array('#type' => 'textfield',
          '#title' => $vocabulary->name,
          '#description' => $help,
          '#required' => $vocabulary->required,
          '#default_value' => $typed_string,
          '#autocomplete_path' => 'taxonomy/autocomplete/'. $vocabulary->vid,
          '#weight' => $vocabulary->weight,
          '#maxlength' => 255,
        );
      }
      else {
        // Extract terms belonging to the vocabulary in question.
        $default_terms = array();
        foreach ($terms as $term) {
          if ($term->vid == $vocabulary->vid) {
            $default_terms[$term->tid] = $term;
          }
        }
        $form['taxonomy'][$vocabulary->vid] = taxonomy_form($vocabulary->vid, array_keys($default_terms), $vocabulary->help);
        $form['taxonomy'][$vocabulary->vid]['#weight'] = $vocabulary->weight;
        $form['taxonomy'][$vocabulary->vid]['#required'] = $vocabulary->required;
      }
    }
    if (is_array($form['taxonomy']) && !empty($form['taxonomy'])) {
      if (count($form['taxonomy']) > 1) { // Add fieldset only if form has more than 1 element.
        $form['taxonomy'] += array(
          '#type' => 'fieldset',
          '#title' => t('Categories'),
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
        );
      }
      $form['taxonomy']['#weight'] = -3;
      $form['taxonomy']['#tree'] = TRUE;
    }
  }
}

function _taxonomy_term_select($title, $name, $value, $vocabulary_id, $description, $multiple, $blank, $exclude = array()) {
  $tree = taxonomy_get_tree($vocabulary_id);
  $options = array();

  if ($blank) {
    $options[''] = $blank;
  }
  if ($tree) {
    foreach ($tree as $term) {
      if (!in_array($term->tid, $exclude)) {
        $choice = new stdClass();
        $choice->option = array($term->tid => str_repeat('-', $term->depth) . $term->name);
        $options[] = $choice;
      }
    }
  }

  return array('#type' => 'select',
    '#title' => $title,
    '#default_value' => $value,
    '#options' => $options,
    '#description' => $description,
    '#multiple' => $multiple,
    '#size' => $multiple ? min(9, count($options)) : 0,
    '#weight' => -15,
    '#theme' => 'taxonomy_term_select',
  );
}

