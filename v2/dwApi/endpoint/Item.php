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
   * Do single_read on $query based on $property, $relation, $id and $hash parameter.
   * @throws ErrorException
   */
  public function single_read() {
    $this->query->property = $this->request->getParameters("get", "property");
    $this->query->relation = $this->request->getParameters("get", "relation");
    $this->query->id = $this->request->getParameters("get", "id");
    $this->query->hash = $this->request->getParameters("get", "hash");

    if (!is_null($this->query->hash)) {
      $hashids = new Hashids('dwApi', 50);
      $this->query->id = $hashids->decode($this->query->hash)[0];
    }

    if ($this->query->single_read()) {
      $this->response->result = $this->query->getResult();
      $this->response->result["token"] = $this->current_token->extend_token();

      $this->response->debug = $this->query->getDebug();
    }
    else {
      $this->response->http_response_code = 400;
      throw new ErrorException('ID or hash is required', ErrorException::DW_ID_REQUIRED);
    }
  }

  /**
   * Do read on $query based on $filter, $property, $sort, $paging and $relation parameter.
   * @throws ErrorException
   */
  public function read() {
    $this->query->filter = $this->request->getParameters("get", "filter");
    $this->query->property = $this->request->getParameters("get", "property");
    $this->query->sort = $this->request->getParameters("get", "sort");
    $this->query->paging = $this->request->getParameters("get", "paging");
    $this->query->relation = $this->request->getParameters("get", "relation");

    if ($this->isParameterSyntaxCorrect("filter", $this->query->filter, false)) {
      $this->sanitizeParameterArray($this->query->filter, true);
      if ($this->isParameterSyntaxCorrect("sort", $this->query->sort, false)) {
        $this->sanitizeParameterArray($this->query->sort, true);
      }
    }

    if ($this->query->read()) {
      $this->response->result = $this->query->getResult();
      $this->response->result["token"] = $this->current_token->extend_token();

      $this->response->debug = $this->query->getDebug();
    }
  }


  /**
   * Do update on $query based on $values and $filter parameter.
   * @throws ErrorException
   */
  public function update() {
    $this->query->values = $this->request->getParameters("put", "values");
    $this->query->filter = $this->request->getParameters("put", "filter");

    $this->request->processFiles($this->query->values);

    if ($this->isParameterSyntaxCorrect("value", $this->query->values) &&
      $this->isParameterSyntaxCorrect("filter", $this->query->filter)) {

      $this->sanitizeParameterArray($this->query->values, false);
      $this->sanitizeParameterArray($this->query->filter, true);

      if ($this->checkRequiredValues($this->query->values)) {
        if ($this->query->update()) {
          $this->response->result = $this->query->getResult();
          $this->response->result["token"] = $this->current_token->extend_token();

          $this->debug = $this->query->getDebug();
          return;
        }
      }
    }
  }

  /**
   * Do create on $query based on $values parameter.
   * @throws ErrorException
   */
  public function create()
  {
    $this->query->values = $this->request->getParameters("post", "values");
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
      }
    }

    $this->response->result = array("id" => NULL);
  }


  /**
   * Do single update on $query based on $values, $id and $hash parameter.
   * @throws ErrorException
   */
  public function single_update() {
    $this->query->values = $this->request->getParameters("put", "values");

    $this->request->processFiles($this->query->values);

    $this->query->id = $this->request->getParameters("get", "id");
    $this->query->hash = $this->request->getParameters("get", "hash");

    if ($this->query->hash != "") {
      $hashids = new Hashids('dwApi', 50);
      $this->query->id = $hashids->decode($this->query->hash)[0];
    }


    if ($this->query->single_update()) {
      $this->response->result = $this->query->getResult();
      $this->response->result["token"] = $this->current_token->extend_token();

      $this->response->debug = $this->query->getDebug();
      return;
    }
    else {
      $this->response->http_response_code = 400;
      throw new ErrorException('ID or hash is required', ErrorException::DW_ID_REQUIRED);
    }
  }


  /**
   * Do delete on $query basis on $filter parameter.
   * @throws ErrorException
   */
  public function delete() {
    $this->query->filter = $this->request->getParameters("delete", "filter");

    if ($this->isParameterSyntaxCorrect("filter", $this->query->filter)) {
      $this->sanitizeParameterArray($this->query->filter, true);
      if ($this->query->delete()) {

        $this->response->result = $this->query->getResult();
        $this->response->result["token"] = $this->current_token->extend_token();

        $this->response->debug = $this->query->getDebug();
      }
    }
  }


}