<?php
namespace dwApi\api;

use dwApi\dwApi;

/**
 * Class Response
 * @package dwApi\api
 */
class Response {
  private $request;

  public $http_response_code = 200;
  public $result;
  public $debug;

  /**
   * @var \Exception|DwapiException
   */
  public $error;

  private static $instance = null;


  /**
   * Response constructor.
   */
  public function __construct() {
    $this->request = Request::getInstance();
  }


  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new Response();
    }

    return self::$instance;
  }

  /**
   * getTwigVariables.
   * @return mixed
   * @throws DwapiException
   */
  public function getTwigVariables() {
    $variables = [];

    if ($this->error == NULL) {
      $variables["status"] = array("success" => true);
    }
    else {
      $variables["status"] = array(
        "success" => false,
        "error_code" => $this->error->getCode(),
        "message" => $this->error->getMessage());
    }
    $variables["result"] = $this->result;
    $variables["settings"] = Project::getInstance()->site;
    $variables["settings"]["api_path"] = dwApi::API_PATH;
    $variables["parameters"] = $this->request->getParameters();

    return Helper::maskValue($variables);
  }

  /**
   * getJsonVariables.
   * @return mixed
   * @throws DwapiException
   */
  public function getJsonVariables() {
    $variables = [];

    if ($this->error != NULL) {
      $variables["status"] = array(
        "success" => false,
        "error_code" => $this->error->getCode(),
        "message" => $this->error->getMessage());
    }
    else {
      $variables["status"] = array(
        "success" => true);

      $variables["result"] = $this->result;
      if ($this->request->debug == true) {
        $variables["debug"] = $this->debug;
      }

      $variables["parameters"] = $this->request->getParameters();
    }

    return Helper::maskValue($variables);
  }

}