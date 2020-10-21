<?php
class ApiRequest
{
  public $method;
  public $input = [];

  public function __construct()
  {
    $this->method = strtolower(getenv('REQUEST_METHOD'));
  }

  private function _parsePut()
  {
    global $_PUT;

    /* PUT data comes in on the stdin stream */
    $putdata = fopen("php://input", "r");

    /* Open a file for writing */
    // $fp = fopen("myputfile.ext", "w");

    $raw_data = '';

    /* Read the data 1 KB at a time
       and write to the file */
    while ($chunk = fread($putdata, 1024))
      $raw_data .= $chunk;

    /* Close the streams */
    fclose($putdata);

    // Fetch content and determine boundary
    $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

    if(empty($boundary)){
      parse_str($raw_data,$data);
      $GLOBALS[ '_PUT' ] = $data;
      return;
    }

    // Fetch each part
    $parts = array_slice(explode($boundary, $raw_data), 1);
    $data = array();

    foreach ($parts as $part) {
      // If this is the last part, break
      if ($part == "--\r\n") break;

      // Separate content from headers
      $part = ltrim($part, "\r\n");
      list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

      // Parse the headers list
      $raw_headers = explode("\r\n", $raw_headers);
      $headers = array();
      foreach ($raw_headers as $header) {
        list($name, $value) = explode(':', $header);
        $headers[strtolower($name)] = ltrim($value, ' ');
      }

      // Parse the Content-Disposition to get the field name, etc.
      if (isset($headers['content-disposition'])) {
        $filename = null;
        $tmp_name = null;
        preg_match(
          '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
          $headers['content-disposition'],
          $matches
        );
        list(, $type, $name) = $matches;

        //Parse File
        if( isset($matches[4]) )
        {
          //if labeled the same as previous, skip
          if( isset( $_FILES[ $matches[ 2 ] ] ) )
          {
            continue;
          }

          //get filename
          $filename = $matches[4];

          //get tmp name
          $filename_parts = pathinfo( $filename );
          $tmp_name = tempnam( ini_get('upload_tmp_dir'), $filename_parts['filename']);

          //populate $_FILES with information, size may be off in multibyte situation
          $_FILES[ $matches[ 2 ] ] = array(
            'error'=>0,
            'name'=>$filename,
            'tmp_name'=>$tmp_name,
            'size'=>strlen( $body ),
            'type'=>$value
          );

          //place in temporary directory
          file_put_contents($tmp_name, $body);
        }
        //Parse Field
        else
        {
          $data[$name] = substr($body, 0, strlen($body) - 2);
        }
      }

    }
    $GLOBALS[ '_PUT' ] = $data;
    return;
  }

  /**
   * @return string|null
   */
  private function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
      $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
      $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
      $requestHeaders = apache_request_headers();
      // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      //print_r($requestHeaders);
      if (isset($requestHeaders['Authorization'])) {
        $headers = trim($requestHeaders['Authorization']);
      }
    }
    return $headers;
  }

  /**
   * @param $items
   * @return array
   */
  private function processParameters($items) {
    $input = [];
    if (is_array($items)) {
      foreach ($items as $key => $item) {
        if (helper::isJson($item)) {
          $input[$key] = json_decode($item, true);
        } else {
          $input[$key] = $item;
        }
      }
    }
    return $input;
  }

  /**
   * @return mixed|null
   */
  public function getBearerToken() {
    $headers = $this->getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
      if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
      }
    }
    return null;
  }

  /**
   * @param $type
   * @param null $key
   * @return bool|mixed
   */
  public function getParameters($type = NULL, $key = NULL) {

    if ($type != NULL) {
      if (!isset($this->input[$type])) {
        if ($type == "get" && $_GET) {
          $this->input[$type] = $this->processParameters($_GET);
        }
        if ($type == "post" && $_POST) {
          $this->input[$type] = $this->processParameters($_POST);
        }
        if ($type == "delete") {
          parse_str(file_get_contents('php://input'), $_DELETE);
          $this->input[$type] = $this->processParameters($_DELETE);
        }
        if ($type == "put") {
          //parse_str(file_get_contents('php://input'), $_PUT);
          $this->_parsePut();//_parsePut
          $this->input[$type] = $this->processParameters($GLOBALS['_PUT']);
        }
        if ($type == "files" && $_FILES) {
          $this->input[$type] = $_FILES;
        }
      }
    }

    if ($type == NULL) {
      return $this->input;
    }

    if ($key == NULL) {
      if (isset($this->input[$type]) && is_array($this->input[$type])) {
        return $this->input[$type];
      }
      else {
        return null;
      }
    }
    if (isset($this->input[$type][$key])) {
      return $this->input[$type][$key];
    }
    else {
      return null;
    }

    return false;
  }

  /**
   * @param $values
   * @throws Exception
   */
  public function processFiles(&$values) {

    if ($files = $this->getParameters("files")) {
      foreach ($files as $field => $file) {
        $target_dir = $_SERVER["DOCUMENT_ROOT"] . "files/" . $this->getParameters("get", "project") . "/";
        if (!file_exists($target_dir)) {
          mkdir($target_dir);
        }
        $target_dir = $_SERVER["DOCUMENT_ROOT"] . "files/" . $this->getParameters("get", "project") . "/" . $this->getParameters("get", "entity") . "/";
        if (!file_exists($target_dir)) {
          mkdir($target_dir);
        }

        $target_file = $target_dir . basename($file["name"]);

        if (!copy($file["tmp_name"],$target_file)) {
        //if (!move_uploaded_file($file["tmp_name"], $target_file)) {
          throw new Exception('Error uploading file(s)');
        } else { 
          //$processed_files[$field] = $file;
          $values[$field] = json_encode(array("type" => explode("/", $file["type"]), "name" => $file["name"], "size" => $file["size"]));
        }
      }
    }
  }
}