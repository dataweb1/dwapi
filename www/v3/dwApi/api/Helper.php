<?php
namespace dwApi\api;
use voku\helper\URLify;
use Symfony\Component\Yaml\Yaml;


/**
 * Class Helper
 * @package dwApi\api
 */
class Helper {

  static $elements_to_mask = ["password", "new_password", "pass"];

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
          $value = $first_three_chars . str_repeat("•", max($length - 2,0)) . $last_three_chars;
        }
      }
    }
    return $values;
  }


  /**
   * @param $file_name
   * @param null $element
   * @return bool|mixed
   */
  public static function readYaml($file_name, $element = NULL) {
    $yaml_content = Yaml::parse(file_get_contents($file_name));

    if ($element == NULL) {
      return $yaml_content;
    }
    else {
      if (array_key_exists($element, $yaml_content)) {
        return $yaml_content[$element];
      }
      else {
        return false;
      }
    }
  }

  /**
   * @param $file_name
   * @param null $element
   * @return bool|mixed
   */
  public static function readJson($file_name, $element = NULL) {
    $json_content = json_decode(file_get_contents($file_name), true);

    if ($element == NULL) {
      return $json_content;
    }
    else {
      if (array_key_exists($element, $json_content)) {
        return $json_content[$element];
      }
      else {
        return false;
      }
    }
  }
}