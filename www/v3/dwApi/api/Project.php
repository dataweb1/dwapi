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
   * @throws ErrorException
   */
  public function __construct()
  {
    $this->project = Request::getInstance()->project;

    if ($this->project == "") {
      throw new ErrorException('Project key is required', ErrorException::DW_PROJECT_REQUIRED);
    }

    // read project from project.yml
    if ($project = Helper::readYaml($_SERVER["DOCUMENT_ROOT"].'/settings/projects.yml', $this->project)) {
      $this->type = $project["type"];
      $this->credentials = $project["credentials"];
      $this->site = $project["site"];
    } else {
      throw new ErrorException('Project "' . $this->project . '" not found', ErrorException::DW_PROJECT_NOT_FOUND);
    }
  }


  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new Project();
    }

    return self::$instance;
  }

}