<?php
namespace dwApi\output;
use dwApi\api\Request;
use dwApi\output\Json;
use dwApi\output\Redirect;

/**
 * Class OutputFactory
 * @package dwApi\output
 */
class OutputFactory
{
  /**
   * Return a Output Repository instance based on $redirect parameter
   * @param $api
   * @return mixed
   */
  public static function create() {
    $request = Request::getInstance();
    if (!is_null($request->redirect) && $request->redirect["enabled"] == true) {
      return new Redirect();
    }
    else {
      return new Json();
    }
  }
}