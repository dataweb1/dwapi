<?php
function custom_autoloader($class) {
  if (strpos($class, "dwApi\\") !== false) {
    $fileName = __DIR__ . "/" . str_replace("\\", "/", $class) . '.php';
    if (is_readable($fileName)) {
      include $fileName;
    }
  }
}

spl_autoload_register('custom_autoloader');