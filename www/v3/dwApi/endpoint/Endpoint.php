<?php
namespace dwApi\endpoint;
use dwApi\api\DwapiException;
use dwApi\api\Request;
use dwApi\api\Response;
use dwApi\api\JwtToken;
use dwApi\dwApi;
use dwApi\query\QueryInterface;
use dwApi\query\UserQueryInterface;
use Hashids\Hashids;


/**
 * Class Endpoint
 * @package dwApi\endpoint
 */
abstract class Endpoint
{
  protected $request;
  protected $response;

  /**
   * @var JwtToken
   */
  protected $current_token;
  protected $logged_in_user;

  /**
   * @var QueryInterface|UserQueryInterface;
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
    $this->logged_in_user = $api->getLoggedInUser();
  }

  /**
   * execute.
   * @param $method
   * @throws DwapiException
   */
  public function execute($method) {
    if (!method_exists(get_class($this), $method)) {
      throw new DwapiException('Method does not (yet) exist.', DwapiException::DW_INVALID_ACTION);
    }

    $this->$method();
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