<?php

namespace CINC\Project;

use CINC\Project;
use Silex\ServiceProviderInterface;
use Silex\Application;

/**
 * Silex Service Provider for CINC Project.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class ProjectServiceProvider implements ServiceProviderInterface
{
  private $app;

  public function register(Application $app)
  {
    $this->app = $app;

    $app['cinc.project'] = $app->protect(function ($name) use ($app) {
        $project = new Project($name);
        $project->importers = $app['cinc.project.importers'] ? $app['cinc.project.importers'] : array();
        $project->exporters = $app['cinc.project.exporters'] ? $app['cinc.project.exporters'] : array();
        return $project;
    });

  }

  public function boot(Application $app)
  {
  }
}
