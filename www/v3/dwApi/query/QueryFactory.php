<?php
namespace dwApi\query;
use dwApi\api\ErrorException;
use dwApi\api\Project;
use dwApi\api\Request;

/**
 * Class QueryFactory
 * @package dwApi\query
 */
class QueryFactory {
  /**
   * create.
   * @param string $entity_type
   * @return mixed
   * @throws ErrorException
   */
  public static function create($entity_type = "") {

    if ($entity_type == "") {
      $entity_type = Request::getInstance()->endpoint;
    }

    $query_class_name = "dwApi\\query\\".Project::getInstance()->type."\\Query";
    if (!class_exists($query_class_name)) {
      throw new ErrorException("Project type '".Project::getInstance()->type."' unknown.", ErrorException::DW_PROJECT_TYPE_UNKNOWN);
    }
    else {
      return new $query_class_name($entity_type);
    }
  }
}