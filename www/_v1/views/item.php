<?php
$item = new Item($this);

switch ($this->action) {
  case "single_read":
    $item->property = $this->request->getParameters("get", "property");
    $item->id = $this->request->getParameters("get", "id");
    $item->relation = $this->request->getParameters("get", "relation");
    $result = $item->singleRead();
    $this->output["status"] = array(
      "success" => true,
      "token" => $this->token->extendToken()
    );
    $this->output["data"] = $result;

    break;

  case "read":
    $item->filter = $this->request->getParameters("get", "filter");
    $item->property = $this->request->getParameters("get", "property");
    $item->sort = $this->request->getParameters("get", "sort");
    $item->paging = $this->request->getParameters("get", "paging");
    $item->relation = $this->request->getParameters("get", "relation");
    $result = $item->read();
    $this->output["status"] = array(
      "success" => true,
      "token" => $this->token->extendToken()
    );
    $this->output["data"] = $result;

    break;

  case "update":
    $item->values = $this->request->getParameters("put", "values");
    $item->filter = $this->request->getParameters("put", "filter");

    $affected_items = $item->update();
    $this->output["status"] = array(
      "success" => true,
      "affected_items" => $affected_items,
      "token" => $this->token->extendToken()
    );

    break;

  case "create":
    $item->values = $this->request->getParameters("post", "values");
    $id = $item->create();
    if (intval($id) > 0) {
      $this->output = array(
        "code" => 201,
        "status" => array(
          "success" => true,
          "token" => $this->token->extendToken()),
        "data" => array("item_id" => $id));
    }
    else {
      if ($id == 0) {
        $this->output = array(
          "code" => 201,
          "status" => array(
            "success" => true,
            "token" => $this->token->extendToken()),
          "data" => array("item_id" => null));
      }
      else {
        $this->output["status"] = array("success" => false);
        $this->output["data"] = array("item_id" => NULL);
      }
    }

    break;

  case "single_update":
    $item->values = $this->request->getParameters("put", "values");
    $item->id = $this->request->getParameters("get", "id");
    $affected_items = $item->singleUpdate();
    if ($affected_items) {

      $this->output["status"] = array(
        "success" => true,
        "affected_items" => $affected_items,
        "token" => $this->token->extendToken()
      );
    }
    else {
      $this->output["status"] = array("success" => false);
    }
    break;

  case "delete":
    $item->filter = $this->request->getParameters("delete", "filter");
    $affected_items = $item->delete();
    if ($affected_items) {

      $this->output["status"] = array(
        "success" => true,
        "token" => $this->token->extendToken(),
        "affected_items" => $affected_items
      );
    }
    else {
      $this->output["status"] = array("success" => false);
    }
    break;
}