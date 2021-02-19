<?php

$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "http://localhost/batch/programme",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "{\"batch_count\":1,\"programmes\":[{\"country\":{\"name_english\":\"string\"},\"name_english\":\"string\",\"name_local\":\"string\",\"degree_type\":{\"name\":\"string\"},\"degree_area\":{\"name_english\":\"string\"},\"degree_title\":{\"degree_type\":{\"name\":\"string\"},\"name_english\":\"string\",\"country\":{\"name_english\":\"string\"}},\"degree_title_local\":\"string\",\"semesters\":0,\"ECTS\":\"string\",\"duration\":\"string\",\"remark\":\"string\",\"comments_internal\":\"string\",\"comments_external\":\"string\",\"original_id\":0,\"programme_date_from\":\"string\",\"programme_date_to\":\"string\",\"hei_original_id\":0,\"hei_entity_original_id\":0}]}",
  CURLOPT_HTTPHEADER => [
    "Content-type: application/json",
    //"Authorization: Basic WWd5UThHWUxud1JNUGtxcHJ6bVdiSng3RW03Tk9BS0Q0OWwyNmRldjNvRTBqNTFCWnk="
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}