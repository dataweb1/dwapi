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
   * @param $method
   * @throws ErrorException
   */
  public function execute($method) {
    if (!method_exists(get_class($this), $method)) {
      throw new ErrorException('Action does not (yet) exists.', ErrorException::DW_INVALID_ACTION);
    }

    $this->$method();
  }


  /**
   * checkRequiredFields.
   * @param $values
   * @return bool
   * @throws ErrorException
   */
  public function checkRequiredFields(&$values) {
    foreach($this->query->getEntityType()->getProperties() as $property_key => $property) {
      if (!array_key_exists($property_key, $values)) {
        $default = $this->query->getEntityType()->getPropertyDefaultValue($property_key);
        if ($default != "") {
          $values[$property_key] = $default;
        }
      }
      if ($this->query->getEntityType()->isPropertyRequired($property_key)) {
        if ((array_key_exists($property_key, $values) && $values[$property_key] == "")) {
          throw new ErrorException('"' . $property_key . '" value is required', ErrorException::DW_VALUE_REQUIRED);
        }
      }
    }
    return true;
  }


  /**
   * checkRequiredValues.
   * @param $values
   * @return bool
   * @throws ErrorException
   */
  public function checkRequiredValues($values)
  {
    foreach ($values as $property_key => $value) {
      if ($this->query->getEntityType()->isPropertyRequired($property_key)) {
        if ((isset($values[$property_key]) && $values[$property_key] == "") ||
          !isset($values[$property_key])) {
          throw new ErrorException('"' . $property_key . '" value is required', ErrorException::DW_VALUE_REQUIRED);
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