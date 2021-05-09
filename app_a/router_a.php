<?php
require('database.php');

session_start();

if (preg_match('/(\/login)$/', $_SERVER['REQUEST_URI'])) {
  session_reset();
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
  }
  return;
}

else if (preg_match('/(\/logout)$/', $_SERVER['REQUEST_URI'])) {
  session_destroy();
  header('Location: http://localhost:8000/');
  return;
}

else if (preg_match('/(\/auth)$/', $_SERVER['REQUEST_URI'])) {
  if (isset($_POST['response_type']) &&
    isset($_POST['client_id']) &&
    isset($_POST['redirect_url']) &&
    isset($_POST['state'])) {
      $db = new Database();
      $db->connect();

      $client_check_result = $db->query('select count(*) as client_count from client where client_id = ? and redirect_url = ?',[
        $_POST['client_id'],
        $_POST['redirect_url']
      ]);

      if ($client_check_result->fetch()['client_count'] <= 0) {
        return;
      }

      $query = http_build_query([
        'response_type' => $_POST['response_type'],
        'client_id' => $_POST['client_id'],
        'redirect_url' => $_POST['redirect_url'],
        'state' => $_POST['state']
      ]);

      $_SESSION['state'] = $_POST['state'];
      header("Access-Control-Allow-Origin: http://localhost:3000");
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
   isset($_POST['response_type']) && $_POST['response_type'] == 'code' &&
   isset($_POST['client_id']) &&
   isset($_POST['redirect_url']) &&
   isset($_POST['state'])) {

    $db = new Database();
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
        'code' => $auth_token,
        'client_id' => $_POST['client_id'],
        'user_id' => $user_info['user_id']
      ]);

      header('Location: '.$_POST['redirect_url'].'?'.$query);
      return;
    } else {
      echo 'no_user';
      return;
    }
  }
}

else if (preg_match('/^(\/issue_access_token)/', $_SERVER['REQUEST_URI'])) {
  header("Access-Control-Allow-Origin: http://localhost:3000");
  header('Content-Type: application/json');

  $db = new Database();
  $db->connect();
  $result = generate_access_token($db);
  echo $result;
  return $result;
} 

function get_user(Database $db, string $email, string $password): PDOStatement {
  $hashed_password = hash('sha256', $password);

  $user_result = $db->query('select * from user where email = ? and password = ?',
              [$email, $hashed_password]);
  return $user_result;
}

function generate_access_token(Database $db) {
  if (isset($_POST['grant_type'])) {
    if ($_POST['grant_type'] == 'authorization_code' &&
     isset($_POST['code']) &&
     isset($_POST['redirect_url']) &&
     isset($_POST['client_id'])) {
      

      $auth_code_result = $db->query('select * from auth_code where auth_code = ?', [$_POST['code']]);
      $fetch_auth_code = $auth_code_result->fetch();

      
      if ($auth_code_result->rowCount() == 1 && 
      $fetch_auth_code != false &&
      $fetch_auth_code['is_activated'] == 0 &&
      $fetch_auth_code['expired_at'] >= date('Y-m-d H:i:s')
      ) {
        $update_auth_code_state = 'update auth_code set is_activated = 1 where auth_code = ?';
        $update_auth_code_state_values = [$_POST['code']];
        $db->query($update_auth_code_state, $update_auth_code_state_values);
      } else {
        return;
      }

      
      $access_token = bin2hex(OAuthProvider::generateToken('100'));
      $register_access_token_state = 'insert into access_token(access_token, client_id, user_id, expired_at) values (?, ?, ?, ?)';
      $register_access_token =[
        $access_token,
        $_POST['client_id'],
        $_POST['user_id'],
        (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s')
      ];
      $db->query($register_access_token_state, $register_access_token);

      $response = [
        'access_token' => $access_token
      ];
      return json_encode($response);
    }
  }
}

require('pages/index.php');
return;
?>