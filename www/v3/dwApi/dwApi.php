<?php
namespace dwApi;
use dwApi\api\DwapiException;
use dwApi\api\Mail;
use dwApi\api\Project;
use dwApi\api\Request;
use dwApi\token\JwtToken;
use dwApi\endpoint\Endpoint;
use dwApi\query\QueryFactory;
use dwApi\endpoint\EndpointFactory;
use dwApi\api\Response;
use dwApi\token\TokenFactory;


/**
 * Class dwApi
 * @package dwApi
 */
class dwApi
{
  const API_VERSION = "v3";
  //const API_PATH = "https://dwapi.dev/".self::API_VERSION;
  const API_PATH = "http://dwapi.local/".self::API_VERSION;

  private $request;
  private $project;

  /**
   * @var JwtToken
   */
  private $current_token;

  /**
   * @var Endpoint;
   */
  private $endpoint;

  private $response = NULL;
  private $logged_in_user = NULL;

  /**
   * Api constructor.
   */
  public function __construct() {

  }

  /**
   *
   */
  public function processCall() {
    try {
      $this->request = Request::getInstance();
      $this->response = Response::getInstance();
      $this->project = Project::getInstance();

      if ($this->request->initPath()) {

        $this->current_token = TokenFactory::create($this->request->token_type, $this->request->token);
        if ($this->current_token->valid) {
          $this->logged_in_user = array(
            "id" => $this->current_token->data["user_id"],
            "item" => $this->current_token->token_user);
        }

        if ($this->request->isTokenRequired()) {
          if ($this->current_token->valid == false) {
            if ($this->request->entity == "user") {
              throw new DwapiException('For a user query a valid token is always required', DwapiException::DW_VALID_TOKEN_REQUIRED);
            }
            else {
              throw new DwapiException('Valid token is required', DwapiException::DW_VALID_TOKEN_REQUIRED);
            }
          }
        }

        /* create Endpoint instance according to the endpoint parameter in the Request */
        $this->endpoint = EndpointFactory::create($this);

        /* create Query instance according to the endpoint parameter in the Request */
        $this->endpoint->query = QueryFactory::create($this->request->entity, $this->logged_in_user);
        $this->endpoint->execute($this->request->action);
      }

      if (!is_null($this->request->mail) && $this->request->mail["enabled"] == true) {
        $mail = new Mail();
        $mail->send();
      }

    } catch (\Exception $error) {
      $this->response->error = $error;
    }
  }

  /**
   * @return mixed
   */
  public function getCurrentToken() {
    return $this->current_token;
  }


  /**
   * @return mixed
   */
  public function getLoggedInUser() {
    return $this->logged_in_user;
  }
}
?>