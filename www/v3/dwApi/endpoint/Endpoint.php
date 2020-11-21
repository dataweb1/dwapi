<?php
namespace dwApi\endpoint;
use dwApi\api\ErrorException;
use dwApi\api\Request;
use dwApi\api\Response;
use dwApi\dwApi;
use dwApi\query\QueryInterface;
use Hashids\Hashids;


/**
 * Class Endpoint
 * @package dwApi\endpoint
 */
abstract class Endpoint
{
  protected $request;
  protected $response;

  protected $current_token;

  /**
   * @var QueryInterface;
   */
  public $query;

  /**
   * Endpoint constructor.
   * @param dwApi $api
   */
  public function __construct(dwApi $api) {
    $this->request = Request::getInstance();
    $this->response = Response::getInstance();

    $this->current_token = $api->getCurrentToken();
  }


  /**
   * execute.
   * @param $action
   * @throws ErrorException
   */
  public function execute($action) {
    if (!method_exists(get_class($this), $action)) {
      throw new ErrorException('Action does not (yet) exists.', ErrorException::DW_INVALID_ACTION);
    }

    $this->$action();
  }


  /**
   * checkRequiredValues.
   * @param $values
   * @return bool
   * @throws ErrorException
   */
  public function checkRequiredValues($values)
  {
    foreach ($values as $field => $value) {
      $property = array("default" => "", "key" => "", "null" => "YES");
      if (isset($this->query->getEntityType()->getProperties()[$field])) {
        $property = $this->query->getEntityType()->getProperties()[$field];
      }
      //null = NO = required, null = YES = not required
      if ($property["default"] == "" && $property["key"] != "PRI") {
        if ($property["null"] == "NO") {
          if (
            (isset($values[$field]) && $values[$field] == "") ||
            !isset($values[$field])) {
            throw new ErrorException('"' . $field . '" value is required', ErrorException::DW_VALUE_REQUIRED);
          }
        }
      }
    }

    return true;
  }


  /**
   * getIdFromHash.
   * @param $hash
   * @return mixed
   */
  protected function getIdFromHash($hash) {
    $hashids = new Hashids('dwApi', 50);
    return $hashids->decode($this->query->hash)[0];
  }
}