<?php
session_start();

require_once('database.php');
require_once('OAuth2.php');

if (preg_match('/(\/error_page)$/', $_SERVER['REQUEST_URI'])) {
  return;
}

$db = new Database();
$db->connect();
$oauth = new OAuth2($db);

if (preg_match('/(\/login)$/', $_SERVER['REQUEST_URI'])) {
  /*session_reset();
  if (isset($_POST['email']) && isset($_POST['password'])) {
    $hashed_password = hash('sha256', $_POST['password']);
    $db = new Database();
    $db->connect();

    $user_result = get_user($db, $_POST['email'], $_POST['password']);
    
    if ($first_found_user = $user_result->fetch()) {
      $_SESSION['user_id'] = $first_found_user['user_id'];
      $_SESSION['name'] = $first_found_user['name'];
      header('Location: http://localhost:8000/');

    } else {
      echo 'there is no requested user.';
    }

  } else {
    echo "required data ない";
  }*/
  return;
}

else if (preg_match('/(\/logout)$/', $_SERVER['REQUEST_URI'])) {
  session_destroy();
  header('Location: http://localhost:8000/');
  return;
}

else if (preg_match('/(\/auth)$/', $_SERVER['REQUEST_URI'])) {
  
  $query = $oauth->get_approving_authorization_redirect_query_process();

  header("Access-Control-Allow-Origin: http://localhost:3000");
  header('Location: http://localhost:8000/authorize?'.$query);
  exit();
}

else if (preg_match('/^(\/authorize)/', $_SERVER['REQUEST_URI'])) {
  readfile('pages/authorize_button.html');
  exit();
}

else if (preg_match('/^(\/issue_authorization_code)/', $_SERVER['REQUEST_URI'])) {

  $query = $oauth->get_authorization_code_query_process();

  header('Location: '.$_POST['redirect_url'].'?'.$query);
  return;
}

else if (preg_match('/^(\/issue_access_token)/', $_SERVER['REQUEST_URI'])) {

  $json_response = $oauth->get_access_token_json_process();
  header("Access-Control-Allow-Origin: http://localhost:3000");
  header('Content-Type: application/json');

  return $json_response;
}

function redirectError(Exception $exception) {
  
  //header('Location: http://localhost:8000/error_page');
  //echo $exception;
}

require('pages/index.php');
return;
?>