<?php
namespace dwApi\api;
use dwApi\reference\Reference;
use dwApi\endpoint\Endpoint;
use dwApi\dwApi;
use dwApi\api\Token;


/**
 * Class Route
 * @package dwApi\api
 */
class Route {

  private $request;
  private $reference;


  /**
   * Route constructor.
   * @param Request $request
   */
  public function __construct(Request $request) {
    $this->request = $request;
    $this->reference = Reference::getInstance();
  }


  /**
   * @return bool
   * @throws ErrorException
   */
  public function validRoute() {
    if (
      !$this->validReferencePath() ||
      !$this->validReferenceMethod() ||
      !$this->validEndpoint() ||
      !$this->validAction() ||
      !$this->validProject()) {
      return false;
    }

    return true;
  }

  /**
   * @return bool
   * @throws ErrorException
   */
  public function validReferencePath() {
    if ($this->reference->pathExits($this->request->path)) {
      return true;
    }
    else {
        throw new ErrorException('Path not valid.', ErrorException::DW_INVALID_PATH);
      }
  }


  /**
   * @return bool
   * @throws ErrorException
   */
  public function validReferenceMethod() {
    if ($this->reference->currentPath()->methodExists($this->request->method)) {
      return true;
    }
    else {
      throw new ErrorException('Method not valid.', ErrorException::DW_INVALID_METHOD);
    }
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

    if (is_null($token_required)) {
      if ($entity_type == NULL) {
        $entity_type = $endpoint;
      }
      $token_required = $this->isTokenRequired($entity_type, $action);
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
    if (array_key_exists("header_authorization", $this->reference->currentPath()->getRequiredParameters())) {
      return true;
    }
    else {
      switch ($action) {
        case "single_read":
        case "read":
          if ($entity_type == "user") {
            return true;
          }
          break;
      }
    }
    return false;
  }
}