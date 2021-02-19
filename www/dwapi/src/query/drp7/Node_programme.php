<?php
namespace dwApi\query\drp7;

class Node_programme extends \dwApiLib\query\drp7\Item {

  public function single_read() {
    parent::single_read();
    print_r("Node_programme -> single_read");
    $this->result["ok"] = "ok";
  }
}