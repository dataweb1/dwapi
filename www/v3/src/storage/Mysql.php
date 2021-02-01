<?php
namespace dwApi\storage;
use dwApi\api\Project;

/**
 * Class Mysql
 * @package dwApi\storage
 */
class Mysql
{
  private static $instance = null;
  private $conn;

  public function __construct()
  {
      $credentials = Project::getInstance()->credentials;

      $this->conn = new \PDO("mysql:host=" . $credentials["host"] . ";port=3306;dbname=" . $credentials["dbname"], $credentials["username"], $credentials["password"], [
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
      ]);
      $this->conn->exec("set names utf8");
  }

  // The object is created from within the class itself
  // only if the class has no instance.
  public static function load()
  {
    if (self::$instance == null)
    {
      self::$instance = new Mysql();
    }

    return self::$instance->conn;
  }
}