<?php
namespace dwApi\query\mysql;
use dwApi\api\ErrorException;
use dwApi\storage\Mysql;


/**
 * Class EntityType
 * @package dwApi\query\mysql
 */
class EntityType {
  public $key;
  private $properties = NULL;
  private $storage;

  /**
   * Entity constructor.
   */
  public function __construct()
  {
    $this->storage = Mysql::load();
  }

  /**
   * @param $entity_type
   * @throws ErrorException
   */
  public function load($entity_type) {

    if ($entity_type == "") {
      throw new ErrorException('Entity key is required', ErrorException::DW_ENTITY_REQUIRED);
    }

    if ($this->entityTypeExists($entity_type)) {

      $this->key = $entity_type;

    }
    else {
      throw new ErrorException('Entity "' . $entity_type . '" not found', ErrorException::DW_ENTITY_NOT_FOUND);
    }

  }

  /**
   *
   */
  public function getProperties() {
    if ($this->properties == NULL) {
      $sqlQuery = "SHOW COLUMNS FROM `" . $this->key . "`";

      $stmt = $this->storage->prepare($sqlQuery);
      $stmt->execute();

      $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      $properties = [];
      foreach ($items as $item) {
        $properties[$item["Field"]] = array(
          "type" => $item["Type"],
          "null" => $item["Null"],
          "key" => $item["Key"],
          "default" => $item["Default"],
          "extra" => $item["Extra"],
        );
      }
      $this->properties = $properties;
    }
    return $this->properties;
  }


  /**
   * @param $entity_type
   * @return bool
   */
  public function entityTypeExists($entity_type) {
    $sqlQuery = "SHOW TABLES LIKE '" . $entity_type . "'";
    $stmt = $this->storage->prepare($sqlQuery);
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
    foreach ($this->getProperties() as $field => $property) {
      if ($property["key"] == "PRI") {
        return $field;
      }
    }
  }


}