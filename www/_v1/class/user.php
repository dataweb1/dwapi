<?php
/**
 * Class User
 */
class User extends Item
{
  public $password;
  public $email;
  //public $token;

  public function __construct($api)
  {
    parent::__construct($api, "user");
  }

  /**
   * @param $values
   * @return bool
   * @throws Exception
   */
  private function checkRequiredValues($values) {
    foreach($values as $key => $value) {
      if ($value == "") {
        throw new Exception(ucfirst($key)." is required.", 400);
      }
    }

    return true;
  }

  /**
   * @param $email
   * @return bool
   */
  private function exists($email) {

    $select = $this->db->prepare('SELECT email FROM user WHERE email = ?');
    $select->execute([$email]);
    if ($select->rowCount() > 0) {
      return true;
    }

    return false;
  }


  /**
   *
   */
  public function validateToken() {

  }

  /**
   * @return mixed
   */
  public function extendToken() {
    return $this->api->token->extendToken();
  }


  /**
   * @return bool
   */
  public function logout() {
    $stmt = $this->db->prepare("UPDATE `user` SET force_login = 1 WHERE user_id = ?");
    $stmt->execute([$this->api->logged_in_user_id]);

    $this->api->logged_in_user_id = NULL;

    return true;
  }


  /**
   * @return bool
   * @throws Exception
   */
  public function login() {
    if ($this->checkRequiredValues(array("email" => $this->email, "password" => $this->password))) {
      $stmt = $this->db->prepare("SELECT * FROM user WHERE email = ? AND password = ?");
      $stmt->execute([$this->email, md5($this->password)]);
      if ($stmt->rowCount() > 0) {
        //$user = $stmt->fetch(PDO::FETCH_ASSOC);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);


        //$user_id = $user[$this->entity->getPrimaryKey()];
        $user_id = $item[$this->entity->getPrimaryKey()];
        $this->api->token->create($user_id);

        $stmt = $this->db->prepare("UPDATE `user` SET force_login = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $this->api->logged_in_user_id = $user_id;

        return $item;
      } else {
        return false;
      }
    }
  }


  /**
   * @return bool
   * @throws Exception
   */
  public function register() {
    if ($this->checkRequiredValues(array("email" => $this->email, "password" => $this->password))) {
      $this->password = md5($this->password);
      if (!$this->exists($this->email)) {
        $this->values["password"] = $this->password;
        $user_id = $this->create();
        if ($user_id) {
          return $user_id;
        }
      }
      else {
        return false;
      }
    }
  }

}