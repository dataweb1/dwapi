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
  public function validPath() {
    if ($this->request->current_path) {
      return true;
    }
    else {
      throw new ErrorException('Path/method not valid.', ErrorException::DW_INVALID_PATH);
    }
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
    if ($this->request->path_definition->isParameterRequired("header_authorization")) {
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