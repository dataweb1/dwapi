<?php
use voku\helper\URLify;

class helper {

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
}