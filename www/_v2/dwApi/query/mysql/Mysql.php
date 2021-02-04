<?php
namespace dwApi\query\mysql;
use dwApi\api\ErrorException;
use dwApi\api\Helper;
use Hashids\Hashids;

/**
 * Class Mysql
 * @package dwApi\query\mysql
 */
abstract class Mysql{

  protected $storage;
  protected $request;

  /**
   * @param null $property
   * @return string
   */
  public static function prepareFields($property = NULL)
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
  public static function prepareWhere($filter = NULL, $entity_type)
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

        if ($field == "hash") {
          $hashids = new Hashids('dwApi', 50);
          $value = $hashids->decode($value)[0];
          $field = $entity_type->getPrimaryKey();
        }

        if (strpos(strtoupper($field), "CONCAT") !== false) {
          $where .= $field . " " . $operator . " :" . Helper::IDify($field) . "";
        }
        else {
          $where .= "`" . $field . "` " . $operator . " :" . Helper::IDify($field) . "";
        }

        $binds[":" . Helper::IDify($field)] = $value;
      }
    }
    return [$where, $binds];
  }

  /**
   * @param null $sort
   * @return string
   */
  public static function prepareOrderBy($sort = NULL)
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
  public static function prepareLimit($paging = NULL)
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
  public static function prepareSetters($values = NULL)
  {
    $binds = [];
    $setters = "";
    if ($values != NULL) {

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
    }

    return [$setters, $binds];
  }

  /**
   * @param $binds
   * @param $stmt
   */
  public static function doBinds($binds, &$stmt)
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
   * @param $relation_entity_key
   * @param $relation_key
   * @param $relation_value
   * @return array
   */
  public function getRelationEntityItems($relation_entity_key, $relation_key, $relation_value)
  {
    $sqlQuery = "SELECT * FROM `" . $relation_entity_key . "` WHERE `" . $relation_key . "` = :relation_key";
    $stmt = $this->storage->prepare($sqlQuery);

    $stmt->bindValue(":relation_key", $relation_value);

    $stmt->execute();

    $items = [];
    /* process result */
    while ($fetched_item = $stmt->fetch(\PDO::FETCH_ASSOC)) {

      $items[] = $this->processFetchedItem($fetched_item, $relation_entity_key);
    }

    return $items;
  }


  /**
   * Process item properties and
   * @param $fetched_item
   * @param $fetched_item_entity_type
   * @return array
   */
  function processFetchedItem($fetched_item, $fetched_item_entity_type) {
    $item = [];
    foreach ($fetched_item as $fetched_item_field => $fetched_item_value) {
      if ($fetched_item_field == $this->entity_type->getPrimaryKey()) {
        $hashids = new Hashids('dwApi', 50);
        $item[$this->entity_type->getPrimaryKey()."_hash"] = $hashids->encode($fetched_item_value);
      }

      if (Helper::isJson($fetched_item_value)) {
        $item[$fetched_item_field] = json_decode($fetched_item_value, true);
      } else {
        $item[$fetched_item_field] = $fetched_item_value;
      }
    }

    if ($this->relation != NULL && is_array($this->relation)) {
      foreach ($this->relation as $r) {
        if ($r["pri_entity"] == $fetched_item_entity_type) {
          $relation_items = $this->getRelationEntityItems($r["sec_entity"], $r["sec_key"], $item[$r["pri_key"]]);
          if (is_array($item[$r["sec_entity"]]["items"])) {
            if (count($relation_items) > 0) {
              $item[$r["sec_entity"]]["items"][] = $relation_items;
            }
          } else {
            $item[$r["sec_entity"]]["items"] = $relation_items;
          }
          $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->api->request->project . "/" . $r["sec_entity"];
        }
      }
    }

    return $item;
  }


  /**
   * Set result (by element)
   * @param $element
   * @param $value
   */
  public function setResult($element, $value) {
    $this->result[$element] = $value;
  }

  /**
   * Get result (by element).
   * @param null $element
   * @return mixed|null
   */
  public function getResult($element = NULL) {

    /* return element */
    if ($element == NULL) {
      return $this->result;
    }
    else {
      return $this->result[$element];
    }
  }


  /**
   * Get debug information.
   * @return mixed
   */
  public function getDebug() {
    return $this->debug;
  }


  /**
   * Get EntityType object.
   * @return mixed
   */
  public function getEntityType() {
    return $this->entity_type;
  }
}