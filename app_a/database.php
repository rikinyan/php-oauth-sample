<?php
class Database {
  private $db;

  function connect() {
    try {
      $mysql_user = $_ENV['MYSQL_USER'];
      $mysql_password = $_ENV['MYSQL_PASSWORD'];
      $mysql_dbs = 'mysql:host=mysql;port='.$_ENV['MYSQL_PORT'].';dbname='.$_ENV['MYSQL_DATABASE'];
      $this->db = new PDO(
        $mysql_dbs,
        $mysql_user,
        $mysql_password
      );
    } catch(Exception $e) {
      redirectDBError($e->getMessage());
    }
  }

  function query(string $statement, array $placeholder_values): PDOStatement {
    $query = $this->db->prepare($statement);
    if (is_string($statement) && is_array($placeholder_values)) {
      try {
        $query->execute($placeholder_values);
        return $query;
      } catch (Exception $e) {
        redirectDBError('db statement error');
      }
    } else {
      redirectDBError('please check auguments');
    }
  }
};

function redirectDBError(string $message) {
  header('Location: http://localhost:8000/error_page');
  echo $message;
}
?>