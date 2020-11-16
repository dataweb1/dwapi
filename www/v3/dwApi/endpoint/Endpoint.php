<?php
namespace dwApi\endpoint;
use dwApi\api\ErrorException;
use dwApi\api\Request;
use dwApi\api\Response;
use dwApi\dwApi;
use dwApi\query\QueryInterface;


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
   * Run.
   * @throws ErrorException
   */
  public function run() {

    $action = Request::getInstance()->action;
    if (!method_exists(get_class($this), $action)) {
      throw new ErrorException('Action does not (yet) exists.', ErrorException::DW_INVALID_ACTION);
    }

    $this->$action();
  }


  /**
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
   * @param $array
   * @param $multi_array_wanted
   * @return mixed
   */
  public function sanitizeParameterArray(&$array, $multi_array_wanted) {
    if (is_array($array) && $multi_array_wanted) {
      /** ["id", "=", "1"] instead of [["id", "=", "1"]] **/
      if (array_key_exists(0, $array) && !is_array($array[0])) {
        $a[0] = $array;
        $array = $a;
      }
      else {
        /** {"field": "id", "operator": "=", "value": "1"} instead of [{"field": "id", "operator": "=", "value": "1"}] **/
        if (!array_key_exists(0, $array)) {
          $a[0] = $array;
          $array = $a;
        }
      }
    }
  }


  /**
   * @param $verb
   * @param $parameter
   * @param bool $required
   * @return bool
   * @throws ErrorException
   */
  public function isParameterSyntaxCorrect($verb, $parameter, $required = true) {
    if ($required) {
      if (!$parameter) {
        throw new ErrorException(ucfirst($verb) . " is missing. At least one is needed.", ErrorException::DW_SYNTAX_ERROR);
      }
      else {
        if (!is_array($parameter)) {
          throw new ErrorException(ucfirst($verb) . " syntax not correct.", ErrorException::DW_SYNTAX_ERROR);
        }
      }
    }
    else {
      if ($parameter != "") {
        if (!is_array($parameter)) {
          throw new ErrorException(ucfirst($verb) . " syntax not correct.", ErrorException::DW_SYNTAX_ERROR);
        }
      }
    }

    return true;
  }
}