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

  public function get_approving_authorization_redirect_query_process() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new OauthInvalidRequestException(); }
    
    if (isset($_POST['response_type']) &&
    isset($_POST['client_id']) &&
    isset($_POST['redirect_url'])) {
      if ($this->check_exist_response_type($_POST['response_type'])) { throw new OauthUnsupportedResponseTypeException(); }
      if (!$this->check_client($_POST['client_id'], $_POST['redirect_url'])) { throw new OauthUnauthorizedClientException(); }

      return $query = http_build_query([
        'response_type' => $_POST['response_type'],
        'client_id' => $_POST['client_id'],
        'redirect_url' => $_POST['redirect_url'],
        'state' => $_POST['state']
      ]);
    }
  }

  public function get_authorization_code_query_process() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new OauthInvalidRequestException(); }

    if (isset($_POST['email']) && isset($_POST['password']) &&
    isset($_POST['response_type']) && $_POST['response_type'] == 'code' &&
    isset($_POST['client_id']) &&
    isset($_POST['redirect_url']) &&
    isset($_POST['state'])) {
      if (!$this->check_exist_response_type($_POST['response_type'])) { throw new OauthUnsupportedResponseTypeException(); }
      $user_info = $this->get_user($_POST['email'], $_POST['password']);
      if (count($user_info) <= 0 ) { throw new OauthInvalidUserException; }
      if (!$this->check_client($_POST['client_id'], $_POST['redirect_url'])) { throw new OauthUnauthorizedClientException; }

      $auth_token = $this->generate_authorization_code($_POST['client_id'], $_POST['email'], $_POST['password']);

      return http_build_query([
        'code' => $auth_token,
        'client_id' => $_POST['client_id'],
        'user_id' => $user_info['user_id']
      ]);
    } else {
      throw new OauthInvalidRequestException();
    }
  }

  public function get_access_token_json_process() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new OauthInvalidRequestException(); }

    if (isset($_POST['grant_type']) &&
      isset($_POST['code']) &&
      isset($_POST['redirect_url']) &&
      isset($_POST['client_id']) &&
      isset($_POST['user_id'])) {
      if (!$this->check_exist_grant_type($_POST['grant_type'])) { throw new OauthInvalidRequestException(); }
      if (!$this->check_available_authorization_code($_POST['code'])) { throw new OauthInvalidRequestException(); }
      if (!$this->check_client($_POST['client_id'], $_POST['redirect_url'])) { throw new OauthUnauthorizedClientException(); }
      if (!$this->check_user_by_id($_POST['user_id'])) { throw new OauthInvalidRequestException(); }
      $this->activate_authorization_code($_POST['code']);
      $result = $this->generate_access_token($_POST['client_id'], $_POST['user_id']);
      return json_encode(["token" => $result]);
    } else {
    throw new OauthInvalidRequestException();
    }
  }

  private function check_exist_grant_type(string $grant_type) {
    if (in_array($grant_type, OAuth2::$grant_types, true)) {
      return true;
    }
    return false;
  }

  private function check_exist_response_type(string $response_type) {
    if (in_array($response_type, OAuth2::$response_types, true)) {
      return true;
    }
    return false;
  }

  private function is_valid_client(int $client_id, string $redirect_url) {
    $client_check_result = $this->db->query('select count(*) as client_count from client where client_id = ? and redirect_url = ?',
    [
      $client_id, 
      $redirect_url
    ]);

    if ($client_check_result->fetch()['client_count'] <= 0) {
      return false;
    }
    return true;
  }

  private function generate_authorization_code(string $client_id, string $email, string $password): string {
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

  private function get_user(string $email, string $password): array {
    $hashed_password = hash('sha256', $password);
  
    $user_result = $this->db->query('select * from user where email = ? and password = ?',
                [$email, $hashed_password]);
    if ($user_result->rowCount() == 1) {
      return $user_result->fetch();
    } else {
      return [];
    }
  }

  private function check_user_by_id(int $user_id): bool {
    $check_user_result = $this->db->query('select count(*) as user_count from user where user_id = ?', [
      $user_id
    ]);

    if ($check_user_result->fetch()['user_count'] == 1) {
      return true;
    } else {
      return false;
    } 
  }

  private function check_client($client_id, $redirect_url): bool {
    $check_client_result = $this->db->query('select count(*) as client_count from client where client_id = ? and redirect_url = ?', [
      $client_id, $redirect_url
    ]);

    if ($check_client_result->fetch()['client_count'] == 1) {
      return true;
    } else {
      return false;
    }
  }

  private function check_available_authorization_code(string $auth_code): bool {
    $auth_code_result = $this->db->query('select * from auth_code where auth_code = ? and is_activated = 0 and expired_data >= ?', 
    [$auth_code, date('Y-m-d H:i:s')]);
    return $auth_code_result->rowCount() == 1;
  }

  private function activate_authorization_code(string $auth_code) {
    $update_auth_code_state = 'update auth_code set is_activated = 1 where auth_code = ?';
    $this->db->query($update_auth_code_state, [$auth_code]); 
  }

  private function generate_access_token(string $client_id, string $user_id) {
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