<?php
namespace dwApi\reference;


use dwApi\api\ErrorException;
use dwApi\dwApi;
use dwApi\api\Helper;

/**
 * Class Reference
 * @package dwApi\reference
 */
class Reference {
  //private $paths = [];
  private $current_path;
  private $reference;
  private static $instance = NULL;


  /**
   * Reference constructor.
   */
  public function __construct()
  {
    $reference_filename = __DIR__.'/../../../../reference/dwAPI'.dwApi::API_VERSION.'.json';

    if (!$this->reference = Helper::readJson($reference_filename)) {
      throw new ErrorException('OpenAPI '.dwApi::API_VERSION.' reference not found.', ErrorException::DW_PROJECT_NOT_FOUND);
    }
  }


  /**
   * @param null $to_find_path
   * @param null $to_find_method
   * @return bool|Path
   */
  public function currentPath($to_find_path = NULL, $to_find_method = NULL) {
    if ($to_find_path == NULL) {
      return $this->current_path;
    }

    foreach($this->reference["paths"] as $path_key => $path) {
      $pattern = '#^' . preg_replace('#{[^}]+}#', '[^/]+', $path_key) . '/?$#';

      if (preg_match($pattern, $to_find_path)) {

        if (isset($path[$to_find_method])) {
          $this->current_path = new Path($path, $to_find_method);
          return $this->current_path;
        }
        else {
          return false;
        }
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