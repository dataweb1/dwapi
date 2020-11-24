<?php
namespace dwApi\query\drp7;
use dwApi\api\ErrorException;
use dwApi\query\EntityTypeInterface;
use dwApi\storage\Drp7;


/**
 * Class EntityType
 * @package dwApi\query\drp7
 */
class EntityType implements EntityTypeInterface {
  public $entity;
  public $type;
  public $bundle;
  private $properties = NULL;
  private $storage;


  /**
   * Entity constructor.
   */
  public function __construct()
  {
    $this->storage = Drp7::load();
  }


  /**
   * load.
   * @param $entity
   * @throws ErrorException
   */
  public function load($entity) {

    if ($entity == "") {
      throw new ErrorException('Entity key is required', ErrorException::DW_ENTITY_REQUIRED);
    }

    $this->entity = $entity;
    $e = explode("-", $entity);
    $this->type = $e[0];
    $this->bundle = $e[1];

    if (!$this->getProperties()) {
      throw new ErrorException('Entity "' . $entity . '" not found', ErrorException::DW_ENTITY_NOT_FOUND);
    }

  }


  /**
   * getProperties.
   * @return bool|mixed|null
   * @throws ErrorException
   */
  public function getProperties() {
    if ($this->properties == NULL) {
      $this->storage->setPostValue("entity", $this->entity);
      $this->properties = $this->storage->execute("EntityType", "getProperties");
    }

    return $this->properties;
  }


  /**
   * getPrimaryKey.
   * @return int|string
   */
  public function getPrimaryKey() {
    /*
    foreach ($this->getProperties() as $field => $property) {
      if ($property["key"] == "PRI") {
        return $field;
      }
    }
    */
  }


  /**
   * isPropertyRequired.
   * @param $property
   * @return bool
   */
  public function isPropertyRequired($property) {
    if ($this->properties[$property]["required"] == 1) {
      return true;
    }
    return false;
  }


  /**
   * getPropertyTargetType.
   * @param $property
   * @return mixed
   */
  public function getPropertyTargetType($property) {
    return array_key_first($this->properties[$property]["field_info"]["columns"]);
  }


  /**
   * getPropertyDefaultValue.
   * @param $property
   * @return mixed
   */
  public function getPropertyDefaultValue($property) {
    return $this->properties[$property]["default_value"][0][$this->getPropertyTargetType($property)];
  }
}