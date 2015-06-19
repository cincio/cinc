<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

use Symfony\Component\Yaml\Parser;

/**
 * Provides Google Sheets import functionality for fields.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class Field {

  function sheetsToLists($importer) {

    static $typeSynonyms = array(
      'longtext' => 'text_with_summary',
      'long text' => 'text_with_summary',
      'term reference' => 'taxonomy_term_reference',
      'term ref' => 'taxonomy_term_reference',
      'termreference' => 'taxonomy_term_reference',
      'entity reference' => 'entityreference',
      'entity ref' => 'entityreference',
      'entityreference' => 'entityreference',
      'link' => 'link_field',
      'select list' => 'list_text',
      'list (text)' => 'list_text',
      'list (integer)' => 'list_integer',
      'list' => 'list_text',
      'video embed' => 'video_embed_field',
      'integer' => 'number_integer',
      'boolean' => 'list_boolean',
    );

    $parser = new Parser();
    $result = array();
    $columns = array();

    // Get the right worksheet.
    $worksheet = $importer->worksheetFromSheets(
      $importer->sheets,
      array('fields')
    );
    if (!$worksheet) {
      return FALSE;
    }

    // Map header names to column numbers.
    $header = array_shift($worksheet);
    foreach ($header as $index => $name) {
      $columns[strtolower($name)] = $index;
    }

    // Reorganize rows by header names.
    foreach ($worksheet as $row) {

      $resultRow = array();

      foreach ($columns as $name => $index) {
        if (isset($row[$index])) {
          $resultRow[$name] = $row[$index];
        }
      }

      if (isset($resultRow['type']) && !empty($resultRow['type'])) {

        $resultRow['type'] = strtolower($resultRow['type']);

        if (isset($typeSynonyms[$resultRow['type']])) {
          $resultRow['type'] = $typeSynonyms[$resultRow['type']];
        }

      }

      if (isset($resultRow['field settings'])) {

        try {
          $resultRow['field settings'] = $parser->parse($resultRow['field settings']);
        } catch (\Exception $e) {
          unset($resultRow['field settings']);
        }

      }

      if (isset($resultRow['# values'])) {
        if (strtolower($resultRow['# values']) == 'unlimited') {
          $resultRow['# values'] = -1;
        }
      }
      else {
        $resultRow['# values'] = 1;
      }

      if (isset($resultRow['machine name']) && !empty($resultRow['machine name'])) {
        $result[] = $resultRow;
      }

    }

    $importer->lists['field'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['field'])) {
      $importer->lists['field_name_to_type'] = array();
      foreach ($importer->lists['field'] as $field) {
        if (!isset($field['entity type'])) {
          $field['entity type'] = 'node';
        }
        $field_export = array(
          'id' => $field['entity type'] . '.' . $field['machine name'],
          'field_name' => $field['machine name'],
          'entity_type' => $field['entity type'],
          'type' => $field['type'],
          'cardinality' => $field['# values'],
          'settings' => isset($field['field settings']) && is_array($field['field settings']) ? $field['field settings'] : array(),
        );
        $importer->lists['field_name_to_type'][$field['machine name']] = $field['type'];
        $name = 'field.storage.' . $field['entity type'] . '.' . $field['machine name'] . '.yml';
        $project->yaml[$name] = $importer->yamlDumper->dump($field_export, 9999);
      }
    }
  }

}
