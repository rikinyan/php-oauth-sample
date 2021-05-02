<?php
require('database.php');

session_start();

if (preg_match('/(\/login)$/', $_SERVER['REQUEST_URI'])) {
  if (isset($_POST['mail']) && isset($_POST['password'])) {
    $mail = $_POST['mail'];
    $password = $_POST['password'];

    $db = new DataBase();
    $db->connect();
    
    session_reset();
    $_SESSION['user_email'] = 'aaa@email.com';
    header('Location: http://localhost:8000/');
  }
  return;
}

else if (preg_match('/(\/auth)$/', $_SERVER['REQUEST_URI'])) {
  header("Access-Control-Allow-Origin: http://localhost:3000");
  if (isset($_SESSION['user_email'])) {
    if (isset($_POST['response_type']) &&
      isset($_POST['client_id']) &&
      isset($_POST['redirect_url']) &&
      isset($_POST['state'])) {
      
        $query = http_build_query([
          'response_type' => $_POST['response_type'],
          'client_id' => $_POST['client_id'],
          'redirect_url' => $_POST['redirect_url'],
          'state' => $_POST['state']
        ]);

        $_SESSION['state'] = $_POST['state'];
        header('Location: http://localhost:8000/authorize?'.$query);
    }
  }
  return;
}

else if (preg_match('/^(\/authorize)/', $_SERVER['REQUEST_URI'])) {
  readfile('pages/authorize_button.html');
  return;
}

else if (preg_match('/^(\/issue_authorization_code)/', $_SERVER['REQUEST_URI'])) {
  if (isset($_POST['response_type']) &&
      isset($_POST['client_id']) &&
      isset($_POST['redirect_url']) &&
      isset($_POST['state'])) {
    $unique_string = $_POST['client_id']."".uniqid();
    $auth_token = hash('sha256', $unique_string);

    $db = new DataBase();
    $db->connect();

    $insert_statement = 'insert into auth_code(auth_code, client_id, is_activated, expired_at) values(
      :auth_code, :client_id, :is_activated, :expired_at
    )';
    $statement_values = [
      ':auth_code' => $auth_token,
      ':client_id' => $_POST['client_id'],
      ':is_activated' => false,
      ':expired_at' => (new DateTime())->add(new DateInterval('PT1H')) -> format('Y-m-d H:i:s')
    ];
    $db->query($insert_statement, $statement_values);
  
    $query = http_build_query([
      'response_type' => $_POST['response_type'],
      'client_id' => $_POST['client_id'],
      'code' => $auth_token,
      'state' => $_POST['state']
    ]);
    header('Location: '.$_POST['redirect_url'].'?'.$query);
    return;
  }
  return;
}

else if (preg_match('/^(\/issue_access_token)/', $_SERVER['REQUEST_URI'])) {

  if (isset($_GET['response_type']) &&
    isset($_GET['client_id']) &&
    isset($_GET['code']) &&
    isset($_GET['state'])) {

    $db = new DataBase();
    $db->connect();

    $client_statement = 'select count(*) as client_count from client where client_id = ?';
    $statement_values = [$_GET['client_id']];
    $result = $db->query($insert_statement, $statement_values);

    if ($result->fetch()->client_count > 0) {
      $update_auth_code_state = 'update auth_code set is_activated=1 where auth_code = ?';
      $update_auth_code_state_values = [$_GET['code']];
      $result = $db->query($update_auth_code_state, $update_auth_code_state_values);

      $access_token = bin2hex(OAuthProvider::generateToken('100'));
      $register_access_token_state = 'insert into access_token(access_token, client_id, expired_at) values (?, ?, ?)';
      $register_access_token =[
        $access_token,
        $_GET['client_id'],
        (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s')
      ];
      $db->query($register_access_token_state, $register_access_token);

      $response = [
        'access_token' => $access_token
      ];
      echo json_encode($response);
      return json_encode($response);

    } else {
      echo "there aren't client...<bn> please create client.";
      return;
    }
    
    $query = http_build_query([
      'response_type' => $_POST['response_type'],
      'client_id' => $_POST['client_id'],
      'redirect_url' => $_POST['redirect_url'],
      'state' => $_POST['state']
    ]);

    $_SESSION['state'] = $_POST['state'];
    header('Location: http://localhost:8000/authorize?'.$query);
  }

  return;
}

require('pages/index.php');
return;
?>