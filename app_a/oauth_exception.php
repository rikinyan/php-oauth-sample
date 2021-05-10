
<?php
class OauthInvalidRequestException extends Exception {
  function __construct() 
  {
    parent::__construct('リクエストが不正です');
  }
}

class OauthUnauthorizedClientException extends Exception {
  function __construct() 
  {
    parent::__construct('クライアントが正規登録されていません');
  }
}

class OauthUnsupportedResponseTypeException extends Exception {
  function __construct() 
  {
    parent::__construct('承認方法が不正です');
  }
}

class OauthInvalidScopeException extends Exception {
  function __construct() 
  {
    parent::__construct('付与されたスコープではアクセスできません。');
  }
}

class OauthServerException extends Exception {
  function __construct() 
  {
    parent::__construct('サーバーエラーです');
  }
}

class OauthAccessDeniedException extends Exception {
  function __construct() 
  {
    parent::__construct('アクセスが拒否されました');
  }
}

class OauthTemporarilyUnavailableException extends Exception {
  function __construct() 
  {
    parent::__construct('現在リソースは利用できません。');
  }
}

class OauthInvalidUserException extends Exception {
  function __construct() 
  {
    parent::__construct('そのユーザは存在しないか、利用できません。');
  }
}
?>