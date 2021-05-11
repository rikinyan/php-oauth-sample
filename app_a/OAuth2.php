<?php
require_once('oauth_exception.php');
require_once('database.php');

class OAuth2 {

  private $grant_types = [
    'authorization_code'
  ];

  private $response_types = [
    'code', 'token'
  ];

  public Database $db;

  function __construct(Database $db) {
    $this->db = $db;
  }

  function exist_grant_type(string $grant_type) {
    if (in_array($grant_type, OAuth2::$grant_types, true)) {
      return true;
    }
    return false;
  }

  function exist_response_type(string $response_type) {
    if (in_array($response_type, OAuth2::$response_types, true)) {
      return true;
    }
    throw new OauthUnsupportedResponseTypeException();
  }

  function is_valid_client(int $client_id, string $redirect_url) {
    $client_check_result = $this->db->query('select count(*) as client_count from client where client_id = ? and redirect_url = ?',
    [
      $client_id, 
      $redirect_url
    ]);

    if ($client_check_result->fetch()['client_count'] <= 0) {
      throw new OauthUnauthorizedClientException();
      return false;
    }
    return true;
  }

  function generate_authorization_code(string $client_id, string $email, string $password): string {
    $user_data = $this->get_user($email, $password);

    $unique_string = $client_id.$user_data['user_id'].uniqid();
    $auth_token = hash('sha256', $unique_string);

    $this->db->query('insert into auth_code(auth_code, client_id, is_activated, expired_at) values(?, ?, ?, ?)',
    [
      $auth_token,
      $client_id,
      false,
      (new DateTime())->add(new DateInterval('PT1H')) -> format('Y-m-d H:i:s')
    ]);

    return $auth_token;
  }

  function issue_access_token(string $client_id, string $user_id, string $auth_code) {
    if ($this->available_authorization_code($auth_code)) {
      $update_auth_code_state = 'update auth_code set is_activated = 1 where auth_code = ?';
      $update_auth_code_state_values = [$auth_code];
      $this->db->query($update_auth_code_state, $update_auth_code_state_values);
    } else {
      throw new OauthInvalidRequestException();
    }
    
    return $this->generate_access_token($client_id, $user_id);
  }

  function get_user(string $email, string $password) {
    $hashed_password = hash('sha256', $password);
  
    $user_result = $this->db->query('select * from user where email = ? and password = ?',
                [$email, $hashed_password]);
    if ($user_result->rowCount() == 1) {
      return $user_result->fetch();
    } else {
      throw new OauthInvalidUserException();
    }
  }

  function available_authorization_code(string $auth_code) {
    $auth_code_result = $this->db->query('select * from auth_code where auth_code = ? and is_activated = 0 and expired_data >= ?', 
    [$auth_code, date('Y-m-d H:i:s')]);
    return $auth_code_result->rowCount() == 1;
  }

  function activate_authorization_code(string $auth_code) {
    $update_auth_code_state = 'update auth_code set is_activated = 1 where auth_code = ?';
    $this->db->query($update_auth_code_state, [$auth_code]); 
  }

  function generate_access_token(string $client_id, string $user_id) {
    $access_token = bin2hex(OAuthProvider::generateToken('100'));
    $register_access_token_state = 'insert into access_token(access_token, client_id, user_id, expired_at) values (?, ?, ?, ?)';
    $register_access_token = [
      $access_token,
      $client_id,
      $user_id,
      (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d H:i:s')
    ];
    $this->db->query($register_access_token_state, $register_access_token);

    return $access_token;
  }
}

?>