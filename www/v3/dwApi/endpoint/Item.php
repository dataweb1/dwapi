<?php
namespace dwApi\endpoint;
use dwApi\api\ErrorException;
use Hashids\Hashids;

/**
 * Class Item
 * @package dwApi\endpoint
 */
class Item extends Endpoint {

  /**
   * Read item.
   * @throws ErrorException
   */
  public function get() {

    $this->query->hash = $this->request->getParameters("path", "hash");
    if (!is_null($this->query->hash)) {
      $hashids = new Hashids('dwApi', 50);
      $this->query->id = $hashids->decode($this->query->hash)[0];

      $this->query->property = $this->request->getParameters("get", "property");
      $this->query->relation = $this->request->getParameters("get", "relation");

      if (!$this->query->single_read()) {
        $this->response->http_response_code = 400;
        throw new ErrorException('Item not found', ErrorException::DW_ID_REQUIRED);
      }
    }
    else {
      $this->query->filter = $this->request->getParameters("get", "filter");
      $this->query->paging = $this->request->getParameters("get", "paging");
      $this->query->sort = $this->request->getParameters("get", "sort");

      if ($this->isParameterSyntaxCorrect("filter", $this->query->filter, false)) {
        $this->sanitizeParameterArray($this->query->filter, true);
        if ($this->isParameterSyntaxCorrect("sort", $this->query->sort, false)) {
          $this->sanitizeParameterArray($this->query->sort, true);
        }
      }

      $this->query->property = $this->request->getParameters("get", "property");
      $this->query->relation = $this->request->getParameters("get", "relation");

      $this->query->read();
    }

    $this->response->result = $this->query->getResult();
    $this->response->result["token"] = $this->current_token->extend_token();

    $this->response->debug = $this->query->getDebug();
  }


  /**
   * Put item.
   * @throws ErrorException
   */
  public function put() {

    $this->query->hash = $this->request->getParameters("path", "hash");
    $this->query->values = $this->request->getParameters("put");

    if (!is_null($this->query->hash)) {
      $hashids = new Hashids('dwApi', 50);
      $this->query->id = $hashids->decode($this->query->hash)[0];
      $this->request->processFiles($this->query->values);

      if (!$this->query->single_update()) {
        $this->response->http_response_code = 400;
        throw new ErrorException('ID or hash is required', ErrorException::DW_ID_REQUIRED);
      }

    }
    else {
      $this->query->values = $this->request->getParameters("put");
      $this->query->filter = $this->request->getParameters("get", "filter");

      $this->request->processFiles($this->query->values);

      if ($this->isParameterSyntaxCorrect("value", $this->query->values) &&
        $this->isParameterSyntaxCorrect("filter", $this->query->filter)) {

        $this->sanitizeParameterArray($this->query->values, false);
        $this->sanitizeParameterArray($this->query->filter, true);

        if ($this->checkRequiredValues($this->query->values)) {
          $this->query->update();
        }
      }
    }

    $this->response->result = $this->query->getResult();
    $this->response->result["token"] = $this->current_token->extend_token();

    $this->response->debug = $this->query->getDebug();
  }


  /**
   * Post item.
   * @throws ErrorException
   */
  public function post()
  {
    $this->query->values = $this->request->getParameters("post");
    $this->request->processFiles($this->query->values);

    if ($this->isParameterSyntaxCorrect("value", $this->query->values)) {
      $this->sanitizeParameterArray($this->query->values, false);
      if ($this->checkRequiredValues($this->query->values)) {
        if ($this->query->create()) {
          $this->response->http_response_code = 201;
          $this->response->result = $this->query->getResult();
          $this->response->result["token"] = $this->current_token->extend_token();

          $this->response->debug = $this->query->getDebug();
          return;
        }
        else {
          $this->response->result = array("id" => NULL);
        }
      }
    }


  }


  /**
   * Delete item.
   * @throws ErrorException
   */
  public function delete() {
    $this->query->hash = $this->request->getParameters("path", "hash");
    if (!is_null($this->query->hash)) {
      $hashids = new Hashids('dwApi', 50);
      $this->query->id = $hashids->decode($this->query->hash)[0];

      if (!$this->query->single_delete()) {
        $this->response->http_response_code = 400;
        throw new ErrorException('ID or hash is required', ErrorException::DW_ID_REQUIRED);
      }
    }
    else {
      $this->query->filter = $this->request->getParameters("delete", "filter");

      if ($this->isParameterSyntaxCorrect("filter", $this->query->filter)) {
        $this->sanitizeParameterArray($this->query->filter, true);
        $this->query->delete();
      }
    }

    $this->response->result = $this->query->getResult();
    $this->response->result["token"] = $this->current_token->extend_token();

    $this->response->debug = $this->query->getDebug();
  }
}