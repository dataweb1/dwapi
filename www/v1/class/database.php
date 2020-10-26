<?php
class Database
{
  public $conn = null;

  public function __construct($credentials)
  {

    $this->conn = new PDO("mysql:host=" . $credentials["host"] . ";port=3306;dbname=" . $credentials["dbname"], $credentials["username"], $credentials["password"], [
      PDO::ATTR_EMULATE_PREPARES => false,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $this->conn->exec("set names utf8");

    return $this->conn;
  }
}