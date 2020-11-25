<?php
namespace dwApi\storage;
use dwApi\api\ErrorException;
use dwApi\api\Project;

/**
 * Class Drp7
 * @package dwApi\storage
 */
class Drp7
{
  private static $instance = null;
  private $host;
  private $post_values = [];


  public function __construct()
  {
      $credentials = Project::getInstance()->credentials;
      $this->host = $credentials["host"];

  }

  // The object is created from within the class itself
  // only if the class has no instance.
  public static function load()
  {
    if (self::$instance == null)
    {
      self::$instance = new Drp7();
    }

    return self::$instance;
  }


  /**
   * setPostValue.
   * @param $key
   * @param $value
   */
  public function setPostValue($key, $value) {
    $this->post_values[$key] =  $value;
  }


  /**
   * execute.
   * @param $class
   * @param $method
   * @return bool
   * @throws ErrorException
   */
  public function execute($class, $method)
  {
    if ($method != "") {
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->host . "/dwapi/" . $class . "/" . $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POSTFIELDS => json_encode(
          $this->post_values
        ),
        CURLOPT_CUSTOMREQUEST => "POST",
      ));

      $response = json_decode(curl_exec($curl), true);
      $err = curl_error($curl);

      curl_close($curl);



      if ($err) {
        return false;
      } else {
        if ($response["success"] == false) {
          throw new ErrorException( $response["message"], $response["error_code"]);
        }
        else {
          return $response["output"];
        }
      }
    }
  }
}