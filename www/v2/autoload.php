<?php
function custom_autoloader($class) {
  if (strpos($class, "dwApi\\") !== false) {
    include __DIR__ . "/" . str_replace("\\", "/", $class) . '.php';
  }
}

spl_autoload_register('custom_autoloader');