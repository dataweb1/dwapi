<?php
namespace dwApi\reference;


use dwApi\dwApi;
use dwApi\api\Helper;

/**
 * Class Reference
 * @package dwApi\reference
 */
class Reference {
  private $paths = [];
  private $current_path;
  private static $instance = null;


  /**
   * Reference constructor.
   * @throws ErrorException
   */
  public function __construct()
  {
    $this->getPaths();
  }


  public function getPaths() {
    $reference_filename = __DIR__.'/../../../../reference/dwAPI'.dwApi::API_VERSION.'.json';
    if ($paths = Helper::readJson($reference_filename,  "paths")) {
      foreach($paths as $path_key => $path) {
        $this->paths[$path_key] = new Path($path);
      }

    } else {
      throw new ErrorException('OpenAPI '.dwApi::API_VERSION.' reference not found.', ErrorException::DW_PROJECT_NOT_FOUND);
    }
  }

  /**
   * @return Path $this
   */
  public function currentPath() {
    return $this->current_path;
  }

  /**
   * @param $to_find_path
   * @return bool
   */
  public function pathExits($to_find_path) {

    foreach($this->paths as $path_key => $path) {
      $pattern = '#^' . preg_replace('#{[^}]+}#', '[^/]+', $path_key) . '/?$#';

      if (preg_match($pattern, $to_find_path)) {
        $this->current_path = $path;
        return true;
      }
    }

    return false;
  }


  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance()
  {
    if (self::$instance == null)
    {
      self::$instance = new Reference();
    }

    return self::$instance;
  }

}