<?php
use Symfony\Component\Yaml\Yaml;

class ApiProject{
  public $key;
  public $settings;
  public $db;

  public function __construct($api)
  {
    $project_key = $api->request->getParameters("get", "project");
    if ($project_key == "") {
      throw new Exception('Project key is required',400);
    }

    /**
     * Read projects.yml
     */
    $projects = Yaml::parse(file_get_contents(__DIR__ . '/../../settings/projects.yml'));

    if (array_key_exists($project_key, $projects)) {
      $settings = $projects[$project_key];
      $this->key = $project_key;
      $this->settings = $settings;

      $this->db = new Database($this->settings["db_credentials"]);
    } else {
      throw new Exception('Project "' . $project_key . '" not found', 404);
    }
  }
}