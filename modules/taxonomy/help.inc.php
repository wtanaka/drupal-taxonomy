<?php
/**
 * Implementation of hook_help().
 */
function real_taxonomy_help($section) {
  switch ($section) {
    case 'admin/help#taxonomy':
      $output = '<p>'. t('The taxonomy module is one of the most popular features because users often want to create categories to organize content by type. A simple example would be organizing a list of music reviews by musical genre.') .'</p>';
      $output .= '<p>'. t('Taxonomy is the study of classification. The taxonomy module allows you to define vocabularies (sets of categories) which are used to classify content. The module supports hierarchical classification and association between terms, allowing for truly flexible information retrieval and classification. The taxonomy module allows multiple lists of categories for classification (controlled vocabularies) and offers the possibility of creating thesauri (controlled vocabularies that indicate the relationship of terms) and taxonomies (controlled vocabularies where relationships are indicated hierarchically). To view and manage the terms of each vocabulary, click on the associated <em>list terms</em> link. To delete a vocabulary and all its terms, choose <em>edit vocabulary.</em>') .'</p>';
      $output .= '<p>'. t('A controlled vocabulary is a set of terms to use for describing content (known as descriptors in indexing lingo). Drupal allows you to describe each piece of content (blog, story, etc.) using one or many of these terms. For simple implementations, you might create a set of categories without subcategories, similar to Slashdot\'s sections. For more complex implementations, you might create a hierarchical list of categories.') .'</p>';
      $output .= '<p>'. t('For more information please read the configuration and customization handbook <a href="@taxonomy">Taxonomy page</a>.', array('@taxonomy' => 'http://drupal.org/handbook/modules/taxonomy/')) .'</p>';
      return $output;
    case 'admin/content/taxonomy':
      return '<p>'. t('The taxonomy module allows you to classify content into categories and subcategories; it allows multiple lists of categories for classification (controlled vocabularies) and offers the possibility of creating thesauri (controlled vocabularies that indicate the relationship of terms), taxonomies (controlled vocabularies where relationships are indicated hierarchically), and free vocabularies where terms, or tags, are defined during content creation. To view and manage the terms of each vocabulary, click on the associated <em>list terms</em> link. To delete a vocabulary and all its terms, choose "edit vocabulary".') .'</p>';
    case 'admin/content/taxonomy/add/vocabulary':
      return '<p>'. t("When you create a controlled vocabulary you are creating a set of terms to use for describing content (known as descriptors in indexing lingo). Drupal allows you to describe each piece of content (blog, story, etc.) using one or many of these terms. For simple implementations, you might create a set of categories without subcategories. For more complex implementations, you might create a hierarchical list of categories.") .'</p>';
  }
}
