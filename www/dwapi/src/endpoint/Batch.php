<?php
namespace dwApi\endpoint;

use dwApiLib\api\DwapiException;
use dwApiLib\endpoint\Endpoint;
use dwApiLib\query\QueryFactory;

class Batch extends Endpoint
{
  public function programme()
  {
    $query = QueryFactory::create("node-batch", $this->logged_in_user);

    $parameters = $this->request->getParameters("body");
    $batch_count = intval($parameters["batch_count"]);
    $items = $parameters["programmes"];
    $data = json_encode($parameters);
    $checksum = crc32($data);

    $query->filter = [["field_checksum", "=", $checksum]];
    $query->read();
    if ($query->getResult()["item_count"] == 0) {
      if (intval($batch_count) > 0 && count($items) == $batch_count) {
        $query = QueryFactory::create("node-batch", $this->logged_in_user);
        $query->values["title"] = "programme (" . $batch_count . ")";
        $query->values["field_node_type"] = "programme";
        $query->values["field_batch_count"] = $batch_count;
        $query->values["field_data"] = $data;
        $query->values["field_checksum"] = $checksum;
        $query->create();
      } else {
        throw new DwapiException('Item count does not match batch count.', DwapiException::DW_VALUE_REQUIRED);
      }
    }
    else {
      throw new DwapiException('Batch with this checksum already exists.', DwapiException::DW_VALUE_REQUIRED);
    }
  }
}