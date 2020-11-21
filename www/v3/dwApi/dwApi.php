<?php
namespace dwApi;
use dwApi\api\Mail;
use dwApi\api\Project;
use dwApi\api\Request;
use dwApi\api\Token;
use dwApi\api\Route;
use dwApi\query\QueryFactory;
use dwApi\endpoint\EndpointFactory;
use dwApi\api\Response;


/**
 * Class dwApi
 * @package dwApi
 */
class dwApi
{
  const API_VERSION = "v3";
  const API_PATH = "https://dwapi.dev/".self::API_VERSION;
  //const API_PATH = "http://dwapi.local/".self::API_VERSION;

  private $request;
  private $project;
  private $route;
  private $current_token;
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
        $this->route = new Route($this->request);
        //if ($this->route->validPath()) {
        $this->current_token = new Token($this->request->project, $this->request->token);
        if ($this->route->tokenValidIfRequired($this->current_token)) {
          if ($this->current_token->valid) {
            $this->logged_in_user = QueryFactory::create("user");
            $this->logged_in_user->id = $this->current_token->data["user_id"];
            $this->logged_in_user->single_read();
          }

          /* create Endpoint instance according to the endpoint parameter in the Request */
          $this->endpoint = EndpointFactory::create($this);

          /* create Query instance according to the endpoint parameter in the Request */
          $this->endpoint->query = QueryFactory::create($this->request->entity);
          $this->endpoint->execute($this->request->action);
        }
        //}
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