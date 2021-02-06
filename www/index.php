<?php
error_reporting(E_ALL & ~E_NOTICE);


require __DIR__."/../vendor/autoload.php";
require __DIR__.'/dwapi/autoload.php';

use dwApi\dwApi;
use dwApiLib\output\OutputFactory;

$api_path = "http://localhost/";
$reference_path = "https://dataweb.stoplight.io/api/v1/projects/dataweb/dwapi/nodes/reference/dwapi.json";

$api = new dwApi($api_path, $reference_path);
$api->processCall();

$output = OutputFactory::create();
$output->render();