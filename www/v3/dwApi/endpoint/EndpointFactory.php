<?php
namespace dwApi\endpoint;
use dwApi\api\ErrorException;
use dwApi\api\Request;
use dwApi\api\Token;
use dwApi\dwApi;
use dwApi\reference\Reference;


/**
 * Class EndpointFactory
 * @package dwApi\endpoint
 */
class EndpointFactory {

  /**
   * Return a Endpoint instance according to the $endpoint parameter in the Request
   * @param dwApi $api
   * @return Endpoint
   * @throws ErrorException
   */
  public static function create(dwApi $api) {
    $endpoint = Request::getInstance()->endpoint;
    $endpoint_class_name = "dwApi\\endpoint\\".ucfirst($endpoint);
    if (!class_exists($endpoint_class_name)) {
      throw new ErrorException('Endpoint "'.$endpoint_class_name.'" not valid', ErrorException::DW_INVALID_ENDPOINT);
    }

    return new $endpoint_class_name($api);
  }
}