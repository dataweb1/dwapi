<?php
namespace dwApi\query\mysql;
use dwApi\api\DwapiException;
use dwApi\api\Helper;
use dwApi\api\Request;
use dwApi\query\BaseQuery;
use dwApi\query\QueryInterface;
use dwApi\query\UserQueryInterface;
use Hashids\Hashids;
use dwApi\storage\Mysql;


/**
 * Class ItemRepository
 * @package dwApi\query\mysql
 */
class Query extends BaseQuery implements QueryInterface {

  /* item parameters */
  public $values = NULL;
  public $filter = NULL;
  public $property = NULL;
  public $sort = NULL;
  public $hash = NULL;
  public $id = NULL;
  public $paging = NULL;
  public $relation = NULL;


  /**
   * Query constructor.
   * @param string $entity_type
   * @param UserQueryInterface|null $logged_in_user
   * @throws DwapiException
   */
  public function __construct($entity_type = "", $logged_in_user = NULL) {
    parent::__construct($logged_in_user);

    $this->storage = Mysql::load();

    $this->entity_type = new EntityType();
    $this->entity_type->load($entity_type);
  }


  /**
   * single_read.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function single_read()
  {
    $fields = self::prepareFields($this->property);

    $sqlQuery = "SELECT " . $fields . " FROM `" . $this->entity_type->entity . "` WHERE `" . $this->entity_type->getPrimaryKey() . "` = :id  LIMIT 1";
    $binds = array(":id" => $this->id);

    $stmt = $this->storage->prepare($sqlQuery);
    $this->doBinds($binds, $stmt);

    $stmt->execute();

    if ($fetched_item = $stmt->fetch(\PDO::FETCH_ASSOC)) {

      $this->result["id"] = $this->id;
      $this->result["item"] = $this->processFetchedItem($fetched_item, $this->entity_type->entity);;
      $this->result["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $this->entity_type->entity;

      $this->debug["query"] = $sqlQuery;
      return true;
    }
    else {
      throw new DwapiException(ucfirst($this->getEntityType()->entity).' does not exist.', DwapiException::DW_USER_NOT_FOUND);
    }
  }

  /**
   * Read.
   * @return bool
   */
  public function read()
  {
    /* build query */
    $fields = $this->prepareFields($this->property);

    $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS " . $fields . " FROM `" . $this->entity_type->entity . "`";
    list($where, $binds) = $this->prepareWhere($this->filter, $this->entity_type);
    if ($where != "") {
      $sqlQuery .= "WHERE " . $where;
    }

    $orderby = $this->prepareOrderBy($this->sort);
    if ($orderby != "") {
      $sqlQuery .= " ORDER BY " . $orderby;
    }

    $limit = $this->prepareLimit($this->paging);
    if ($limit != "") {
      $sqlQuery .= " LIMIT " . $limit;
    }

    $stmt = $this->storage->prepare($sqlQuery);
    $this->doBinds($binds, $stmt);

    $stmt->execute();

    $item_count = $this->storage->query('SELECT FOUND_ROWS()')->fetchColumn();

    /* process result */
    $items = [];
    while ($fetched_item = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $items[] = $this->processFetchedItem($fetched_item, $this->entity_type->entity);
    }

    $this->result = array(
      "item_count" => $item_count,
      "items" => $items,
      "assets_path" => "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $this->entity_type->entity);


    $this->debug["query"] = $sqlQuery;

    if ($limit != "") {
      $this->result["paging"]["page"] = intval($this->paging["page"]);
      $this->result["paging"]["items_per_page"] = intval($this->paging["items_per_page"]);
      $this->result["paging"]["page_count"] = ceil(intval($item_count) / intval($this->paging["items_per_page"]));
    }

    return true;
  }


  /**
   * create.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function create()
  {
    if ($this->checkRequiredFields($this->values)) {
      list($setters, $binds) = $this->prepareSetters($this->values);

      $sqlQuery = "INSERT INTO `" . $this->entity_type->entity . "` SET " . $setters;

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
  }


  /**
   * single_update.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function single_update()
  {
    if (intval($this->id) > 0) {
      $this->filter = [[$this->entity_type->getPrimaryKey(), "=", $this->id]];
      $this->update();
      return true;
    }
    else {
      throw new DwapiException('ID or hash is required', DwapiException::DW_ID_REQUIRED);
    }
  }

  /**
   * update.
   * @return bool|mixed
   * @throws DwapiException
   */
  public function update() {
    if ($this->checkRequiredValues($this->values)) {
      list($where, $binds_where) = $this->prepareWhere($this->filter, $this->entity_type);
      list($setters, $binds_update) = $this->prepareSetters($this->values);
      $binds = array_merge($binds_where, $binds_update);
      $sqlQuery = "UPDATE `" . $this->entity_type->entity . "` SET " . $setters . " WHERE " . $where;
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
  }


  /**
   * Delete.
   * @return bool
   */
  public function delete()
  {
    list($where, $binds) = $this->prepareWhere($this->filter, $this->entity_type);

    $sqlQuery = "DELETE FROM `" . $this->entity_type->entity . "` WHERE " . $where;
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
    if (intval($this->id) > 0) {
      $this->filter = [[$this->entity_type->getPrimaryKey(), "=", $this->id]];
      $this->delete();
      return true;
    }
    else {
      throw new DwapiException('ID or hash is required', DwapiException::DW_ID_REQUIRED);
    }

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

  /**
   * checkRequiredFields.
   * @param $values
   * @return bool
   * @throws DwapiException
   */
  protected function checkRequiredFields(&$values) {
    foreach($this->getEntityType()->getProperties() as $property_key => $property) {
      if (!array_key_exists($property_key, $values)) {
        $default = $this->getEntityType()->getPropertyDefaultValue($property_key);
        if ($default != "") {
          $values[$property_key] = $default;
        }
      }
      if ($this->getEntityType()->isPropertyRequired($property_key)) {
        if (($values[$property_key] == "")) {
          throw new DwapiException('"' . $property_key . '" value is required', DwapiException::DW_VALUE_REQUIRED);
        }
      }
    }
    return true;
  }


  /**
   * checkRequiredValues.
   * @param $values
   * @return bool
   * @throws DwapiException
   */
  protected function checkRequiredValues($values)
  {
    foreach ($values as $property_key => $value) {
      if ($this->getEntityType()->isPropertyRequired($property_key)) {
        if ((array_key_exists($property_key, $values) && $values[$property_key] == "") ||
          !array_key_exists($property_key, $values)) {
          throw new DwapiException('"' . $property_key . '" value is required', DwapiException::DW_VALUE_REQUIRED);
        }
      }
    }

    return true;
  }

}