<?php
namespace dwApi\query;

use dwApi\endpoint\Item;
use dwApi\query\mysql\ItemRepository;
use dwApi\query\mysql\UserRepository;

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
   * @return InterfaceItemRepository|InterfaceUserRepository
   */
  public static function create($endpoint, $entity_type = "") {
    $query_class_name = "dwApi\\query\\mysql\\".ucfirst($endpoint)."Repository";
    if ($entity_type == "") {
      $entity_type = $endpoint;
    }
    return new $query_class_name($entity_type);
  }
}