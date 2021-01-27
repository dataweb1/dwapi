<?php


namespace dwApi\query;

use dwApi\api\Request;
use dwApi\storage\Mysql;

class BaseQuery implements BaseQueryInterface
{
  protected $storage;
  protected $request;

  protected $logged_in_user;

  protected $entity_type = NULL;

  /* response */
  protected $result;
  protected $debug;


  /**
   * BaseQuery constructor.
   * @param null $logged_in_user
   */
  public function __construct($logged_in_user = NULL) {
    $this->request = Request::getInstance();
    $this->logged_in_user = $logged_in_user;
  }


  /**
   * setResult.
   * @param $element
   * @param $value
   */
  public function setResult($element, $value) {
    $this->result[$element] = $value;
  }

  /**
   * getResult.
   * @param null $element
   * @return mixed
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
   * Get debug information.
   * @return mixed
   */
  public function getDebug() {
    return $this->debug;
  }


  /**
   * Get EntityType object.
   * @return \dwApi\query\mysql\EntityType
   */
  public function getEntityType() {
    return $this->entity_type;
  }
}