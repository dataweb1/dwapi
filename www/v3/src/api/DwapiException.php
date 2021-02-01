<?php
namespace dwApi\api;
use Throwable;

/**
 * Class DwapiException
 * @package dwApi\api
 */
class DwapiException extends \Exception {

  private $response_code = NULL;

  const DW_EXTERNAL_ERROR = 9000;
  const DW_VALUE_REQUIRED = 9001;
  const DW_INVALID_HASH = 9002;
  const DW_PROJECT_REQUIRED = 9003;
  const DW_UPLOAD_ERROR = 9004;
  const DW_PROJECT_NOT_FOUND = 9005;
  const DW_ID_REQUIRED = 9006;
  const DW_ENTITY_NOT_FOUND =  9007;
  const DW_USER_NOT_FOUND = 9008;
  const DW_USER_EXISTS = 9009;
  const DW_USER_ACTIVATED = 9010;
  const DW_SYNTAX_ERROR = 9011;
  const DW_ENTITY_REQUIRED = 9012;
  const DW_VALID_TOKEN_REQUIRED = 9013;
  const DW_MAIL_ERROR = 9014;
  const DW_INVALID_ACTION = 9015;
  const DW_PROJECT_TYPE_UNKNOWN = 9016;
  const DW_INVALID_ENDPOINT = 9017;
  const DW_ENDPOINT_REQUIRED = 9018;
  const DW_INVALID_METHOD = 9019;
  const DW_INVALID_LINK = 9020;
  const DW_INVALID_PATH = 9021;
  const DW_NOT_IMPLEMENTED = 9022;
  const DW_TOKEN_TYPE_UNKNOWN = 9023;

  public function __construct($message = "", $code = 0, Throwable $previous = null, $response_code = 400)
  {
    $this->response_code = $response_code;
    parent::__construct($message, $code, $previous);
  }


  public function getResponseCode() {
    return $this->response_code;
  }

}