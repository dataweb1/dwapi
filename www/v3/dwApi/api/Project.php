<?php
namespace dwApi\api;



/**
 * Class Project
 * @package dwApi\api
 */
class Project {
  public $key;
  public $settings;
  private $request;
  private static $instance = null;


  /**
   * Project constructor.
   * @throws ErrorException
   */
  public function __construct()
  {
    $this->request = Request::getInstance();

    $this->key = $this->request->project;
    if ($this->key == "") {
      throw new ErrorException('Project key is required', ErrorException::DW_PROJECT_REQUIRED);
    }

    // read project from project.yml
    if ($project = Helper::readYaml($_SERVER["DOCUMENT_ROOT"].'/settings/projects.yml', $this->key)) {
      $this->settings = $project;
    } else {
      throw new ErrorException('Project "' . $this->key . '" not found', ErrorException::DW_PROJECT_NOT_FOUND);
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