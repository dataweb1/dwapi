<?php
namespace dwApi\api;


/**
 * Class Project
 * @package dwApi\api
 */
class Project {
  public $project;
  public $type;
  public $credentials;
  public $site;

  private static $instance = null;


  /**
   * Project constructor.
   * @throws DwapiException
   */
  public function __construct()
  {
    $this->project = Request::getInstance()->project;

    if ($this->project == "") {
      throw new DwapiException('Project key is required', DwapiException::DW_PROJECT_REQUIRED);
    }

    // read project from project.yml
    if ($project = Helper::readYaml($_SERVER["DOCUMENT_ROOT"].'/settings/projects.yml', $this->project)) {
      $this->type = $project["type"];
      $this->credentials = $project["credentials"];
      $this->site = $project["site"];
    } else {
      throw new DwapiException('Project "' . $this->project . '" not found', DwapiException::DW_PROJECT_NOT_FOUND);
    }
  }


  /**
   * getInstance.
   * The object is created from within the class itself
   * only if the class has no instance.
   * @return Project|null
   * @throws DwapiException
   */

  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new Project();
    }

    return self::$instance;
  }

}