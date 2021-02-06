<?php
function custom_autoloader($class) {
  if (strpos($class, "dwApi\\") !== false) {
    $c = str_replace("dwApi\\", "src\\", $class);
    $fileName = __DIR__ . "/" . str_replace("\\", "/", $c) . '.php';

    if (is_readable($fileName)) {
      include $fileName;
    }
  }
}

spl_autoload_register('custom_autoloader');
