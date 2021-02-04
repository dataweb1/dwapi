<?php
namespace dwApi\endpoint;
use dwApi\api\DwapiException;
use dwApi\api\JwtToken;
use dwApi\dwApi;
use dwApi\token\AccessToken;


/**
 * Class User
 * @package dwApi\endpoint
 */
class User extends Endpoint {
  public function __construct(dwApi $api)
  {
    parent::__construct($api);
  }

  /**
   * Login user.
   * @throws DwapiException
   */
  public function login() {
    $this->query->email = $this->request->getParameters("post", "email");
    $this->query->password = $this->request->getParameters("post", "password");


    if ($this->query->login()) {
      $this->current_token->token_user = $this->query->getResult("item");
      $this->current_token->token = $this->current_token->create($this->query->getResult("id"));

      $this->response->result = $this->query->getResult();
      $this->response->result["token"] = $this->current_token->token;
    }


    return true;
  }

  /**
   * logout.
   */
  public function logout() {
    if ($this->query->logout()) {
      $this->logged_in_user = NULL;
    }
  }

  /**
   * activate_link (clicked).
   * @throws DwapiException
   */
  public function activate_link() {

    if (!isset($this->request->redirect["enabled"])) {
      $this->request->redirect["enabled"] = true;
    }

    $this->query->id = $this->getIdFromHash($this->query->hash);


    $this->query->activate_link();
  }

  /**
   * reset_password (mail)
   * @throws DwapiException
   */
  public function reset_password() {
    $this->query->email = $this->request->getParameters("get", "email");

    if ($this->query->reset_password()) {
      $temp_token = new JwtToken($this->request->project);
      $token = $temp_token->create(0, 1);

      //$this->response->result = $this->query->getResult();
      $this->response->result["hash"] = $this->query->getResult("items")[0]["hash"];
      $this->response->result["temp_token"] = $token;

      if (!isset($this->request->mail["enabled"])) {
        $this->request->mail["enabled"] = true;
      }
    }
  }

  /**
   * reset_password_link (clicked)
   * @throws DwapiException
   */
  public function reset_password_link() {
    // override redirect "enabled" to true if not given in parameter
    if (!isset($this->request->redirect["enabled"])) {
      $this->request->redirect["enabled"] = true;
    }

    $token = $this->request->getParameters("get", "temp_token");
    $temp_token = new JwtToken($this->request->project, $token);
    if ($temp_token->validate_token()) {
      $this->query->id = $this->getIdFromHash($this->query->hash);

      $this->query->reset_password_link();
    }
    else {
      throw new DwapiException('Temp token invalid.', DwapiException::DW_VALID_TOKEN_REQUIRED);
    }
  }

  /**
   * confirm_password.
   * @throws DwapiException
   */
  public function confirm_password() {
    $token = $this->request->getParameters("get", "temp_token");
    $temp_token = new JwtToken($this->request->project, $token);
    if ($temp_token->validate_token()) {
      $this->query->id = $this->getIdFromHash($this->query->hash);

      $this->query->email = $this->request->getParameters("get", "email");
      $this->query->password = $this->request->getParameters("post", "new_password");

      if ($this->response->result = $this->query->confirm_password()) {
        $this->response->result = $this->query->getResult();
        $this->response->debug = $this->query->getDebug();
      }

    }
    else {
      throw new DwapiException('Temp token invalid.', DwapiException::DW_VALID_TOKEN_REQUIRED);
    }
  }

  /**
   * Register user, send activation mail.
   * @throws DwapiException
   */
  public function register() {
    $this->query->values = $this->request->getParameters("post");

    if ($this->query->register()) {
      $this->response->result = $this->query->getResult();
      $this->response->debug = $this->query->getDebug();

      if (!isset($this->request->mail["enabled"])) {
        $this->request->mail["enabled"] = true;
      }
    }
    else {
      throw new DwapiException('User with this email already exists.', DwapiException::DW_USER_EXISTS);
    }
  }

  /**
   * Validate token.
   * @return bool
   * @throws DwapiException
   */
  public function validate_token() {
    if ($this->current_token->validate_token()) {
      $this->response->result["token"] = $this->current_token->token;
      return true;
    }
    else {
      $this->response->http_response_code = 401;
      throw new DwapiException('Valid token is required.', DwapiException::DW_VALID_TOKEN_REQUIRED, null, 401);
    }
  }

  /**
   * Extend token.
   * @throws DwapiException
   */
  public function extend_token() {
    if ($this->current_token->validate_token()) {
      $this->current_token->extend_token();

      $this->response->result["token"] = $this->current_token->token;
      return;
    }
    throw new DwapiException('Valid token is required.', DwapiException::DW_VALID_TOKEN_REQUIRED);
  }

  /**
   * generate_access_token.
   */
  public function generate_access_token() {
    $id = $this->request->getParameters("post", "id");
    $restrict_host = $this->request->getParameters("post", "restrict_host");
    $restrict_ip  = $this->request->getParameters("post", "restrict_ip");

    $access_token = new AccessToken($this->request->project);
    $this->response->result["access_token"] = $access_token->create($id, $restrict_host, $restrict_ip);

    return;
  }
}