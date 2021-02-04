<?php
namespace dwApi\query\mysql;

use dwApi\api\DwapiException;
use dwApi\query\UserQueryInterface;
use dwApi\token\AccessToken;

class UserQuery extends Query implements UserQueryInterface {

  public $email = NULL;
  public $password = NULL;

  public $restrict_host = NULL;
  public $restrict_ip = NULL;

  /**
   * login.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function login() {
    if ($this->emailPasswordExists()) {
      if ($this->isActiveUser()) {
        $user = $this->getResult("items")[0];
        $user_id = $user[$this->getEntityType()->getPrimaryKey()];
        $this->values = ["force_login" => 0];
        $this->filter = [["user_id", "=", $user_id]];
        $this->update();

        unset($this->result["items"]);

        $this->result["id"] = $user_id;
        $this->result["item"] = $user;
        return true;
      }
    }
    else {
      throw new DwapiException('Active user with this e-mail/password not found.', DwapiException::DW_USER_NOT_FOUND);
    }
  }

  /**
   * logout.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function logout() {
    $this->values = ["force_login" => 1];
    $this->filter = [["user_id", "=", $this->logged_in_user["id"]]];
    $this->update();

    return true;
  }

  /**
   * login_by_id.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function login_by_id() {
    $this->single_read();
    return true;
  }

  /**
   * login_by_access_token.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function login_by_access_token() {
    $this->single_read();
    return true;
  }

  /**
   * confirm_password.
   * @return bool|mixed|null
   * @throws DwapiException
   */
  public function confirm_password() {
    if ($this->single_read()) {
      $array_to_check = array(
        "email" => $this->email,
        "password" => $this->password);

      if ($this->checkRequiredValues($array_to_check)) {
        $this->values = array("email" => $this->email, "password" => $this->password, "active" => 1, "force_login" => 1);
        if ($this->emailExists($this->values["email"])) {
          $this->filter = [["email", "=", $this->values["email"]]];

          //email in filter = not updating
          unset($this->values["email"]);
          $this->values["password"] = md5($this->values["password"]);
          $this->update();

          return true;
        }
      }

    }
    else {
      throw new DwapiException('User with e-mail not found.', DwapiException::DW_USER_NOT_FOUND);
    }

    return false;
  }

  /**
   * register.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function register() {
    $array_to_check = array(
      "email" => $this->values["email"],
      "password" => $this->values["password"]);

    if ($this->checkRequiredValues($array_to_check)) {

      if (!$this->emailExists($this->values["email"])) {
        $this->values["password"] = md5($this->values["password"]);
        $this->create();

        return true;
      }
    }

    return false;
  }

  /**
   * emailPasswordExists.
   * @return bool
   */
  private function emailPasswordExists() {
    $this->filter = [["email", "=", $this->email],["password", "=", md5($this->password)]];
    $this->read();
    if ($this->getResult("item_count") > 0){
      return true;
    }

    return false;
  }

  /**
   * isActiveUser.
   * @return bool|mixed
   */
  private function isActiveUser() {
    $force_login = $this->getResult("items")[0]["force_login"];
    $active = $this->getResult("items")[0]["active"];
    if ($force_login == 1 || $active == 0) {
      return false;
    }
    return true;
  }

  /**
   * emailExists.
   * @param $email
   * @return bool
   */
  private function emailExists($email) {
    $this->filter = [["email", "=", $email]];
    $this->read();
    if (count($this->getResult("items")) > 0) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * activate_link.
   * @return mixed|void
   * @throws DwapiException
   */
  public function activate_link()
  {
    if ($this->single_read()) {
      if ($this->getResult("item")["active"] == 0) {
        $this->values = array("active" => 1);
        if ($this->single_update()) {
          $this->result = $this->getResult();
          $this->debug = $this->getDebug();
        }
      }
      else {
        throw new DwapiException('User is activate already.', DwapiException::DW_USER_ACTIVATED);
      }
    } else {
      throw new DwapiException('User not found.', DwapiException::DW_USER_NOT_FOUND);
    }
  }

  /**
   * reset_password.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function reset_password(){
    $this->filter = [["email", "=", $this->email]];
    if ($this->read()) {

      if ($this->getResult("item_count") == 1) {
        $this->setResult("item", $this->getResult("items")[0]);
        $this->id = $this->query->getResult("items")[0]["user_id"];
        $this->values = array("active" => 0, "force_login" => 1);

        if ($this->single_update()) {
          return true;
        }
      }
      else {
        throw new DwapiException('User not found.', DwapiException::DW_USER_NOT_FOUND);
      }
    }
  }

  /**
   * reset_password_link (clicked)
   * @return bool|mixed
   * @throws DwapiException
   */
  public function reset_password_link() {
    if ($this->single_read()) {
      $this->values = array("active" => 0, "force_login" => 1);
      if ($this->single_update()) {
        return true;
      }
    }
    else {
      throw new DwapiException('User hash is invalid', DwapiException::DW_INVALID_HASH);
    }
  }


}