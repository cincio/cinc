<?php

namespace CINC\Project;

use CINC\Project;
use Silex\ServiceProviderInterface;

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

    $app['cinc.project'] = $app->protect(function ($name) uses ($app) {
        return new Project($name);
    });

  }

  public function boot(Application $app)
  {
  }
}
