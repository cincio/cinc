<?php

namespace CINC\Project\Importer\GoogleSheets\ConfigType;

use Symfony\Component\Yaml\Parser;

/**
 * Provides Google Sheets import functionality for field instances.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class FieldInstance {

  public $entityTypes = FALSE;

  function sheetsToLists($importer) {

    $parser = new Parser();

    $this->entityTypes = array(
      'node' => array(
        'sheet names' => array('node types', 'content types'),
        'dependencies' => function($bundle, $field, $importer) {
          return array(
            'entity' => array(
              'field.storage.node.' . $field['field machine name'],
              'node.type.' . $bundle,
            ),
          );
        },
      ),
      'field_collection_item' => array(
        'sheet names' => array('field collections'),
        'dependencies' => function($bundle, $field, $importer) {
          $fieldNodeBundle = '';
          foreach ($importer->lists['field_instance']['node'] as $nodeBundle => $typeFieldInstances) {
            foreach ($typeFieldInstances as $nodeField) {
              if ($nodeField['field machine name'] == $bundle) {
                $fieldNodeBundle = $nodeBundle;
              }
            }
          }
          return array(
            'entity' => array(
              'field.storage.field_collection_item.' . $field['field machine name'],
              'field.field.node.' . $fieldNodeBundle . '.' . $bundle,
            ),
          );
        },
      ),
      'bean' => array(
        'sheet names' => array('bean types'),
        'dependencies' => function($bundle, $field, $importer) {
          return array(
            'entity' => array(
              'field.storage.bean.' . $field['field machine name'],
              'bean_type.' . $bundle,
            ),
          );
        },
      ),
    );

    foreach ($this->entityTypes as $entityType => $entityTypeInfo) {

      // Get the right worksheet.
      $worksheet = $importer->worksheetFromSheets(
        $importer->sheets,
        $entityTypeInfo['sheet names']
      );
      if (!$worksheet) {
        break;
      }

      // Map header names to column numbers.
      $columns = array();
      $header = array_shift($worksheet);
      foreach ($header as $index => $name) {
        $columns[strtolower($name)] = $index;
      }

      // Reorganize rows by header names.
      $currentBundle = FALSE;
      $result = array();

      foreach ($worksheet as $row) {
        $resultRow = array();
        foreach ($columns as $name => $index) {
          if (isset($row[$index])) {
            $resultRow[$name] = $row[$index];
          }
        }
        if (isset($resultRow['machine name']) && !empty($resultRow['machine name'])) {
          $currentBundle = $resultRow['machine name'];
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
          if (!isset($result[$currentBundle])) {
            $result[$currentBundle] = array();
          }
          $result[$currentBundle][] = $resultRow;
        }
      }

      if (!isset($importer->lists['field_instance'])) {
        $importer->lists['field_instance'] = array();
      }

      $importer->lists['field_instance'][$entityType] = $result;

    }

  }

  function listsToYaml($importer, $project) {

    $nameToType =& $importer->lists['field_name_to_type'];

    foreach ($this->entityTypes as $entityType => $entityTypeInfo) {

      if (isset($importer->lists['field_instance'][$entityType])) {

        foreach ($importer->lists['field_instance'][$entityType] as $bundle => $typeFieldInstances) {

          foreach ($typeFieldInstances as $index => $field) {

            if (isset($nameToType[$field['field machine name']])) {

              $fieldExport = array(
                'id' => $entityType . '.' . $bundle . '.' . $field['field machine name'],
                'label' => $field['label'],
                'entity_type' => $entityType,
                'required' => $field['required'],
                'bundle' => $bundle,
                'field_type' => $nameToType[$field['field machine name']],
                'field_name' => $field['field machine name'],
                'description' => isset($field['help text']) ? $field['help text'] : '',
                'settings' => isset($field['field settings']) && is_array($field['field settings']) ? $field['field settings'] : array(),
                'widget' => array(
                  'weight' => ($index + 1),
                ),
                'dependencies' => $entityTypeInfo['dependencies']($bundle, $field, $importer),
              );
              $fieldExport['widget'] += isset($field['widget settings']) && is_array($field['widget settings']) ? $field['widget settings'] : array();
              $name = 'field.field.' . $entityType . '.' . $bundle . '.' . $field['field machine name'] . '.yml';
              $project->yaml[$name] = $importer->yamlDumper->dump($fieldExport, 9999);

            }

          }

        }

      }

    }

  }

}
