<?php
namespace dwApi\api;
use dwApi\endpoint\Endpoint;
use dwApi\dwApi;
use dwApi\api\Token;


/**
 * Class Route
 * @package dwApi\api
 */
class Route {

  private $request;

  static $action_method_allowed = array(
    "single_read" => "get",
    "read" => "get",
    "create" => "post",
    "update" => "put",
    "single_update" => "put",
    "delete" => "delete",
    "register" => "post",
    "reset_password" => "get",
    "reset_link" => "get",
    "confirm_password" => "post",
    "login" => "post",
    "logout" => "get",
    "validate_token" => "get",
    "extend_token" => "get",
    "activate_link" => "get");


  /**
   * Route constructor.
   * @param Request $request
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }


  /**
   * @return bool
   * @throws ErrorException
   */
  public function validRoute() {
    if (!$this->validProject() ||
      !$this->validEndpoint() ||
      !$this->validAction() ||
      !$this->validActionWithMethod()) {
      return false;
    }

    return true;
  }

  /**
   * @return bool
   * @throws ErrorException
   */
  private function validActionWithMethod()
  {
    $action_method_allowed = self::$action_method_allowed[$this->request->action];

    if (!$action_method_allowed == $this->request->method) {
      throw new ErrorException('Method not valid, '.strtoupper($action_method_allowed).' expected.', ErrorException::DW_INVALID_METHOD);
    }

    return true;
  }


  /**
   * @return bool
   */
  private function validProject() {

    Project::getInstance();

    return true;
  }


  /**
   * @return bool
   * @throws ErrorException
   */
  private function validEndpoint() {
    $endpoint = $this->request->endpoint;

    if ($endpoint == "") {
      throw new ErrorException('Endpoint is required.', ErrorException::DW_ENDPOINT_REQUIRED);
      return false;
    }

    $endpoint_class_name = "dwApi\\endpoint\\".ucfirst($endpoint);
    if (!class_exists($endpoint_class_name)) {
      throw new ErrorException('Endpoint "'.$endpoint_class_name.'" not valid', ErrorException::DW_INVALID_ENDPOINT);
    }

    return true;
  }

  /**
   * @return bool
   * @throws ErrorException
   */
  private function validAction() {
    $endpoint = $this->request->endpoint;
    $action = $this->request->action;

    if ($action == "") {
      throw new ErrorException('CRUD action is required.', ErrorException::DW_CRUD_ACTION_REQUIRED);
    }

    $endpoint_class_name = "dwApi\\endpoint\\".ucfirst($endpoint);
    if (!method_exists($endpoint_class_name, $action)) {
      throw new ErrorException('Action not valid', ErrorException::DW_INVALID_ACTION);
    }

    return true;
  }

  /**
   * @param \dwApi\api\Token $current_token
   * @return bool
   * @throws ErrorException
   */
  public function tokenValidIfRequired(Token $current_token) {
    $token_required = $this->request->token_required;
    $entity_type = $this->request->entity;
    $endpoint = $this->request->endpoint;
    $action = $this->request->action;

    // TODO: isTokenRequired via config and NOT via function logic
    if (is_null($token_required)) {
      if ($entity_type == NULL) {
        $entity_type = $endpoint;
      }
      $token_required = $this->isTokenRequired($entity_type, $action);
      if ($current_token->valid  == false) {
      }
    }

    if ($token_required == true) {
      if ($current_token->valid == false) {
        throw new ErrorException('Valid token is required', ErrorException::DW_VALID_TOKEN_REQUIRED);
      }
    }

    return true;
  }


  /**
   * @param $entity_type
   * @param $action
   * @return bool
   */
  private function isTokenRequired($entity_type, $action) {
    switch ($action) {
      case "login": case "register": case "activate_link": case "reset_password": case "reset_link": case "confirm_password":
    return false;
      break;
      case "single_read": case "read":
      if ($entity_type == "user") {
        return true;
      }
      else {
        return false;
      }
      break;
      default:
        return true;
    }
  }
}