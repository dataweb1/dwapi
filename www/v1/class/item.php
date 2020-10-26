<?php

class Item
{
  protected $api;
  protected $db;
  protected $request;
  protected $entity;

  public $values = NULL;
  public $filter = NULL;
  public $property = NULL;
  public $sort = NULL;
  public $id = NULL;
  public $paging = NULL;
  public $relation = NULL;

  //private $assets_path = "";


  public function __construct($api, $entity_key = "")
  {
    $this->api = $api;
    $this->db = $api->project->db->conn;
    $this->request = $api->request;
/*
    $this->values = $this->request->getParameters($this->request->method, "values");
    $this->filter = $this->request->getParameters($this->request->method, "filter");
    $this->property = $this->request->getParameters($this->request->method, "property");
    $this->sort = $this->request->getParameters($this->request->method, "sort");
    $this->id = $this->request->getParameters("get", "id");
    $this->paging = $this->request->getParameters($this->request->method, "paging");
    $this->relation = $this->request->getParameters($this->request->method, "relation");
*/
    $this->entity = new Entity($api, $entity_key);

    //$this->assets_path = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $api->project->key . "/" . $this->entity->key;
  }

  /**
   * @param null $property
   * @return string
   */
  private function prepareFields($property = NULL)
  {
    $fields = "*";

    if ($property != NULL && is_array($property)) {
      $fields = "";
      foreach ($property as $p) {
        if ($fields != "") {
          $fields .= ", ";
        }
        $fields .= "`" . $p["entity"] . "`.`" . $p["field"] . "`";
        if (isset($p["as"]) && $p["as"] != "") {

          $fields .= " AS " . $p["as"];

        }
      }
    }

    return $fields;
  }

  /**
   * @param null $filter
   * @return array
   */
  private function prepareWhere($filter = NULL)
  {
    $binds = [];
    $where = "";
    if ($filter != NULL && is_array($filter)) {
      foreach ($filter as $key => $f) {
        if ($where != "") {
          $where .= " AND ";
        }

        $field = array_key_exists("field", $f) ? $f["field"] : $f[0];
        $operator =  array_key_exists("operator", $f) ? $f["operator"] : $f[1];
        $value = array_key_exists("value", $f) ? $f["value"] : $f[2];

        if (strpos(strtoupper($field), "CONCAT") !== false) {
          $where .= $field . " " . $operator . " :" . helper::IDify($field) . "";
        }
        else {
          $where .= "`" . $field . "` " . $operator . " :" . helper::IDify($field) . "";
        }

        $binds[":" . helper::IDify($field)] = $value;
      }
    }
    return [$where, $binds];
  }

  /**
   * @param null $sort
   * @return string
   */
  private function prepareOrderBy($sort = NULL)
  {
    $orderby = "";
    if ($sort != NULL && is_array($sort)) {
      foreach ($sort as $s) {

        if ($orderby != "") {
          $orderby .= ", ";
        }
        $field = array_key_exists("field", $s) ? $s["field"] : $s[0];
        $direction = array_key_exists("direction", $s) ? $s["direction"] : $s[1];

        $orderby .= "`" . $field . "` " . $direction;
      }
    }
    return $orderby;
  }

  /**
   * @param null $paging
   * @return string
   */
  private function prepareLimit($paging = NULL)
  {
    $limit = "";
    if ($paging != NULL && is_array($paging)) {
      if (intval($paging["items_per_page"]) == 0) {
        $paging["items_per_page"] = 20;
      }

      $from = 0;
      $to = $paging["items_per_page"];
      if (intval($paging["page"]) > 0) {
        $from = (intval($paging["page"]) - 1) * intval($paging["items_per_page"]);
      }

      $limit = $from . ", " . $to;
    }
    return $limit;
  }

  /**
   * @param $values
   * @return array
   * @throws Exception
   */
  private function prepareSetters($values)
  {
    $binds = [];
    $setters = "";
    foreach ($values as $field => $value) {
      if ($setters != "") {
        $setters .= ", ";
      }
      $setters .= $field . " = :" . $field;
      if (isset($values[$field])) {
        $binds[":" . $field] = $value;
      } else {
        $binds[":" . $field] = "";
      }
    }
    return [$setters, $binds];
  }

  /**
   * @param $binds
   * @param $stmt
   */
  private function doBinds($binds, &$stmt)
  {
    if (is_array($binds)) {
      foreach ($binds as $bind_key => $bind_value) {
        if (is_array($bind_value)) {
          $bind_value = json_encode($bind_value);
        }
        $stmt->bindValue($bind_key, $bind_value);
      }
    }
  }

  /**
   * @param $sec_entity
   * @param $sec_key
   * @param $key_value
   * @return array
   */
  private function getRelationEntityItems($sec_entity, $sec_key, $key_value)
  {

    $sqlQuery = "SELECT * FROM `" . $sec_entity . "` WHERE `" . $sec_key . "` = :sec_key";
    $stmt = $this->db->prepare($sqlQuery);

    $stmt->bindValue(":sec_key", $key_value);

    $stmt->execute();

    $items = [];
    /* process result */
    while ($fetched_item = $stmt->fetch(PDO::FETCH_ASSOC)) {

      unset($fetched_item[$sec_key]);

      $item = [];
      foreach ($fetched_item as $fetched_item_field => $fetched_item_value) {

        if (helper::isJson($fetched_item_value)) {
          $item[$fetched_item_field] = json_decode($fetched_item_value, true);
        } else {
          $item[$fetched_item_field] = $fetched_item_value;
        }
      }

      foreach ($this->relation as $r) {
        if ($r["pri_entity"] == $sec_entity) {
          $item[$r["sec_entity"]]["items"] = $this->getRelationEntityItems($r["sec_entity"], $r["sec_key"], $item[$r["pri_key"]]);
          $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->api->project->key . "/" . $r["sec_entity"];
        }
      }

      $items[] = $item;
    }

    return $items;
  }

  /**
   * @param $verb
   * @param $parameter
   * @param $required
   * @return bool
   * @throws Exception
   */
  private function isParameterSyntaxCorrect($verb, $parameter, $required = true)
  {
    if ($required) {
      if (!$parameter) {
        throw new Exception(ucfirst($verb) . " is missing. At least one is needed.", 400);
      }
      else {
        if (!is_array($parameter)) {
          throw new Exception(ucfirst($verb) . " syntax not correct.", 400);
        }
      }
    }
    else {
      if ($parameter != "") {
        if (!is_array($parameter)) {
          throw new Exception(ucfirst($verb) . " syntax not correct.", 400);
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
  private function sanitizeArray(&$array, $multi_array_wanted) {
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
   * @param $values
   * @param bool $only_input_values
   * @return bool
   * @throws Exception
   */
  private function checkRequiredValues($values, $only_input_values = false)
  {
    if ($only_input_values == false) {
      $to_check = $this->entity->properties;
    }
    else {
      $to_check = $values;
    }

    //foreach($this->entity->properties as $field => $property) {
    foreach ($to_check as $field => $value) {
      $property = array("default" => "", "key" => "", "null" => "YES");
      if (isset($this->entity->properties[$field])) {
        $property = $this->entity->properties[$field];
      }
      //null = NO = required, null = YES = not required
      if ($property["default"] == "" && $property["key"] != "PRI") {
        if ($property["null"] == "NO") {
          if (
            (isset($values[$field]) && $values[$field] == "") ||
            !isset($values[$field])) {
            throw new Exception('"' . $field . '" value is required', 400);
          }
        }
      }
    }
    return true;
  }


  /**
   * @return array
   * @throws Exception
   */
  public function singleRead()
  {
    if ($this->id) {
      $fields = $this->prepareFields($this->property);

      $sqlQuery = "SELECT " . $fields . " FROM `" . $this->entity->key . "` WHERE `" . $this->entity->getPrimaryKey() . "` = :id  LIMIT 1";

      $binds = array(":id" => $this->id);

      $stmt = $this->db->prepare($sqlQuery);
      $this->doBinds($binds, $stmt);

      $stmt->execute();

      $fetched_item = $stmt->fetch(PDO::FETCH_ASSOC);
      $item = [];
      if ($fetched_item) {
        foreach ($fetched_item as $fetched_item_field => $fetched_item_value) {
          if (helper::isJson($fetched_item_value)) {
            $item[$fetched_item_field] = json_decode($fetched_item_value, true);
          } else {
            $item[$fetched_item_field] = $fetched_item_value;
          }
        }

        if ($this->isParameterSyntaxCorrect("relation", $this->relation, false)) {
          $this->sanitizeArray($this->relation, true);
          if ($this->relation != NULL && is_array($this->relation)) {
            foreach ($this->relation as $r) {
              if ($r["pri_entity"] == $this->entity->key) {
                $item[$r["sec_entity"]]["items"] = $this->getRelationEntityItems($r["sec_entity"], $r["sec_key"], $item[$r["pri_key"]]);
                $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->api->project->key . "/" . $r["sec_entity"];
              }
            }
          }
        }
      }

      $result = array(
        "item" => $item,
        "assets_path" => "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->api->project->key . "/" . $this->entity->key);

      return $result;
    } else {
      throw new Exception('ID is required', 400);
    }
  }

  /**
   * @return array
   * @throws Exception
   */
  public function read()
  {

    if ($this->isParameterSyntaxCorrect("filter", $this->filter, false)) {
      $this->sanitizeArray($this->filter, true);
      /* build query */
      $fields = $this->prepareFields($this->property);
      $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS " . $fields . " FROM `" . $this->entity->key . "`";
      list($where, $binds) = $this->prepareWhere($this->filter);
      if ($where != "") {
        $sqlQuery .= "WHERE " . $where;
      }

      if ($this->isParameterSyntaxCorrect("sort", $this->sort, false)) {
        $this->sanitizeArray($this->sort, true);
        $orderby = $this->prepareOrderBy($this->sort);
        if ($orderby != "") {
          $sqlQuery .= " ORDER BY " . $orderby;
        }
      }

      $limit = $this->prepareLimit($this->paging);
      if ($limit != "") {
        $sqlQuery .= " LIMIT " . $limit;
      }

      $stmt = $this->db->prepare($sqlQuery);
      $this->doBinds($binds, $stmt);

      $stmt->execute();

      $item_count = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

      /* process result */
      $items = [];
      while ($fetched_item = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $item = [];
        foreach ($fetched_item as $fetched_item_field => $fetched_item_value) {
          if (helper::isJson($fetched_item_value)) {
            $item[$fetched_item_field] = json_decode($fetched_item_value, true);
          } else {
            $item[$fetched_item_field] = $fetched_item_value;
          }
        }
        if ($this->isParameterSyntaxCorrect("relation", $this->relation, false)) {
          $this->sanitizeArray($this->relation, true);
          if ($this->relation != NULL && is_array($this->relation)) {
            foreach ($this->relation as $r) {
              if ($r["pri_entity"] == $this->entity->key) {
                $item[$r["sec_entity"]]["items"] = $this->getRelationEntityItems($r["sec_entity"], $r["sec_key"], $item[$r["pri_key"]]);
                $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->api->project->key . "/" . $r["sec_entity"];
              }
            }
          }
        }

        $items[] = $item;
      }



      $result = array(
        "item_count" => $item_count,
        "items" => $items,
        "assets_path" => "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->api->project->key . "/" . $this->entity->key);

      if ($limit != "") {
        $result["paging"]["page"] = intval($this->paging["page"]);
        $result["paging"]["items_per_page"] = intval($this->paging["items_per_page"]);
        $result["paging"]["page_count"] = ceil(intval($item_count) / intval($this->paging["items_per_page"]));
      }

      return $result;

    }
  }


  /**
   * @return bool
   * @throws Exception
   */
  public function create()
  {
    $this->request->processFiles($this->values);

    if ($this->isParameterSyntaxCorrect("value", $this->values)) {
      $this->sanitizeArray($this->values, false);
      $this->checkRequiredValues($this->values, false);

      list($setters, $binds) = $this->prepareSetters($this->values);

      $sqlQuery = "INSERT INTO `" . $this->entity->key . "` SET " . $setters;

      $stmt = $this->db->prepare($sqlQuery);

      $this->doBinds($binds, $stmt);

      if ($stmt->execute()) {
        return intval($this->db->lastInsertId());

        return true;

      } else {
        return false;
      }
    }
  }


  /**
   * @return bool
   * @throws Exception
   */
  public function singleUpdate()
  {
    if ($this->isParameterSyntaxCorrect("value", $this->values)) {
      $this->sanitizeArray($this->values, false);
      if ($this->id) {
        $this->filter = [[$this->entity->getPrimaryKey(), "=", $this->id]];
        $affected_items = $this->update();

        return $affected_items;

      } else {
        throw new Exception('ID is required', 400);
      }
    }
  }

  /**
   * @return bool
   * @throws Exception
   */
  public function update() {

    $this->request->processFiles($this->values);

    if ($this->isParameterSyntaxCorrect("values", $this->values) &&
        $this->isParameterSyntaxCorrect("filter", $this->filter)) {

      $this->sanitizeArray($this->values, false);
      $this->sanitizeArray($this->filter, true);

      $this->checkRequiredValues($this->values, true);

      list($where, $binds_where) = $this->prepareWhere($this->filter);
      list($setters, $binds_update) = $this->prepareSetters($this->values);
      $binds = array_merge($binds_where, $binds_update);

      $sqlQuery = "UPDATE `" . $this->entity->key . "` SET " . $setters . " WHERE " . $where;
      $stmt = $this->db->prepare($sqlQuery);

      $this->doBinds($binds, $stmt);

      if ($stmt->execute()) {
        return $stmt->rowCount();
      } else {
        return false;

      }
    }
  }


  /**
   * @return bool
   * @throws Exception
   */
  public function delete()
  {
    if ($this->isParameterSyntaxCorrect("filter", $this->filter)) {
      $this->sanitizeArray($this->filter, true);
      list($where, $binds) = $this->prepareWhere($this->filter);

      $sqlQuery = "DELETE FROM `" . $this->entity->key . "` WHERE " . $where;
      $stmt = $this->db->prepare($sqlQuery);

      $this->doBinds($binds, $stmt);

      if ($stmt->execute()) {
        return $stmt->rowCount();
      } else {
        return false;
      }
    }
  }
}