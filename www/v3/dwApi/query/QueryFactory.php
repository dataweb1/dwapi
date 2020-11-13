<?php
namespace dwApi\query;
use dwApi\api\Request;

/**
 * Class QueryFactory
 * @package dwApi\query
 */
class QueryFactory {
  /**
   * A Query instance is returned.
   *
   * @param $entity_type
   * @return QueryInterface
   */
  public static function create($entity_type = "") {

    if ($entity_type == "") {
      $entity_type = Request::getInstance()->endpoint;
    }

    $query_class_name = "dwApi\\query\\mysql\\Query";
    return new $query_class_name($entity_type);
  }
}