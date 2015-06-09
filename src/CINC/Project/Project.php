<?php

namespace CINC\Project;

/**
 * Provides functionality for configuration projects.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class Project {

  public $name;
  public $owner;
  public $metadata;
  public $yaml;

  /**
   * @param string $name  The project name.
   */
   public function __construct($name)
   {
       $this->name = $name;
       $this->owner = NULL;
       $this->metadata = array();
       $this->yaml = array();
   }

   /**
    * @param string $tsv  A tab-seperated string.
    */
   public function fromTSV($tsv) {

   }

   public function toTar() {

   }

}
