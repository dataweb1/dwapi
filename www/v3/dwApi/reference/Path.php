<?php
namespace dwApi\reference;



use dwApi\api\Request;

/**
 * Class Path
 * @package dwApi\reference
 */
class Path {

  private $path_parameters;
  private $query_parameters;

  /**
   * Path constructor.
   * @param $path
   * @param $method
   */
  public function __construct($path, $method) {
    foreach($path[$method] as $key => $value){
      if ($key == "parameters") {
        $this->query_parameters = (array)$value;
      }
      else {
        $this->{$key} = $value;
      }
    }

    $this->path_parameters = (array)$path["parameters"];
  }


  /**
   * @param $method
   * @return bool
   */
  public function validMethod($method) {
    if (isset($this->$method)) {
      return true;
    }
    return false;
  }


  /**
   * @param $parameter
   * @return bool
   */
  public function isParameterRequired($parameter) {
    if (array_key_exists($parameter, $this->getRequiredParameters())) {
      return true;
    }
    return false;
  }

  /**
   * @return array
   */
  public function getRequiredParameters() {
    $required_parameters = [];

    $elements = ["path_parameters", "query_parameters"];
    foreach($elements as $element) {
      foreach ($this->{$element} as $parameter) {
        if ($parameter["required"] == 1) {
          $required_parameters[strtolower($parameter["in"] . "_" . $parameter["name"])] = $parameter;
        }
      }
    }

    return $required_parameters;
  }


  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new Reference();
    }

    return self::$instance;
  }

}