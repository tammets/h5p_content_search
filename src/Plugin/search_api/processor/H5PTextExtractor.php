<?php

namespace Drupal\h5p_content_search\Plugin\search_api\processor;

use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;

/**
 * Extracts text from H5P JSON content for indexing.
 *
 * @SearchApiProcessor(
 *   id = "h5p_text_extractor",
 *   label = @Translation("H5P Text Extractor"),
 *   description = @Translation("Flattens H5P JSON data into searchable text."),
 *   stages = {
 *     "add_properties" = 0,
 *     "preprocess_index" = -10,
 *   }
 * )
 */
class H5PTextExtractor extends ProcessorPluginBase {

  /**
   * Defines a new "virtual" property for the search index.
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource && $datasource->getEntityTypeId() === 'node') {
      $definition = [
        'label' => $this->t('H5P Extracted Text'),
        'description' => $this->t('All text extracted from H5P parameters.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['h5p_extracted_text'] = new \Drupal\search_api\Processor\ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * Preprocesses search items for indexing.
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      $this->addFieldValues($item);
    }
  }

  /**
   * Adds the extracted H5P text values to the search index.
   */
  public function addFieldValues(ItemInterface $item) {
    $flat_text = '';

    // Get the entity (Node).
    $entity = $item->getOriginalObject()->getValue();

    // Check if the H5P field exists and has data.
    if ($entity->hasField('field_h5p') && !$entity->get('field_h5p')->isEmpty()) {
      $h5p_item = $entity->get('field_h5p')->first()->getValue();
      $h5p_content_id = $h5p_item['h5p_content_id'] ?? NULL;

      if ($h5p_content_id) {
        // Load the raw H5P parameters from the database.
        $h5p_content = \Drupal::database()->select('h5p_content', 'hc')
          ->fields('hc', ['parameters'])
          ->condition('id', $h5p_content_id)
          ->execute()
          ->fetchField();

        if ($h5p_content) {
          $decoded = json_decode($h5p_content, TRUE);

          // Extract text using the helper function.
          $flat_text = $this->extractStrings($decoded);

          // Add the flattened text to the search index.
          $fields = $item->getFields();
          
          // Add value directly to the h5p_extracted_text field.
          if (isset($fields['h5p_extracted_text'])) {
            $fields['h5p_extracted_text']->addValue($flat_text);
          }
        }
      }
    }
  }

  /**
   * Recursively extracts all string values from H5P JSON data.
   *
   * @param mixed $data
   *   The H5P parameters data (array or string).
   *
   * @return string
   *   Concatenated text extracted from the data.
   */
  private function extractStrings($data) {
    $text = '';

    if (is_array($data)) {
      foreach ($data as $value) {
        $text .= ' ' . $this->extractStrings($value);
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

}