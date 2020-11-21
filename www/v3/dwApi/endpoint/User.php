<?php
namespace dwApi\endpoint;
use dwApi\api\ErrorException;
use dwApi\api\Request;
use dwApi\api\Token;
use dwApi\dwApi;
use dwApi\query\QueryFactory;


/**
 * Class User
 * @package dwApi\endpoint
 */
class User extends Endpoint {

  protected $logged_in_user;

  public function __construct(dwApi $api)
  {
    parent::__construct($api);

    $this->logged_in_user = $api->getLoggedInUser();
  }

  /**
   * Login user.
   * @throws ErrorException
   */
  public function login() {
    $this->query->email = $this->request->getParameters("post", "email");
    $this->query->password = $this->request->getParameters("post", "password");

    if ($this->checkRequiredValues(array("email" => $this->query->email, "password" => $this->query->password))) {
      if ($this->emailPasswordExists()) {
        if ($this->query->getResult("items")[0]["active"] == 1) {
          $this->query->values = ["force_login" => 0];
          $this->query->filter = [["user_id", "=", $this->query->getResult("items")[0][$this->query->entity_type->getPrimaryKey()]]];
          $this->query->update();

          $id = $this->query->getResult("items")[0][$this->query->getEntityType()->getPrimaryKey()];
          $this->current_token->create($id);
          $this->logged_in_user = QueryFactory::create("user");
          $this->logged_in_user->id = $id;
          $this->logged_in_user->single_read();

          $this->response->result = $this->logged_in_user->getResult();
          $this->response->result["token"] = $this->current_token->token;
        }
        else {
          $this->response->http_response_code = 400;
          throw new ErrorException('Active user with this e-mail/password not found.',  ErrorException::DW_USER_NOT_FOUND);
        }
      }
      else {
        $this->response->http_response_code = 400;
        throw new ErrorException('Active user with this e-mail/password not found.',  ErrorException::DW_USER_NOT_FOUND);
      }
      return;
    }

  }


  /**
   * Logout user.
   */
  public function logout() {
    $this->query->values = ["force_login" => 1];
    $this->query->filter = [["user_id", "=", $this->logged_in_user->id]];
    $this->query->update();

    $this->logged_in_user = NULL;
  }


  /**
   * Activate link clicked.
   * @throws ErrorException
   */
  public function activate_link() {

    if (!isset($this->request->redirect["enabled"])) {
      $this->request->redirect["enabled"] = true;
    }

    $this->query->id = $this->getIdFromHash($this->query->hash);
    if (intval($this->query->id) > 0) {
      if ($this->query->single_read()) {
        if ($this->query->getResult("item")["active"] == 0) {
          $this->query->values = array("active" => 1);
          if ($this->query->single_update()) {
            $this->response->result = $this->query->getResult();
            $this->response->debug = $this->query->getDebug();
          }
        }
        else {
          $this->response->http_response_code = 400;
          throw new ErrorException('User is activate already.', ErrorException::DW_USER_ACTIVATED);
        }
      } else {
        $this->response->http_response_code = 400;
        throw new ErrorException('User not found.', ErrorException::DW_USER_NOT_FOUND);
      }
    }
    else {
      $this->response->http_response_code = 400;
      throw new ErrorException('User does not exist.', ErrorException::DW_USER_NOT_FOUND);
    }
  }


  /**
   * Send reset password mail.
   * @return bool
   * @throws ErrorException
   */
  public function reset_password() {
    $email = $this->request->getParameters("get", "email");
    $this->query->filter = [["email", "=", $email]];
    if ($this->query->read()) {

      if ($this->query->getResult("item_count") == 1) {
        $this->query->setResult("item", $this->query->getResult("items")[0]);
        $this->query->id = $this->query->getResult("items")[0]["user_id"];
        $this->query->values = array("active" => 0, "force_login" => 1);

        if ($this->query->single_update()) {

          $temp_token = new Token($this->request->project);
          $token = $temp_token->create(0, 1);

          //$this->response->result = $this->query->getResult();
          $this->response->result["hash"] = $this->query->getResult("items")[0]["hash"];
          $this->response->result["temp_token"] = $token;

          if (!isset($this->request->mail["enabled"])) {
            $this->request->mail["enabled"] = true;
          }

          return true;
        }
      }
      else {
        throw new ErrorException('User not found.', ErrorException::DW_USER_NOT_FOUND);
      }
    }
  }


  /**
   * Reset password link clicked.
   * @return bool
   * @throws ErrorException
   */
  public function reset_password_link() {
    // override redirect "enabled" to true if not given in parameter
    if (!isset($this->request->redirect["enabled"])) {
      $this->request->redirect["enabled"] = true;
    }

    $token = $this->request->getParameters("get", "temp_token");
    $temp_token = new Token($this->request->project, $token);
    if ($temp_token->validate_token()) {
      $this->query->id = $this->getIdFromHash($this->query->hash);
      if ($this->query->single_read()) {
        $this->query->values = array("active" => 0, "force_login" => 1);
        if ($this->query->single_update()) {
          return true;
        }
      }
      else {
        throw new ErrorException('User hash is invalid', ErrorException::DW_INVALID_HASH);
      }
    }
    else {
      throw new ErrorException('Temp token invalid.', ErrorException::DW_VALID_TOKEN_REQUIRED);
    }
  }


  /**
   * Confirm new password.
   * @return bool
   * @throws ErrorException
   */
  public function confirm_password() {
    $token = $this->request->getParameters("get", "temp_token");
    $temp_token = new Token($this->request->project, $token);
    if ($temp_token->validate_token()) {
      $this->query->id = $this->getIdFromHash($this->query->hash);
      if ($this->query->single_read()) {
        $email = $this->request->getParameters("get", "email");
        $new_password = $this->request->getParameters("post", "new_password");
        $array_to_check = array(
          "email" => $email,
          "password" => $new_password);

        if ($this->checkRequiredValues($array_to_check)) {
          $this->query->values = array("email" => $email, "password" => $new_password, "active" => 1, "force_login" => 1);
          if ($this->emailExists($this->query->values["email"])) {
            $this->query->filter = [["email", "=", $this->query->values["email"]]];

            //email in filter = not updating
            unset($this->query->values["email"]);
            $this->query->values["password"] = md5($this->query->values["password"]);
            $this->query->update();

            $this->response->result = $this->query->getResult();
            return true;
          }
          else {
            throw new ErrorException('User with e-mail not found.', ErrorException::DW_USER_NOT_FOUND);
          }
        }
      }
      else {
        throw new ErrorException('User hash is invalid.', ErrorException::DW_INVALID_HASH);
      }
    }
    else {
      throw new ErrorException('Temp token invalid.', ErrorException::DW_VALID_TOKEN_REQUIRED);
    }
  }


  /**
   * Register user, send activation mail.
   * @throws ErrorException
   */
  public function register() {
    $this->query->values = $this->request->getParameters("post");

    $array_to_check = array(
      "email" => $this->query->values["email"],
      "password" => $this->query->values["password"]);

    if ($this->checkRequiredValues($array_to_check)) {

      if (!$this->emailExists($this->query->values["email"])) {
        $this->query->values["password"] = md5($this->query->values["password"]);
        $this->query->create();

        $this->response->result = $this->query->getResult();
        $this->response->debug = $this->query->getDebug();

        if (!isset($this->request->mail["enabled"])) {
          $this->request->mail["enabled"] = true;
        }
        return;
      } else {
        throw new ErrorException('User with this email already exists.', ErrorException::DW_USER_EXISTS);
      }
    }
  }

  /**
   * Validate token.
   * @return bool
   * @throws ErrorException
   */
  public function validate_token() {
    if ($this->current_token->validate_token()) {
      $this->response->result["token"] = $this->current_token->token;
      return true;
    }
    else {
      $this->response->http_response_code = 401;
      throw new ErrorException('Valid token is required.', ErrorException::DW_VALID_TOKEN_REQUIRED);
    }
  }

  /**
   * Extend token.
   * @throws ErrorException
   */
  public function extend_token() {
    if ($this->current_token->validate_token()) {
      $this->current_token->extend_token();

      $this->response->result["token"] = $this->current_token->token;
      return;
    }
    $this->response->http_response_code = 401;
    throw new ErrorException('Valid token is required.', ErrorException::DW_VALID_TOKEN_REQUIRED);
  }



  /**
   * @return bool
   */
  private function emailPasswordExists() {
    $this->query->filter = [["email", "=", $this->query->email],["password", "=", md5($this->query->password)]];
    $this->query->read();
    if ($this->query->getResult("item_count") > 0){
      return true;
    }

    return false;
  }



  /**
   * @param $email
   * @return bool
   */
  private function emailExists($email) {
    $this->query->filter = [["email", "=", $email]];
    $this->query->read();
    if (count($this->query->getResult("items")) > 0) {
      return true;
    }
    else {
      return false;
    }
  }

}