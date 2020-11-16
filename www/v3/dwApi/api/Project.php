<?php
namespace dwApi\api;



/**
 * Class Project
 * @package dwApi\api
 */
class Project {
  public $key;
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
    $key = Request::getInstance()->project;

    if ($key == "") {
      throw new ErrorException('Project key is required', ErrorException::DW_PROJECT_REQUIRED);
    }

    // read project from project.yml
    if ($project = Helper::readYaml($_SERVER["DOCUMENT_ROOT"].'/settings/projects.yml', $key)) {
      $this->key = $key;
      $this->type = $project["type"];
      $this->credentials = $project["credentials"];
      $this->site = $project["site"];
    } else {
      throw new ErrorException('Project "' . $key . '" not found', ErrorException::DW_PROJECT_NOT_FOUND);
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