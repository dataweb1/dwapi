<?php
require_once "class/helper.php";
require_once "class/ApiRequest.php";
include_once 'class/ApiProject.php';
require_once 'class/database.php';
require_once 'class/ApiToken.php';
include_once 'class/entity.php';
include_once 'class/ApiMail.php';
include_once 'class/item.php';
include_once 'class/user.php';

require __DIR__."/../../../vendor/autoload.php";

class Api {
  public $project;
  public $logged_in_user_id = NULL;

  public $endpoint;
  public $action;

  public $request;

  public $output = array(
    "code" => 200,
    "status" => NULL,
    "data" => NULL);

  public $db;

  public $token = NULL;

  /**
   * Api constructor.
   */
  public function __construct() { }

  /**
   * @throws Exception
   */
  public function init() {
    $this->request = new ApiRequest();

    $this->endpoint = $this->request->getParameters("get", "endpoint");
    $this->action = $this->request->getParameters("get", "action");

    $this->validateActionAndMethod();

    $this->project = new ApiProject($this);

    $this->token = new ApiToken($this);
  }

  /**
   * @throws Exception
   */
  private function validateActionAndMethod() {
    switch ($this->action) {
      case "single_read": case "read": case "validate_token": case "extend_token": case "logout":
      if ($this->request->method != "get") {
        throw new Exception('Method not valid, GET expected.',400);
      }
      break;
      case "login": case "register": case "create":
      if ($this->request->method != "post") {
        throw new Exception('Method not valid, POST expected.', 400);
      }
      break;
      case "update": case "single_update":
      if ($this->request->method != "put" && $this->request->method != "post") {
        throw new Exception('Method not valid, PUT expected.', 400);
      }
      break;
      case "delete":
        if ($this->request->method != "delete") {
          throw new Exception('Method not valid, DELETE expected.', 400);
        }
        break;
      case "":
        throw new Exception('CRUD action is required.', 400);
        break;
      default:
        throw new Exception('CRUD action is invalid.', 400);
    }
  }

  public function processEndpoint() {
    switch ($this->endpoint) {
      case "item":
        require "views/item.php";
        break;
      case "user":
        require "views/user.php";
        break;
      default:
        throw new Exception('Endpoint not valid', 400);
    }
  }

  public function processMail() {

     $mail = new ApiMail("single_read", $this->parameters["mail"], $items);

          //$mail->send();

  }

  /**
   *
   */
  public function renderOutput() {
    $output = [];

    if ($this->output["status"] != null) {
      $output["status"] = $this->output["status"];
    }
    if ($this->output["data"] != null) {
      $output["data"] = $this->output["data"];
    }
    $output["parameters"] = $this->request->getParameters();

    http_response_code($this->output["code"]);
    echo json_encode($output, JSON_PRETTY_PRINT);
  }



  /**
   * @param $error
   */
  public function renderError($error) {
    $response_code = strval($error->getCode());
    if (strlen($response_code) > 3) { $response_code = 400; }

    http_response_code(intval($response_code));

    $output = array(
      "status" => array(
        "success" => false,
        "error_code" => $error->getCode(),
        "message" => $error->getMessage()),
      "parameters" => $this->request->getParameters());


    echo json_encode($output, JSON_PRETTY_PRINT);
  }
}
?>