<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

/**
 * Provides Google Sheets import functionality for content types.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class ContentType {

  function sheetsToLists($importer) {

    $result = array();
    $columns = array();

    // Get the right worksheet.
    $worksheet = $importer->worksheetFromSheets(
      $importer->sheets,
      array('node types', 'content types')
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

    $importer->lists['content_type'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['content_type'])) {
      foreach ($importer->lists['content_type'] as $contentType) {
        $type = array(
          'name' => $contentType['name'],
          'type' => $contentType['machine name'],
          'description' => isset($contentType['description']) ?
            $contentType['description'] : '',
          'create_body' => FALSE,
          'pathauto' => isset($contentType['pathauto']) ?
            $contentType['pathauto'] : '',
        );
        $name = 'node.type.' . $contentType['machine name'] . '.yml';
        $project->yaml[$name] = $importer->yamlDumper->dump($type, 9999);
      }
    }
  }

}
