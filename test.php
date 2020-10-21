<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.data-web.be/v1/item/read?project=LluG3gwZKPzC&entity=product&filter=%5B%22id%22%2C%20%22%3D%22%2C%2019%5D&relation=%5B%7B%22pri_entity%22%3A%20%22product%22%2C%20%22pri_key%22%3A%20%22id%22%2C%20%22sec_entity%22%3A%20%22product_heeft_maat%22%2C%20%22sec_key%22%3A%20%22product_id%22%7D%5D",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJleHAiOjE2MDI5NzA5NzIsImlzcyI6IkxsdUczZ3daS1B6QyIsImlhdCI6MTYwMjkzNDk3Mn0.7IYKUNFKdjNd1dBeZ934b2ax-JkyAvtdhaqeOFlcog0"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}