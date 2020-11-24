<?php
namespace dwApi\query\drp7;
use dwApi\api\ErrorException;
use dwApi\api\Helper;
use dwApi\api\Request;
use dwApi\query\QueryInterface;
use dwApi\storage\Drp7;
use Hashids\Hashids;
use dwApi\query\drp7\EntityType;


/**
 * Class ItemRepository
 * @package dwApi\query\mysql
 */
class Query implements QueryInterface {

  protected $storage;
  protected $request;

  private $entity_type;

  /* item parameters */
  public $values = NULL;
  public $filter = NULL;
  public $property = NULL;
  public $sort = NULL;
  public $hash = NULL;
  public $id = NULL;
  public $paging = NULL;
  public $relation = NULL;

  /* user parameters */
  public $email = NULL;
  public $password = NULL;

  /* response */
  protected $result;
  protected $debug;


  /**
   * Query constructor.
   * @param string $entity
   * @throws ErrorException
   */
  public function __construct($entity = "") {
    $this->storage = Drp7::load();

    $this->request = Request::getInstance();

    $this->storage->setPostValue("api_host", $_SERVER["HTTP_HOST"]);
    $this->storage->setPostValue("project", $this->request->project);
    $this->storage->setPostValue("entity", $entity);

    $this->entity_type = new EntityType();
    $this->entity_type->load($entity);
  }


  /**
   * Single read.
   * @return bool
   */
  public function single_read() {
    $this->storage->setPostValue("id", $this->id);
    $this->storage->setPostValue("relation", $this->relation);
    $this->storage->setPostValue("property", $this->property);

    $this->result = $this->storage->execute("Query", "single_read");

    return true;
  }


  /**
   * Read.
   * @return bool
   */
  public function read() {
    $this->storage->setPostValue("filter", $this->filter);
    $this->storage->setPostValue("sort", $this->sort);
    $this->storage->setPostValue("paging", $this->paging);
    $this->storage->setPostValue("relation", $this->relation);
    $this->storage->setPostValue("property", $this->property);

    $this->result = $this->storage->execute("Query", "read");

    return true;
  }


  /**
   * create.
   * @return bool|mixed
   * @throws ErrorException
   */
  public function create()
  {
    $this->storage->setPostValue("values", $this->values);
    $this->result = $this->storage->execute("Query", "create");

    return true;

  }


  /**
   * single_update.
   * @return bool|mixed
   * @throws ErrorException
   */
  public function single_update()
  {
    $this->filter = [["entity_id", "=", $this->id]];
    $this->update();

    return true;
  }

  /**
   * update.
   * @return bool|mixed
   * @throws ErrorException
   */
  public function update() {
    $this->storage->setPostValue("filter", $this->filter);
    $this->storage->setPostValue("values", $this->values);

    $this->result = $this->storage->execute("Query", "update");

    return true;
  }


  /**
   * single_delete.
   * @return bool|mixed
   * @throws ErrorException
   */
  public function single_delete()
  {
    $this->filter = [["entity_id", "=", $this->id]];
    $this->delete();

    return false;
  }


  /**
   * delete.
   * @return bool|mixed
   * @throws ErrorException
   */
  public function delete()
  {
    $this->storage->setPostValue("filter", $this->filter);

    $this->result = $this->storage->execute("Query", "delete");

    return true;

  }


  /**
   * Set result (by element)
   * @param $element
   * @param $value
   */
  public function setResult($element, $value) {
    $this->result[$element] = $value;
  }


  /**
   * Get result (by element).
   * @param null $element
   * @return mixed|null
   */
  public function getResult($element = NULL) {

    /* return element */
    if ($element == NULL) {
      return $this->result;
    }
    else {
      return $this->result[$element];
    }
  }


  /**
   * Get EntityType object.
   * @return \dwApi\query\drp7\EntityType
   */
  public function getEntityType() {
    return $this->entity_type;
  }


  /**
   * Get debug information.
   * @return mixed
   */
  public function getDebug() {
    return $this->debug;
  }


}