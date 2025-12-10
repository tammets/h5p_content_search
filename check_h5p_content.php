<?php

/**
 * Quick script to check H5P content in the database.
 * Run from Drupal root: php web/modules/custom/h5p_content_search/check_h5p_content.php
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';
$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest(Request::createFromGlobals());

// Get all H5P content from database
$database = \Drupal::database();
$query = $database->select('h5p_content', 'hc')
  ->fields('hc', ['id', 'title', 'parameters'])
  ->execute();

$search_term = $argv[1] ?? 'saba'; // Get search term from command line or use 'saba' as default

echo "Searching for: '$search_term'\n";
echo str_repeat('=', 80) . "\n\n";

$found_count = 0;

while ($row = $query->fetchAssoc()) {
  $parameters = $row['parameters'];
  
  // Check if search term appears in the JSON
  if (stripos($parameters, $search_term) !== false) {
    $found_count++;
    echo "✓ FOUND in H5P ID: {$row['id']}\n";
    echo "  Title: {$row['title']}\n";
    
    // Show a snippet of where it appears
    $decoded = json_decode($parameters, true);
    $flat_text = extractStrings($decoded);
    
    // Find the search term in context
    $pos = stripos($flat_text, $search_term);
    if ($pos !== false) {
      $start = max(0, $pos - 50);
      $length = 100;
      $snippet = substr($flat_text, $start, $length);
      echo "  Context: ..." . trim($snippet) . "...\n";
    }
    echo "\n";
  }
}

if ($found_count === 0) {
  echo "✗ Search term '$search_term' NOT FOUND in any H5P content.\n";
  echo "\nTry searching for a different term, or check your H5P content.\n";
} else {
  echo "Found '$search_term' in $found_count H5P content item(s).\n";
}

/**
 * Helper function to extract strings from H5P JSON (same as the processor).
 */
function extractStrings($data) {
  $text = '';
  
  if (is_array($data)) {
    foreach ($data as $value) {
      $text .= ' ' . extractStrings($value);
    }
  }
  elseif (is_string($data) && strlen($data) > 2) {
    $clean_string = strip_tags(html_entity_decode($data, ENT_QUOTES));
    if (strlen(trim($clean_string)) > 2) {
      $text .= ' ' . $clean_string;
    }
  }
  
  return $text;
}
