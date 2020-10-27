<?php
namespace dwApi\query\mysql;
use dwApi\api\ErrorException;
use dwApi\api\Helper;
use dwApi\storage\Mysql as StorageMysql;
use Hashids\Hashids;
use dwApi\api\Request;


/**
 * Class ItemRepository
 * @package dwApi\query\mysql
 */
class ItemRepository extends Mysql {

  /* parameters */
  public $values = NULL;
  public $filter = NULL;
  public $property = NULL;
  public $sort = NULL;
  public $id = NULL;
  public $paging = NULL;
  public $relation = NULL;

  /* response */
  protected $result;
  protected $debug;
  protected $entity_type;


  /**
   * ItemRepository constructor.
   * @param $endpoint
   * @param string $entity_type
   * @throws ErrorException
   */
  public function __construct($endpoint, $entity_type = "") {
    $this->storage = StorageMysql::load();

    $this->request = Request::getInstance();

    $this->entity_type = new EntityType();
    if ($entity_type == "") { $entity_type = $endpoint; }
    $this->entity_type->load($entity_type);
  }


  /**
   * @return bool
   */
  public function single_read()
  {
    if ($this->id > 0) {
      $fields = self::prepareFields($this->property);

      $sqlQuery = "SELECT " . $fields . " FROM `" . $this->entity_type->key . "` WHERE `" . $this->entity_type->getPrimaryKey() . "` = :id  LIMIT 1";
      $binds = array(":id" => $this->id);

      $stmt = $this->storage->prepare($sqlQuery);
      $this->doBinds($binds, $stmt);

      $stmt->execute();

      $fetched_item = $stmt->fetch(\PDO::FETCH_ASSOC);


      $item = [];
      if ($fetched_item) {
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
            if ($r["pri_entity"] == $this->entity_type->key) {
              $item[$r["sec_entity"]]["items"] = $this->getRelationEntityItems($r["sec_entity"], $r["sec_key"], $item[$r["pri_key"]]);
              $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $r["sec_entity"];
            }
          }
        }
      }

      $this->debug["query"] = $sqlQuery;

      $this->result = array(
        "item" => $item,
        "assets_path" => "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $this->entity_type->key);


      return true;
    }
    else {
      return false;
    }
  }

  /**
   * @return bool
   */
  public function read()
  {
    /* build query */
    $fields = $this->prepareFields($this->property);

    $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS " . $fields . " FROM `" . $this->entity_type->key . "`";
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
          if ($r["pri_entity"] == $this->entity_type->key) {
            $item[$r["sec_entity"]]["items"] = $this->getRelationEntityItems($r["sec_entity"], $r["sec_key"], $item[$r["pri_key"]]);
            $item[$r["sec_entity"]]["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $r["sec_entity"];
          }
        }
      }

      $items[] = $item;
    }

    $this->result = array(
      "item_count" => $item_count,
      "items" => $items,
      "assets_path" => "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $this->entity_type->key);

    $this->debug["query"] = $sqlQuery;

    if ($limit != "") {
      $this->result["paging"]["page"] = intval($this->paging["page"]);
      $this->result["paging"]["items_per_page"] = intval($this->paging["items_per_page"]);
      $this->result["paging"]["page_count"] = ceil(intval($item_count) / intval($this->paging["items_per_page"]));
    }

    return true;
  }


  /**
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
   * @return bool
   */
  public function update() {
    list($where, $binds_where) = $this->prepareWhere($this->filter, $this->entity_type);
    list($setters, $binds_update) = $this->prepareSetters($this->values);
    $binds = array_merge($binds_where, $binds_update);
    $sqlQuery = "UPDATE `" . $this->entity_type->key . "` SET " . $setters . " WHERE " . $where;
    //print_r($sqlQuery);
    //print_r($binds);
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
}