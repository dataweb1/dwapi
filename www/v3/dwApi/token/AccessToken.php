<?php
namespace dwApi\token;

use dwApi\query\QueryFactory;
use Hashids\Hashids;
use ReallySimpleJWT\Token as SimpleJWTToken;

/**
 * Class AccessToken
 * @package dwApi\token
 */
class AccessToken {
  public $token = "";
  public $data = NULL;
  public $valid = false;
  public $project = "";
  public $token_user = NULL;

  /**
   * Token constructor.
   * @param $project
   * @param null $token
   * @throws DwapiException
   */
  public function __construct($project, $token = NULL) {
    $this->project = $project;

    if ($token != NULL) {
      $this->load($token);
    }

  }

  /**
   * Reset object properties.
   */
  public function reset() {
    $this->token = "";
    $this->data = NULL;
    $this->valid = FALSE;
    $this->token_user = NULL;
  }

  /**
   * load.
   * @param $token
   * @throws DwapiException
   */
  public function load($token) {

    $t = base64_decode($token);

    $hashids = new Hashids("dwApi", 50);
    $data = array (
      "user_id" => $hashids->decode($t[0]),
      "project" => base64_decode($t[1]),
      "restrict_host" => base64_decode($t[2]),
      "restrict_ip" => base64_decode($t[3]));

    if ($this->validate_token($data)) {
      if ($this->token_user == NULL) {
        if ($this->token_user = $this->loadUser($data["user_id"])) {
          $this->valid = true;
          $this->data = $data;
          $this->token = $token;
        } else {
          $this->reset();
        }
      } else {
        $this->reset();
      }
    }
  }


  /**
   * create.
   * @param $user_id
   * @param null $restrict_host
   * @param null $restrict_ip
   * @return string
   */
  public function create($user_id, $restrict_host = NULL, $restrict_ip = NULL) {
    $hashids = new Hashids("dwApi", 50);

    $data = array(
      $hashids->encode($user_id),
      base64_encode($this->project),
      base64_encode($restrict_host),
      base64_encode($restrict_ip));


    return base64_encode(implode("|", $data));
  }

  /**
   * validate_token.
   * @param null $data
   * @return bool
   */
  public function validate_token($data = NULL) {
    $to_check_data = $data;
    if ($to_check_data == NULL) {
      $to_check_data = $this->data;
    }

    $this->valid = true;

    if ($to_check_data["project"] != $this->project) {
      $this->valid = false;
    }

    if ($to_check_data["restrict_host"] != "") {
      if ($to_check_data["restrict_host"] != $_SERVER["REMOTE_HOST"]) {
      $this->valid = false;
      }
    }

    if ($to_check_data["restrict_ip"] != "") {
      if ($to_check_data["restrict_ip"] != $_SERVER["REMOTE_ADDR"]) {
        $this->valid = false;
      }
    }

    if (!$this->valid) {
      $this->reset();
    }

    return $this->valid;

  }

  /**
   * extend_token.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function extend_token() {
    return false;
  }

  /**
   * loadUser.
   * @param $user_id
   * @return bool|mixed
   * @throws \dwApi\api\DwapiException
   */
  private function loadUser($user_id) {
    $token_user = QueryFactory::create("user");
    $token_user->id = $user_id;

    if ($token_user->login_by_token()) {
      return $token_user->getResult("item");
    }
    else {
      return false;
    }
  }
}