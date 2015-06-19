<?php

namespace CINC;

/**
 * Provides functionality for configuration projects.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class Project {

  public $name;
  public $metadata;
  public $yaml;
  public $importers;
  public $exporters;

  /**
   * @param string $name  The project name.
   */
   public function __construct($name)
   {
       $this->name = $name;
       $this->metadata = array();
       $this->yaml = array();
       $this->importers = array();
       $this->exporters = array();
   }

   public function import($type, $parameters) {

    if (isset($this->importers[$type])) {
      $this->importers[$type]->import($this, $parameters);
    }

   }

   public function export($type, $parameters) {

     if (isset($this->exporters[$type])) {
       $this->exporters[$type]->export($this, $parameters);
     }

   }

}
