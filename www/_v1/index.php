<?php
error_reporting(E_ALL & ~E_NOTICE);

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  // return only the headers and not the content
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Authorization, X-Requested-With');
  header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
  exit;
}

header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/*
 * Load api with project
 */
include_once 'class/api.php';

$api = new Api();
try {

  $api->init();
  $api->processEndpoint();
  $api->renderOutput();

} catch (Exception $e) {

  $api->renderError($e);

}


