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

  function is_valid_client(Database $db, int $client_id, string $redirect_url) {
    $client_check_result = $db->query('select count(*) as client_count from client where client_id = ? and redirect_url = ?',[
      $client_id, $redirect_url
    ]);

    if ($client_check_result->fetch()['client_count'] <= 0) {
      throw new OauthUnauthorizedClientException();
    }
  }

  function issue_authorization_code($db, ) {

  }
  
}

?>