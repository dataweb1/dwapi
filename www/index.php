<?php
error_reporting(E_ALL & ~E_NOTICE);


require __DIR__."/../vendor/autoload.php";
//require 'autoload.php';

use dwApi\dwApi;
use dwApi\output\OutputFactory;


$api = new dwApi();
$api->processCall();

$output = OutputFactory::create();
$output->render();