<?php
error_reporting(E_ALL & ~E_NOTICE);


require __DIR__."/../vendor/autoload.php";
require __DIR__.'/dwapi/autoload.php';

use dwApi\dwApi;
use dwApiLib\output\OutputFactory;

$settings = new stdClass();
$settings->project = "staging_AbcDefGhiJkl";//"CFNIOuwTJGyR";
$settings->api_path = "http://localhost/";
//$settings->reference_path = "https://dataweb.stoplight.io/api/v1/projects/dataweb/dwapi/nodes/reference/dwapi.json";
$settings->reference_path = "https://api.eeed.eu/reference/eeed_api.v1.yaml";
$settings->template_path = $_SERVER["DOCUMENT_ROOT"]."/templates";

$api = new dwApi($settings);
$api->allowPath("/test1/*");
$api->allowPath("/test1");
$api->processCall();

$output = OutputFactory::create();
$output->render();