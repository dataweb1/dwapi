<?php
namespace dwApi\query\drp7;
use dwApi\api\ErrorException;
use dwApi\api\Helper;
use dwApi\api\Request;
use dwApi\query\QueryInterface;
use dwApi\storage\Drp7;
use Hashids\Hashids;
use dwApi\query\mysql\EntityType;


/**
 * Class ItemRepository
 * @package dwApi\query\mysql
 */
class Query implements QueryInterface {

  protected $storage;
  protected $request;

  /* item parameters */
  public $values = NULL;
  public $filter = NULL;
  public $property = NULL;
  public $sort = NULL;
  public $hash = NULL;
  public $id = NULL;
  public $paging = NULL;
  public $relation = NULL;

  /* user parameters */
  public $email = NULL;
  public $password = NULL;

  /* response */
  protected $result;
  protected $debug;


  /**
   * Query constructor.
   * @param string $entity
   */
  public function __construct($entity = "") {
    $this->storage = Drp7::load();

    $this->request = Request::getInstance();

    $this->storage->setPostValue("api_host", $_SERVER["HTTP_HOST"]);
    $this->storage->setPostValue("project", $this->request->project);
    $this->storage->setPostValue("entity", $entity);

  }


  /**
   * Single read.
   * @return bool
   */
  public function single_read() {
    $this->storage->setPostValue("id", $this->id);
    $this->storage->setPostValue("relation", $this->relation);
    $this->storage->setPostValue("property", $this->property);

    $this->result = $this->storage->execute("single_read");

    return true;
  }


  /**
   * Read.
   * @return bool
   */
  public function read() {
    $this->storage->setPostValue("filter", $this->filter);
    $this->storage->setPostValue("sort", $this->sort);
    $this->storage->setPostValue("paging", $this->paging);
    $this->storage->setPostValue("relation", $this->relation);
    $this->storage->setPostValue("property", $this->property);

    $this->result = $this->storage->execute("read");

    return true;
  }


  /**
   * Create.
   * @return bool
   */
  public function create()
  {
    list($setters, $binds) = $this->prepareSetters($this->values);

    $sqlQuery = "INSERT INTO `" . $this->entity_type->key . "` SET " . $setters;

    $stmt = $this->storage->prepare($sqlQuery);

    $this->doBinds($binds, $stmt);

    if ($stmt->execute()) {
      $this->debug["query"] = $sqlQuery;
      $this->id = $this->storage->lastInsertId();
      $this->single_read();
      return true;
    } else {
      return false;
    }
  }


  /**
   * Single update.
   * @return bool
   */
  public function single_update()
  {
    if ($this->id) {
      $this->filter = [[$this->entity_type->getPrimaryKey(), "=", $this->id]];
      $this->update();


      return true;
    }

    return false;
  }

  /**
   * Update.
   * @return bool
   */
  public function update() {
    list($where, $binds_where) = $this->prepareWhere($this->filter, $this->entity_type);
    list($setters, $binds_update) = $this->prepareSetters($this->values);
    $binds = array_merge($binds_where, $binds_update);
    $sqlQuery = "UPDATE `" . $this->entity_type->key . "` SET " . $setters . " WHERE " . $where;
    $stmt = $this->storage->prepare($sqlQuery);


    $this->doBinds($binds, $stmt);

    if ($stmt->execute()) {
      $this->debug["query"] = $sqlQuery;
      $this->result["affected_items"] = $stmt->rowCount();

      return true;
    } else {
      return false;

    }
  }


  /**
   * Delete.
   * @return bool
   */
  public function delete()
  {
    list($where, $binds) = $this->prepareWhere($this->filter, $this->entity_type);

    $sqlQuery = "DELETE FROM `" . $this->entity_type->key . "` WHERE " . $where;
    $stmt = $this->storage->prepare($sqlQuery);

    $this->doBinds($binds, $stmt);

    if ($stmt->execute()) {
      $this->debug["query"] = $sqlQuery;
      $this->result["affected_items"] = $stmt->rowCount();
      return true;
    } else {
      return false;
    }

  }


  /**
   * Single delete.
   * @return bool
   */
  public function single_delete()
  {
    if ($this->id) {
      $this->filter = [[$this->entity_type->getPrimaryKey(), "=", $this->id]];
      $this->delete();


      return true;
    }

    return false;
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
  private function prepareWhere($filter = NULL, $entity_type)
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

        if ($field == $entity_type->getPrimaryKey()."_hash") {
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
  private function prepareSetters($values = NULL)
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
   * getRelationItems
   * @param $relation
   * @param $relation_value
   * @return array
   */
  private function getRelationItems($relation, $relation_value)
  {
    $sqlQuery = "SELECT * FROM `" . $relation["sec_entity"] . "` WHERE `" . $relation["sec_key"] . "` = :relation_key";
    $stmt = $this->storage->prepare($sqlQuery);

    $stmt->bindValue(":relation_key", $relation_value);

    $stmt->execute();

    $items = [];
    /* process result */
    while ($fetched_item = $stmt->fetch(\PDO::FETCH_ASSOC)) {

      $items[$fetched_item[$relation["sec_key"]]] = $this->processFetchedItem($fetched_item,  $relation["sec_entity"]);
    }

    return $items;
  }


  /**
   * Process item properties and
   * @param $fetched_item
   * @param $fetched_item_entity_type
   * @return array
   */
  private function processFetchedItem($fetched_item, $fetched_item_entity_type) {
    $item = [];
    //process fields to item
    foreach ($fetched_item as $fetched_item_field => $fetched_item_value) {

      //add hashed version if of the primary key
      if ($fetched_item_field == $this->entity_type->getPrimaryKey()) {
        $hashids = new Hashids('dwApi', 50);
        $item["hash"] = $hashids->encode($fetched_item_value);
      }

      //add value to item, if JSON add as array
      if (Helper::isJson($fetched_item_value)) {
        $item[$fetched_item_field] = json_decode($fetched_item_value, true);
      } else {
        $item[$fetched_item_field] = $fetched_item_value;
      }
    }

    //process relations to item if set
    if ($this->relation != NULL && is_array($this->relation)) {
      foreach ($this->relation as $r) {
        if ($r["pri_entity"] == $fetched_item_entity_type) {
          $relation_items = $this->getRelationItems($r, $item[$r["pri_key"]]);
          if (is_array($item[$r["sec_entity"]]["items"])) {
            if (count($relation_items) > 0) {
              $item[$r["sec_entity"]]["items"][] = $relation_items;
            }
          } else {
            $item[$r["sec_entity"]]["items"] = $relation_items;
          }
          $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $r["sec_entity"];
        }
      }
    }

    return $item;
  }



}