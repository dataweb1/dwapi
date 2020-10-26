<?php
namespace dwApi\query;

/**
 * Class QueryFactory
 * @package dwApi\query
 */
class QueryFactory {
  /**
   * A Query Repository instance is returned according to the $endpoint parameter in the Request
   *
   * @param $endpoint
   * @param $entity_type
   * @return mixed
   */
  public static function create($endpoint, $entity_type = "") {

    $query_class_name = "dwApi\\query\\mysql\\".ucfirst($endpoint)."Repository";
    return new $query_class_name($endpoint, $entity_type);
  }
}