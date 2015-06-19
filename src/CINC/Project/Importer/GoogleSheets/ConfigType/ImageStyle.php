<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

/**
 * Provides Google Sheets import functionality for image styles.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class ImageStyle {

  function sheetsToLists($importer) {

    $result = array();
    $columns = array();

    // Get the right worksheet.
    $worksheet = $importer->worksheetFromSheets(
      $importer->sheets,
      array('image styles', 'imagestyles')
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
      if (isset($resultRow['style name']) && !empty($resultRow['style name'])) {
        $result[] = $resultRow;
      }
    }

    $importer->lists['image_style'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['image_style'])) {
      foreach ($importer->lists['image_style'] as $imageStyle) {
        $style = array(
          'name' => $imageStyle['style name'],
        );
        $name = 'image.style.' . $imageStyle['style name'] . '.yml';
        $project->yaml[$name] = $importer->yamlDumper->dump($style, 9999);
      }
    }
  }

}
