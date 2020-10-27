<?php
namespace dwApi\api;
use voku\helper\URLify;


/**
 * Class Helper
 * @package dwApi\api
 */
class Helper {

  static $elements_to_mask = ["password", "new_password"];

  /**
   * @param $string
   * @return bool
   */
  public static function isJson($string)
  {
    if (is_string($string)) {
      json_decode($string, true);
      return (json_last_error() == JSON_ERROR_NONE);
    }
    else {
      return false;
    }
  }

  /**
   * @param $string
   * @return mixed
   */
  public static function IDify($string) {
    return str_replace("-", "_", URLify::filter($string));
  }


  /**
   * @param $class
   * @return mixed
   */
  public static function getClassName($class) {
    $path = explode('\\', $class);
    return array_pop($path);
  }


  /**
   * @param array $arr
   * @return bool
   */
  public static function isAssoc(array $arr) {
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  /**
   * @param $values
   * @param array $keys
   * @return mixed
   */
  public static function maskValue($values) {
    foreach ($values as $key => &$value) {
      if (is_array($value)) {
        $value = self::maskValue($value);
      }
      else {
        if (self::isAssoc($values) && in_array($key, self::$elements_to_mask)) {
          $length = strlen($value);
          $first_three_chars = substr($value, 0, 1);
          $last_three_chars = substr($value, $length - 1, 1);
          $value = $first_three_chars . str_repeat("•", $length - 2) . $last_three_chars;
        }
      }
    }
    return $values;
  }
}