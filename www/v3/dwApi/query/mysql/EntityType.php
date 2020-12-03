<?php
namespace dwApi\query\mysql;
use dwApi\api\DwapiException;
use dwApi\query\EntityTypeInterface;
use dwApi\storage\Mysql;


/**
 * Class EntityType
 * @package dwApi\query\mysql
 */
class EntityType implements EntityTypeInterface {
  public $entity;
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
   * load.
   * @param $entity
   * @throws DwapiException
   */
  public function load($entity) {

    if ($entity == "" || $entity == "item") {
      throw new DwapiException('Entity parameter is required', DwapiException::DW_ENTITY_REQUIRED);
    }

    $this->entity = $entity;

    if (!$this->getProperties()) {
      throw new DwapiException('Entity "' . $entity . '" not found', DwapiException::DW_ENTITY_NOT_FOUND);
    }

  }



  /**
   * getProperties.
   * @return array|null
   */
  public function getProperties() {
    if ($this->properties == NULL) {
      $sqlQuery = "SHOW COLUMNS FROM `" . $this->entity . "`";

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
   * @return int|string
   */
  public function getPrimaryKey() {
    foreach ($this->getProperties() as $field => $property) {
      if ($property["key"] == "PRI") {
        return $field;
      }
    }
  }


  /**
   * @param $property
   * @return bool
   */
  public function isPropertyRequired($property) {
    //null = NO = required, null = YES = not required
    if ($this->properties[$property]["default"] == "" && $this->properties[$property]["key"] != "PRI") {
      if ($this->properties[$property]["null"] == "NO") {
        return true;
      }
    }

    return false;
  }


  /**
   * defaultValue
   * @param $property
   * @return mixed
   */
  public function getPropertyDefaultValue($property) {
    return $this->properties[$property]["default"];
  }
}