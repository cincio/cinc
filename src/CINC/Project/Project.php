<?php

namespace CINC\Project;

use Pimple;

/**
 * Provides functionality for configuration projects.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class Project extends Pimple {

  public $name;
  public $metadata;
  public $yaml;

  /**
   * @param string $name  The project name.
   */
   public function __construct($name)
   {
       $this->name = $name;
       $this->metadata = array();
       $this->yaml = array();
   }

}
