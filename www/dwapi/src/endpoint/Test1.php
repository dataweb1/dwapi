<?php
namespace dwApi\endpoint;

use dwApiLib\endpoint\Endpoint;
use dwApiLib\query\QueryFactory;

class Test1 extends Endpoint
{
  public function get() {
    $this->result["test"] = "get";
    $query = QueryFactory::create("Categorieen");
    $query->hash = "YgyQ8GYLnwRMPkqprzmWbJx7Em7NOAKD49l26dev3oE0j51BZy";
    $query->single_read();
    $this->result = $query->getResult();

    $this->hook_parameters = new \stdClass();
    $this->hook_parameters->mail = array("enabled" => true, "to_email" => "bert@data-web.be");
  }

  public function abc() {
    $this->result["test"] = "abc";
  }
}