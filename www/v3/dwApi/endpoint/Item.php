<?php
namespace dwApi\endpoint;
use dwApi\api\ErrorException;

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

    $this->query->property = $this->request->getParameters("get", "property", true, true, false);
    $this->query->relation = $this->request->getParameters("get", "relation", true, true, false);
    $this->query->hash = $this->request->getParameters("path", "hash");
    if (!is_null($this->query->hash)) {
      $this->query->id = $this->getIdFromHash($this->query->hash);

      $this->query->single_read();
    }
    else {
      $this->query->filter = $this->request->getParameters("get", "filter", true, true, false);
      $this->query->paging = $this->request->getParameters("get", "paging", false, false, false);
      $this->query->sort = $this->request->getParameters("get", "sort", true, true, false);

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
    $this->query->values = $this->request->getParameters("put", NULL, true, false, true);

    $this->request->processFiles($this->query->values);

    if (!is_null($this->query->hash)) {
      $this->query->id = $this->getIdFromHash($this->query->hash);


      if ($this->checkRequiredValues($this->query->values)) {
        if (!$this->query->single_update()) {
          $this->response->http_response_code = 400;
          throw new ErrorException('ID or hash is required', ErrorException::DW_ID_REQUIRED);
        }
      }
    }
    else {
      $this->query->filter = $this->request->getParameters("get", "filter", true, true, true);

      if ($this->checkRequiredValues($this->query->values)) {
        $this->query->update();
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
    $this->query->values = $this->request->getParameters("post", NULL, true, false, true);
    $this->request->processFiles($this->query->values);

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


  /**
   * Delete item.
   * @throws ErrorException
   */
  public function delete() {
    $this->query->hash = $this->request->getParameters("path", "hash");
    if (!is_null($this->query->hash)) {
      $this->query->id = $this->getIdFromHash($this->query->hash);

      if (!$this->query->single_delete()) {
        $this->response->http_response_code = 400;
        throw new ErrorException('ID or hash is required', ErrorException::DW_ID_REQUIRED);
      }
    }
    else {
      $this->query->filter = $this->request->getParameters("delete", "filter", true, false, true);
      $this->query->delete();
    }

    $this->response->result = $this->query->getResult();
    $this->response->result["token"] = $this->current_token->extend_token();

    $this->response->debug = $this->query->getDebug();
  }
}