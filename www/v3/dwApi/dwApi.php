<?php
namespace dwApi;
use dwApi\api\Mail;
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
  private $request;
  private $route;
  private $current_token;
  private $endpoint;

  private $response = NULL;
  private $logged_in_user = NULL;

  /**
   * Api constructor.
   */
  public function __construct() {
    $this->request = Request::getInstance();
    $this->response = Response::getInstance();
  }

  /**
   *
   */
  public function processCall() {
    try {
      $this->route = new Route($this->request);
      if ($this->route->validRoute()) {
        $this->current_token = new Token($this->request->project, $this->request->token);
        if ($this->route->tokenValidIfRequired($this->current_token)) {
          if ($this->current_token->valid) {
            $this->logged_in_user = QueryFactory::create("user");
            $this->logged_in_user->id = $this->current_token->data["user_id"];
            $this->logged_in_user->single_read();
          }

          /* create Endpoint instance according to the endpoint parameter in the Request */
          $this->endpoint = EndpointFactory::create($this, $this->request->endpoint);

          /* create Query repository instance according to the endpoint parameter in the Request */
          $this->endpoint->query = QueryFactory::create($this->request->endpoint, $this->request->entity);
          $this->endpoint->doAction($this->request->action);
        }
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