<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

/**
 * Provides Google Sheets import functionality for menus.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class Menu {

  function sheetsToLists($importer) {

    $result = array();
    $columns = array();

    // Get the right worksheet.
    $worksheet = $importer->worksheetFromSheets(
      $importer->sheets,
      array('menus')
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

    $importer->lists['menu'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['menu'])) {
      foreach ($importer->lists['menu'] as $menu) {
        $menuExport = array(
          'id' => $menu['machine name'],
          'title' => isset($menu['title']) ? $menu['title'] : '',
        );
        $name = 'system.menu.' . $menu['machine name'] . '.yml';
        $project->yaml[$name] = $importer->yamlDumper->dump($menuExport, 9999);
      }
    }
  }

}
