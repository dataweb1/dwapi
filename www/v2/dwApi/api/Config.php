<?php
namespace dwApi\api;
use Symfony\Component\Yaml\Yaml;


/**
 * Class Settings
 * @package dwApi\api
 */
class Settings {
  public static $API_PATH;
  public $api;
  public $key;
  public $settings;
  private $request;
  private static $instance = null;

  public function __construct()
  {
    $this->request = Request::getInstance();
    self::$API_PATH = "https://".$_SERVER["HTTP_HOST"]."/v2";

    $this->key = $this->request->project;
    if ($this->key == "") {
      throw new ErrorException('Project key is required', ErrorException::DW_PROJECT_REQUIRED);
    }

    /**
     * Read projects.yml
     */
    $projects = Yaml::parse(file_get_contents($_SERVER["DOCUMENT_ROOT"].'/settings/projects.yml'));

    if (array_key_exists($this->key, $projects)) {
      $this->settings = $projects[$this->key];
      $this->settings["api_path"] = self::$API_PATH;

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