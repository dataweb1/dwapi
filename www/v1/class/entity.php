<?php
use ReallySimpleJWT\Token;

class Entity {
  private $api;
  private $db;
  private $request;

  public $key;
  public $properties;

  private function getProperties() {
    $sqlQuery = "SHOW COLUMNS FROM `" . $this->key ."`";

    $stmt = $this->db->prepare($sqlQuery);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $properties = [];
    foreach($items as $item) {
      $properties[$item["Field"]] = array(
        "type" => $item["Type"],
        "null" => $item["Null"],
        "key" => $item["Key"],
        "default" => $item["Default"],
        "extra" => $item["Extra"],
      );
    }

    $this->properties = $properties;

    /*
    if ($for_output) {
      $this->api->output = array(
        "data" => $properties
      );
    }
    */
  }

  private function isTokenRequired($entity_key, $action) {
    switch ($action) {
      case "login": case "register":
        return false;
        break;
      case "single_read": case "read":
        if ($entity_key == "user") {
          return true;
        }
        else {
          return false;
        }
        break;
      default:
        return true;
    }
  }



  /**
   * Entity constructor.
   * @param $api
   * @param string $entity_key
   * @throws Exception
   */
  public function __construct($api, $entity_key = "")
  {
    $this->api = $api;
    $this->db = $api->project->db->conn;
    $this->request = $api->request;

    $entity_to_construct = $entity_key;
    if ($entity_to_construct == "") {
      $entity_to_construct = $this->request->getParameters("get", "entity");
    }

    if ($entity_to_construct == "") {
      throw new Exception('Entity key is required', 400);
    }

    /*
    $token_required = $this->request->getParameters("get", "token_required");
    if ((string)$token_required == "null") {
      if ($this->isTokenRequired($entity_to_construct, $api->action) && $this->api->token->valid == false) {
        throw new Exception('Valid token is required', 401);
      }
    }
    else {
      if ((bool)$token_required == true) {
        if ($this->api->token->valid == false) {
          throw new Exception('Valid token is required', 401);
        }
      }
    }
  */
    $token_required = $this->request->getParameters("get", "token_required");
    if (is_null($token_required)) {
      if ($entity_to_construct == NULL) {
        $entity_to_construct = $this->request->getParameters("get", "endpoint");
      }
      $token_required = $this->isTokenRequired($entity_to_construct, $this->request->getParameters("get", "action"));
      if ($this->api->token->valid  == false) {
        //throw new Exception('Valid token is required', 401);
      }
    }

    if ($token_required == true) {
      if ($this->api->token->valid == false) {
        throw new Exception('Valid token is required', 401);
      }
    }

    if ($this->exists($entity_to_construct)) {

        $this->key = $entity_to_construct;
        $this->getProperties();
        /*
      }
      else {
        throw new Exception('Not authorized for this action on '.$entity_to_construct, 401);
      }
        */
    }
    else {
      throw new Exception('Entity "' . $entity_to_construct . '" not found', 404);
    }
  }

  /**
   * @param $entity
   * @return bool
   */
  public function exists($entity_key) {
    $sqlQuery = "SHOW TABLES LIKE '" . $entity_key . "'";
    $stmt = $this->db->prepare($sqlQuery);
    if ($stmt->execute()) {
      return $stmt->rowCount();
    }
    else {
      return false;
    }
  }

  /**
   * @return int|string
   */
  public function getPrimaryKey() {
    foreach ($this->properties as $field => $property) {
      if ($property["key"] == "PRI") {
        return $field;
      }
    }
  }


}