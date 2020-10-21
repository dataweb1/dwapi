<?php
namespace dwApi\endpoint;
use dwApi\api\Token;
use dwApi\dwApi;


/**
 * Class EndpointFactory
 * @package dwApi\endpoint
 */
class EndpointFactory {

  /**
   * Return a Endpoint instance according to the $endpoint parameter in the Request
   * @param $api
   * @param $endpoint
   * @return mixed
   */
  public static function create(dwApi $api, $endpoint) {
    $endpoint_class_name = "dwApi\\endpoint\\".ucfirst($endpoint);
    return new $endpoint_class_name($api);
  }
}