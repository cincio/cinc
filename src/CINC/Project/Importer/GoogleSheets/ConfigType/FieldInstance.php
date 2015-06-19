<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

use Symfony\Component\Yaml\Parser;

/**
 * Provides Google Sheets import functionality for field instances.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class FieldInstance {

  function sheetsToLists($importer) {

    $parser = new Parser();
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
    $currentType = FALSE;
    foreach ($worksheet as $row) {
      $resultRow = array();
      foreach ($columns as $name => $index) {
        if (isset($row[$index])) {
          $resultRow[$name] = $row[$index];
        }
      }
      if (isset($resultRow['machine name']) && !empty($resultRow['machine name'])) {
        $currentType = $resultRow['machine name'];
      }
      if (isset($resultRow['field settings'])) {
        try {
          $resultRow['field settings'] = $parser->parse($resultRow['field settings']);
        } catch (\Exception $e) {
          unset($resultRow['field settings']);
        }
      }
      if (isset($resultRow['widget settings'])) {
        try {
          $resultRow['widget settings'] = $parser->parse($resultRow['widget settings']);
        } catch (\Exception $e) {
          unset($resultRow['field settings']);
        }
      }
      if (
        (isset($resultRow['required'])) &&
        (in_array($resultRow['required'], array('y', 'Y', 'Yes', 1)))
      ) {
        $resultRow['required'] = 1;
      }
      else {
        $resultRow['required'] = 0;
      }
      if (
        (isset($resultRow['field machine name'])) &&
        (!empty($resultRow['field machine name']))
      ) {
        if (!isset($result[$currentType])) {
          $result[$currentType] = array();
        }
        $result[$currentType][] = $resultRow;
      }
    }

    $importer->lists['field_instance'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['field_instance'])) {
      $nameToType =& $importer->lists['field_name_to_type'];
      foreach ($importer->lists['field_instance'] as $nodeType => $typeFieldInstances) {
        foreach ($typeFieldInstances as $index => $field) {
          if (isset($nameToType[$field['field machine name']])) {
            $fieldExport = array(
              'id' => 'node.' . $nodeType . '.' . $field['field machine name'],
              'label' => $field['label'],
              'entity_type' => 'node',
              'required' => $field['required'],
              'bundle' => $nodeType,
              'field_type' => $nameToType[$field['field machine name']],
              'field_name' => $field['field machine name'],
              'description' => isset($field['help text']) ? $field['help text'] : '',
              'settings' => isset($field['field settings']) && is_array($field['field settings']) ? $field['field settings'] : array(),
              'widget' => array(
                'weight' => ($index + 1),
              ),
              'dependencies' => array(
                'entity' => array(
                  'field.storage.node.' . $field['field machine name'],
                  'node.type.' . $nodeType,
                ),
              ),
            );
            $fieldExport['widget'] += isset($field['widget settings']) && is_array($field['widget settings']) ? $field['widget settings'] : array();
            $name = 'field.field.node.' . $nodeType . '.' . $field['field machine name'] . '.yml';
            $project->yaml[$name] = $importer->yamlDumper->dump($fieldExport, 9999);
          }
        }
      }
    }
  }

}
