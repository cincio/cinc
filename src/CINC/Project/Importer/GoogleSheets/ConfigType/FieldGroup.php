<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

use Symfony\Component\Yaml\Parser;

/**
 * Provides Google Sheets import functionality for field groups.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class FieldGroup {

  function sheetsToLists($importer) {

    $parser = new Parser();
    $result = array();
    $columns = array();

    // Get the right worksheet.
    $worksheet = $importer->worksheetFromSheets(
      $importer->sheets,
      array('field groups')
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
      if (isset($resultRow['group settings'])) {
        try {
          $resultRow['group settings'] = (array) $parser->parse(
            $resultRow['group settings']
          );
        } catch (\Exception $e) {
          $resultRow['group settings'] = array();
        }
      }
      else {
        $resultRow['group settings'] = array();
      }
      if (isset($resultRow['label'])) {
        $resultRow['group settings']['label'] = $resultRow['label'];
      }
      if (
        (isset($resultRow['required'])) &&
        (in_array($resultRow['required'], array('y','Y','Yes','1')))
      ) {
        $resultRow['required'] = 1;
      }
      $result[] = $resultRow;
    }

    $importer->lists['field_group'] = $result;
  }

  function listsToYaml($importer, $project) {
    if (isset($importer->lists['field_group'])) {
      foreach ($importer->lists['field_group'] as $fieldGroup) {
        $children = array();
        $entityType = 'node';
        $mode = 'form';
        $weight = 0;
        if (isset($importer->lists['field_instance'])) {
          foreach ($importer->lists['field_instance'] as $nodeType => $typeFieldInstances) {
            foreach ($typeFieldInstances as $index => $field) {
              if (
                (isset($field['field group'])) &&
                ($field['field group'] == $fieldGroup['machine name'])
              ) {
                if (count($children) == 0) {
                  $bundle = $nodeType;
                  $weight = $index + 1;
                }
                $children[] = $field['field machine name'];
              }
            }
          }
        }
        $identifier = implode('|',
          array($fieldGroup['machine name'], $entityType, $bundle, $mode)
        );
        $groupExport = array(
          'identifier' => $identifier,
          'group_name' => $fieldGroup['machine name'],
          'entity_type' => $entityType,
          'bundle' => $bundle,
          'mode' => $mode,
          'parent_name' => '',
          'table' => 'field_group',
    			'type' => 'Normal',
    			'disabled' => false,
    			'label' => $fieldGroup['label'],
          'weight' => $weight,
          'children' => $children,
          'format_type' => 'fieldset',
          'format_settings' => array() + $fieldGroup['group settings'],
        );
        $name = 'field_group.' . $identifier . '.yml';
        $project->yaml[$name] = $importer->yamlDumper->dump($groupExport, 9999);
      }
    }
  }

}
