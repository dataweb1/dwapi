<?php
try{
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "http://dwapi.local/v2/item/read?project=&entity=",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    echo $response;
  }

} catch (Throwable $exception) { //Use Throwable to catch both errors and exceptions
  header('HTTP/1.1 500 Internal Server Error'); //Tell the browser this is a 500 error
  echo $e->getMessage();
}