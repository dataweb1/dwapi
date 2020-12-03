<?php
try {
  $request_token = "ABC";
  $request_token_secret = "DEF";

  $oauth = new OAuth(OAUTH_CONSUMER_KEY,OAUTH_CONSUMER_SECRET);
  $oauth->setToken($request_token,$request_token_secret);
  $access_token_info = $oauth->getAccessToken("https://example.com/oauth/access_token");
  if(!empty($access_token_info)) {
    print_r($access_token_info);
  } else {
    print "Failed fetching access token, response was: " . $oauth->getLastResponse();
  }
} catch(OAuthException $E) {
  echo "Response: ". $E->lastResponse . "\n";
}
?>