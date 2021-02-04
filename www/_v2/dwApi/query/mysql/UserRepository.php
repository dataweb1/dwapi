<?php
namespace dwApi\query\mysql;


/**
 * Class UserRepository
 * @package dwApi\query\mysql
 */
class UserRepository extends ItemRepository
{
  /* parameters */
  public $password;
  public $email;


  /**
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
   * @return bool
   */
  public function login() {
    if ($this->emailPasswordExists()) {
      if ($this->getResult("items")[0]["active"] == 1) {
        $this->values = ["force_login" => 0];
        $this->filter = [["user_id", "=", $this->getResult("items")[0][$this->entity_type->getPrimaryKey()]]];
        $this->update();
        return true;
      }
    }
  }


  /**
   * @return bool
   */
  public function logout($user_id) {
    $this->values = ["force_login" => 1];
    $this->filter = [["user_id", "=", $user_id]];
    $this->update();

    return true;
  }


  /**
   * @return bool
   */
  public function emailPasswordExists() {
    $this->filter = [["email", "=", $this->email],["password", "=", md5($this->password)]];
    $this->read();
    if ($this->getResult("item_count") > 0){
      /*
      $id = $this->getResult("items")[0][$this->entity_type->getPrimaryKey()];
      $this->result["id"] = $id;
      $this->result["active"] = $this->getResult("items")[0]["active"];
      */
      return true;
    }

    return false;
  }


  /**
   * @return bool
   */
  public function register() {
    if (!$this->emailExists($this->values["email"])) {
      $this->values["password"] = md5($this->values["password"]);
      $this->create();
      return true;
    }
    return false;
  }


  /**
   * @return bool
   */
  public function reset_password() {
    if ($this->emailExists($this->values["email"])) {
      $this->filter = [["email", "=", $this->values["email"]]];

      //email in filter = not updating
      unset($this->values["email"]);
      $this->values["password"] = md5($this->values["password"]);
      $this->update();
      return true;
    }
    return false;
  }

}