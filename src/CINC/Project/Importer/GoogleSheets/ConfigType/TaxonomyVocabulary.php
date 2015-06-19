<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

/**
 * Provides Google Sheets import functionality for taxonomy vocabularies.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class TaxonomyVocabulary {

  function sheetsToLists($importer) {

    $result = array();
    $columns = array();

    // Get the right worksheet.
    $worksheet = $importer->worksheetFromSheets(
      $importer->sheets,
      array('vocabs', 'vocabularies', 'taxonomies')
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
      if (
        (isset($resultRow['machine name'])) &&
        (!empty($resultRow['machine name']))
      ) {
        $result[] = $resultRow;
      }
    }

    $importer->lists['taxonomy_vocabulary'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['taxonomy_vocabulary'])) {
      foreach ($importer->lists['taxonomy_vocabulary'] as $vocab) {
        $taxonomyVocab = array(
          'name' => $vocab['name'],
          'machine_name' => $vocab['machine name'],
          'description' => isset($vocab['description']) ? $vocab['description'] : '',
        );
        $name = 'taxonomy.vocabulary.' . $vocab['machine name'] . '.yml';
        $project->yaml[$name] = $importer->yamlDumper->dump($taxonomyVocab, 9999);
      }
    }
  }

}
