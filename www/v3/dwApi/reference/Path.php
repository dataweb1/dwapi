<?php
namespace dwApi\reference;



use dwApi\api\Request;

/**
 * Class Path
 * @package dwApi\reference
 */
class Path {

  /**
   * Path constructor.
   * @throws ErrorException
   */
  public function __construct($properties) {
    foreach($properties as $key => $value){
      $this->{$key} = $value;
    }
  }


  /**
   * @param $method
   * @return bool
   */
  public function methodExists($method) {
    if (isset($this->$method)) {
      return true;
    }
    return false;
  }


  /**
   * @return array
   */
  public function getRequiredParameters() {
    $required_parameters = [];

    //path parameters
    if (isset($this->parameters)) {
      foreach ($this->parameters as $parameter) {
        if ($parameter["required"] == 1) {
          $required_parameters[strtolower($parameter["in"] . "_" . $parameter["name"])] = $parameter;
        }
      }
    }

    //request parameters
    foreach($this->{Request::getInstance()->method}["parameters"] as $parameter) {
      if ($parameter["required"]==1) {
        $required_parameters[strtolower($parameter["in"]."_".$parameter["name"])] = $parameter;
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