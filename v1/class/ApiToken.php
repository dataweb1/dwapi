<?php
use ReallySimpleJWT\Token;

class ApiToken
{
  private $api;
  private $db;

  public $secret = 'sec!ReT423*&';
  public $token;
  public $data;
  public $valid = NULL;

  public function __construct($api)
  {
    $this->api = $api;
    $this->db = $api->project->db->conn;

    $token = $api->request->getBearerToken();

    //if ($token == "" && $this->isTokenRequired($this->api->action)) {
    //  throw new Exception('Valid token is required', 401);
    //}
    //else {
      if ($token != "") {
        $this->getData($token);
        $this->valid = $this->isValidToken($token);
        if ($this->valid) {
          $this->api->logged_in_user_id = $this->data["user_id"];
        } else {
          throw new Exception('Current token is not valid.', 401);
        }
      }
    //}
  }


  public function create($user_id) {
    $expiration = time() + (3600 * 10); // 10 uur
    $issuer = $this->api->project->key;

    $token = Token::create($user_id, $this->secret, $expiration, $issuer);

    $payload = Token::getPayload($token, $this->secret);
    $this->token = $token;
    $this->data = array(
      "user_id" => $payload["user_id"],
      "valid_from" => $payload["iat"],
      "valid_to" => $payload["exp"]
    );

    return $token;
  }

  /**
   * @param $token
   */
  public function getData($token) {
    $payload = Token::getPayload($token, $this->secret);
    if ($payload["iss"] == $this->api->project->key) {
      $this->token = $token;
      $this->data = array(
        "user_id" => $payload["user_id"],
        "valid_from" => $payload["iat"],
        "valid_to" => $payload["exp"]
      );
    }
  }

  public function isTokenRequired($action) {

    //return false;

    switch ($action) {
      case "login": case "single_read": case "read":
          return false;
          break;
      default:
        return true;
        break;
    }

  }

  /**
   * @param null $token
   * @return mixed
   * @throws Exception
   */
  public function isValidToken($token = NULL) {


    if ($token != NULL) {
      $token_to_validate = $token;
    }
    else {
      $token_to_validate = $this->api->token->token;
    }



    //print_r("token_to_validate: ".$token_to_validate);
    // only executed of valid token or no token

    // no token is not ok
    /*
    if ($token_to_validate == "") {
      throw new Exception('Valid token is required', 401);
    }
    */

    if ($token_to_validate != "") {
      $valid = Token::validate($token_to_validate, $this->secret);
      if ($valid) {
        $stmt = $this->db->prepare("SELECT force_login FROM user WHERE user_id = ?");
        $stmt->execute([$this->data["user_id"]]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $force_login = $user["force_login"];
        if ($force_login == 1) {
          $valid = false;
        }
      }
    }
    else {
      $valid = false;
    }

    return $valid;
  }

  /**
   * @throws Exception
   */
  public function extendToken() {
    // only executed of valid token in authorization header
    if ($this->token != "") {
      if ($this->isValidToken()) {
        $token = $this->api->token->create($this->api->token->data["user_id"]);
        return $token;
      } else {
        throw new Exception('Current token is not valid.', 401);
      }
    }
    else {
      return false;
    }

  }
}