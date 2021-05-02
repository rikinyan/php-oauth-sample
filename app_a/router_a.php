<?php
require('database.php');

session_start();

if (preg_match('/(\/login)$/', $_SERVER['REQUEST_URI'])) {
  session_reset();
  if (isset($_POST['email']) && isset($_POST['password'])) {
     
    $hashed_password = hash('sha256', $_POST['password']);
    $db = new DataBase();
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
  }
  return;
}

else if (preg_match('/(\/logout)$/', $_SERVER['REQUEST_URI'])) {
  session_destroy();
  header('Location: http://localhost:8000/');
  return;
}

else if (preg_match('/(\/auth)$/', $_SERVER['REQUEST_URI'])) {
  header("Access-Control-Allow-Origin: http://localhost:3000");
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
  return;
}

else if (preg_match('/^(\/authorize)/', $_SERVER['REQUEST_URI'])) {
  readfile('pages/authorize_button.html');
  return;
}

else if (preg_match('/^(\/issue_authorization_code)/', $_SERVER['REQUEST_URI'])) {
  if (isset($_POST['email']) && isset($_POST['password']) &&
   isset($_POST['response_type']) &&
   isset($_POST['client_id']) &&
   isset($_POST['redirect_url']) &&
   isset($_POST['state'])) {

    $db = new DataBase();
    $db->connect();

    $user_result = get_user($db, $_POST['email'], $_POST['password']);

    if ($user_info = $user_result->fetch()) {

      $unique_string = $_POST['client_id'].$user_info['user_id'].uniqid();
      $auth_token = hash('sha256', $unique_string);

      $db->query('insert into auth_code(auth_code, client_id, is_activated, expired_at) values(?, ?, ?, ?)',
      [
        $auth_token,
        $_POST['client_id'],
        false,
        (new DateTime())->add(new DateInterval('PT1H')) -> format('Y-m-d H:i:s')
      ]);
    
      $query = http_build_query([
        'response_type' => $_POST['response_type'],
        'client_id' => $_POST['client_id'],
        'user_id' => $user_info['user_id'],
        'code' => $auth_token,
        'state' => $_POST['state']
      ]);

      header('Location: '.$_POST['redirect_url'].'?'.$query);
      return;

    } else {
      echo 'no_user';
      return;
    }

  }
  return;
}

else if (preg_match('/^(\/issue_access_token)/', $_SERVER['REQUEST_URI'])) {
  if (isset($_GET['grant_type'])) {
    if ($_GET['grant_type'] == "authorization_code" && isset($_GET['code'])) {
      $generate_access_token_request_data = [
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_url' => $_GET['redirect_url'],
        'client_id' => $_GET['client_id'],
        'user_id' => $_GET['user_id']
      ];

      $result = generate_access_token();
      echo $result;
      return $result;
    }
  }
  return;
} 

function get_user(DataBase $db, string $email, string $password): PDOStatement {
  $hashed_password = hash('sha256', $password);

  $user_result = $db->query('select * from user where email = ? and password = ?',
              [$email, $hashed_password]);
  return $user_result;
}

function generate_access_token() {
  if (isset($_GET['grant_type'])) {
    if ($_GET['grant_type'] == 'authorization_code' && isset($_GET['code'])) {
      $db = new DataBase();
      $db->connect();
      $client_statement = 'select count(*) as client_count from client where client_id = ?';
      $statement_values = [$_GET['client_id']];
      $result = $db->query($client_statement, $statement_values);

      if ($result->fetch()['client_count'] > 0) {

        $update_auth_code_state = 'update auth_code set is_activated = 1 where auth_code = ?';
        $update_auth_code_state_values = [$_GET['code']];
        $result = $db->query($update_auth_code_state, $update_auth_code_state_values);

        $access_token = bin2hex(OAuthProvider::generateToken('100'));
        $register_access_token_state = 'insert into access_token(access_token, client_id, user_id, expired_at) values (?, ?, ?, ?)';
        $register_access_token =[
          $access_token,
          $_GET['client_id'],
          $_GET['user_id'],
          (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s')
        ];
        $db->query($register_access_token_state, $register_access_token);

        $response = [
          'access_token' => $access_token
        ];
        return json_encode($response);

      } else {
        return "there aren't client...<bn> please create client.";;
      }
    }
  }
}

require('pages/index.php');
return;
?>