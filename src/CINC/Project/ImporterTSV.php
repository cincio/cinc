<?php

namespace CINC\Project;

/**
 * Provides TSV import functionality for configuration projects.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class ImporterTSV {

  public function import(\CINC\Project $project, $parameters) {

    $lines = explode("\n", $parameters['tsv']);

    foreach ($lines as $line) {

      list($machine, $display, $description) = explode("\t", trim($line));
      $type = implode("\n", array(
        "name: '" . $display . "'",
        'type: ' . $machine,
        "description: '" . $description . "'"
      ));
      $project->yaml['node.type.' . $machine . '.yml'] = $type;

    }

  }

}
