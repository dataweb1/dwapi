<?php
namespace dwApi\api;
use dwApi\query\QueryFactory;
use ReallySimpleJWT\Token as JWTToken;


/**
 * Class Token
 * @package dwApi\api
 */
class Token
{

  const SECRET = 'sec!ReT423*&';
  const HOURS_VALID = 10;

  public $project;
  public $token = "";
  public $data = NULL;
  public $valid = false;


  /**
   * Token constructor.
   * @param $project
   * @param string $token
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
    $this->valid = false;
  }

  /**
   * Load token data.
   * @param $token
   */
  public function load($token) {
    if ($payload = JWTToken::getPayload($token, self::SECRET)) {
      if ($payload["iss"] == $this->project) {
        $this->valid = true;
        $this->data = array(
          "user_id" => $payload["user_id"],
          "valid_from" => $payload["iat"],
          "valid_to" => $payload["exp"],
          "iss" => $payload["iss"]
        );
        $this->token = $token;
      }
      else {
        $this->reset();
      }
    }
  }



  /**
   * Create new token.
   * @param $user_id
   * @param int $hours_valid
   * @return mixed
   */
  public function create($user_id, $hours_valid = NULL) {
    if ($hours_valid == NULL) {
      $hours_valid = self::HOURS_VALID;
    }
    $expiration = time() + (3600 * $hours_valid);
    $token = JWTToken::create($user_id, self::SECRET, $expiration, $this->project);
    $this->load($token);
    $this->valid = true;

    return $token;
  }

  /**
   * Validate current token.
   * @return bool
   */
  public function validate_token() {

    if ($this->token != "") {
      $valid = JWTToken::validate($this->token, self::SECRET);
      if ($valid && $this->data["user_id"] > 0) {
        $user_repository = QueryFactory::create("user");
        $user_repository->filter = [["user_id", "=", $this->data["user_id"]]];
        $user_repository->read();

        $force_login = $user_repository->getResult("items")[0]["force_login"];
        $active = $user_repository->getResult("items")[0]["active"];
        if ($force_login == 1 || $active == 0) {
          $this->reset();
        }
      }
    }
    else {
      $this->reset();
    }

    return $this->valid;
  }

  /**
   * Extend current token.
   * @return bool
   * @throws Exception
   */
  public function extend_token() {
    if ($this->validate_token()) {
      return $this->create($this->data["user_id"]);
    } else {
      return false;
    }
  }

}