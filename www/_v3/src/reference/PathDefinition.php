<?php
namespace dwApi\reference;

/**
 * Class Path
 * @package dwApi\reference
 */
class PathDefinition {
  private $spec_path;
  private $spec_path_elements;
  private $base_path;
  private $base_path_elements;
  private $parameters;


  /**
   * Path constructor.
   * @param $spec_path
   * @param $path_definition
   * @param $request_method
   */
  public function __construct($spec_path, $path_definition, $request_method) {

    $this->parameters = [];
    foreach($path_definition[$request_method] as $item => $value){
      if ($item == "parameters") {
        foreach((array)$value as $parameter) {
          $in = $parameter["in"];
          if ($in == "formData") {
            $in = $request_method;
          }
          $this->parameters[$in."_".$parameter["name"]] = $parameter;
        }
      }
      else {
        $this->{$item} = $value;
      }
    }

    foreach((array)$path_definition["parameters"] as $parameter) {
      $this->parameters[$parameter["in"]."_".$parameter["name"]] = $parameter;
    }

    $this->spec_path = $spec_path;
    $this->spec_path_elements = explode("/", $this->spec_path);
    array_shift($this->spec_path_elements);

    $this->base_path = $this->getBasePath();
    $this->base_path_elements = explode("/", $this->base_path);
    array_shift($this->base_path_elements);

    $this->setPathParametersIndex();
  }


  /**
   * @return mixed
   */
  public function getBasePath() {
    $base_path = $this->spec_path;

    $path_parameters = $this->getPathParameters();
    foreach ($path_parameters as $parameter_key => $parameter) {
      $base_path = str_replace("/{" . $parameter["name"] . "}", "", $base_path);
    }

    return $base_path;
  }


  /**
   * @param $index
   * @return mixed
   */
  public function getBasePathElement($index) {
    return $this->base_path_elements[$index];
  }


  /**
   * @return array
   */
  public function getPathParameters() {
    $path_parameters = [];
    foreach ($this->parameters as $parameter_key => $parameter) {
      if (substr($parameter_key, 0, 5) == "path_") {
        $path_parameters[] = $parameter;
      }
    }
    return $path_parameters;
  }

  /**
   * @param $key
   * @return bool|mixed
   */
  public function getParameter($key) {
    if (array_key_exists($key, $this->parameters)) {
      return $this->parameters[$key];
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

    foreach ($this->parameters as $parameter) {
      if ($parameter["required"] == 1) {
        $required_parameters[strtolower($parameter["in"] . "_" . $parameter["name"])] = $parameter;
      }
    }

    return $required_parameters;
  }

  /**
   *
   */
  private function setPathParametersIndex() {
    foreach($this->spec_path_elements as $element_key => $element) {
      if (substr($element, 0, 1) == "{" && substr($element, strlen($element)-1, 1) == "}") {
        $name = substr($element, 1, strlen($element)-2);
        $this->parameters["path_".$name]["key"] = $element_key;
      }
    }
  }

  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new PathDefinition();
    }

    return self::$instance;
  }

}