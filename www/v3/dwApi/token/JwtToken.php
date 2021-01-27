<?php
namespace dwApi\token;
use dwApi\api\DwapiException;
use dwApi\query\QueryFactory;
use ReallySimpleJWT\Token as SimpleJWTToken;


/**
 * Class Token
 * @package dwApi\api
 */
class JwtToken
{
  const SECRET = 'sec!ReT423*&';
  const HOURS_VALID = 10;

  public $token_type = "jwt";
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
   * @param $token_type
   * @param $token
   * @throws DwapiException
   */
  public function load($token) {
    if ($payload = SimpleJWTToken::getPayload($token, self::SECRET)) {
      if ($payload["iss"] == $this->project) {
        if ($this->token_user == NULL) {
          if ($this->token_user = $this->loadUser($payload["user_id"])) {
            $this->valid = true;
            $this->data = array(
              "user_id" => $payload["user_id"],
              "valid_from" => $payload["iat"],
              "valid_to" => $payload["exp"],
              "iss" => $payload["iss"]
            );
            $this->token = $token;
          } else {
            $this->reset();
          }
        }
      } else {
        $this->reset();
      }
    }

  }

  /**
   * create.
   * @param $user_id
   * @param null $hours_valid
   * @return string
   * @throws DwapiException
   */
  public function create($user_id, $hours_valid = NULL) {
    if ($hours_valid == NULL) {
      $hours_valid = self::HOURS_VALID;
    }
    $expiration = time() + (3600 * $hours_valid);
    $token = SimpleJWTToken::create($user_id, self::SECRET, $expiration, $this->project);
    $this->load($token);
    $this->valid = true;

    return $token;
  }

  /**
   * validate_token.
   * @return bool
   */
  public function validate_token() {

    $token_user = NULL;
    if ($this->token != "") {
      $this->valid = SimpleJWTToken::validate($this->token, self::SECRET);
      if (!$this->valid) {
        $this->reset();
      }
    }
    return $this->valid;

  }

  /**
   * extend_token.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function extend_token() {
    if ($this->valid) {
      return $this->create($this->data["user_id"]);
    } else {
      return false;
    }
  }

  /**
   * loadUser.
   * @param $user_id
   * @return bool|mixed
   * @throws DwapiException
   */
  private function loadUser($user_id) {
    $token_user = QueryFactory::create("user");
    $token_user->id = $user_id;

    if ($token_user->login_by_id()) {
      return $token_user->getResult("item");
    }
    else {
      return false;
    }
  }
}