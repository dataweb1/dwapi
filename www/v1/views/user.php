<?php
$user = new User($this);

switch ($this->action) {
  case "login":
    $user->email = $this->request->getParameters("post", "email");
    $user->password = $this->request->getParameters("post", "password");
    $user = $user->login();
    if ($user["user_id"]) {
      $this->output = array(
        "status" => array(
          "success" => true,
          "token" => $this->token->token
        ),
        "data" => array("user_id" => $user["user_id"], "item" => $user));
    }
    else {
      $this->output = array(
        "code" => 401,
        "status" => array(
          "success" => false,
          "message" => "User with this e-mail/password not found.")
      );
    }
    break;

  case "logout":
    $user->logout();

    $this->output = array(
      "status" => array(
        "success" => true)
    );

    break;

  case "register":
    $user->values = $this->request->getParameters("post", "values");
    $user->email = $user->values["email"];
    $user->password = $user->values["password"];
    $user_id = $user->register();
    if ($user_id) {
      $this->output = array(
        "status" => array(
          "success" => true,
          //"token" => $this->token->create($user_id)
        ),
        "data" => array(
          "user_id" => $user_id)
      );
    }
    else {
      $this->output = array(
        "code" => 400,
        "status" => array(
          "success" => false,
          "message" => "400: User with this email already exists.")
      );
    }
    break;

  case "validate_token":
    // Validating current token is already done in _construct()
    // $user->validateToken(); is not needed here

    // only outputting is needed
    $data = $this->token->data;
    $data["token"] = $this->token->token;
    $this->output = array(
      "status" => array(
        "success" => true,
        "token" => $data
      ),
    );

    break;

  case "extend_token":
    if ($user->extendToken()) {
      $data = $this->token->data;
      $data["token"] = $this->token->token;

      $this->output = array(
        "status" => array(
          "success" => true,
          "token" => $data
        ),
      );
    }
    break;
  /*
  case "single_read":
    $user->singleRead();
    break;

  case "read":
    $user->read();
    break;

  case "create":
    $user->create();
    break;

  case "update":
    $tem->update();
    break;

  case "single_update":
    $user->singleUpdate();
    break;

  case "delete":
    $user->delete();

    break;
  */
}