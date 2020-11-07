<?php
namespace dwApi\query\mysql;
use dwApi\api\ErrorException;
use dwApi\api\Request;
use dwApi\query\InterfaceItemRepository;
use dwApi\storage\Mysql as StorageMysql;


/**
 * Class ItemRepository
 * @package dwApi\query\mysql
 */
class ItemRepository extends Mysql implements InterfaceItemRepository {

  /* parameters */
  public $values = NULL;
  public $filter = NULL;
  public $property = NULL;
  public $sort = NULL;
  public $id_hash = NULL;
  public $id = NULL;
  public $paging = NULL;
  public $relation = NULL;

  /* response */
  protected $result;
  protected $debug;
  protected $entity_type;


  /**
   * ItemRepository constructor.
   * @param string $entity_type
   * @throws ErrorException
   */
  public function __construct($entity_type = "") {
    $this->storage = StorageMysql::load();

    $this->request = Request::getInstance();

    $this->entity_type = new EntityType();
    $this->entity_type->load($entity_type);
  }


  /**
   * Single read.
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

      $this->result["item"] = $this->processFetchedItem($fetched_item, $this->entity_type->key);;
      $this->result["assets_path"] = "//" . $_SERVER["HTTP_HOST"] . "/files/" . $this->request->project . "/" . $this->entity_type->key;

      $this->debug["query"] = $sqlQuery;



      return true;
    }
    else {
      return false;
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
      $items[] = $this->processFetchedItem($fetched_item, $this->entity_type->key);
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
}