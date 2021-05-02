<?php
class DataBase {
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
      echo 'db connection error';
    }
  }

  function query(string $statement, array $placeholder_values): PDOStatement {
    if (is_string($statement) && is_array($placeholder_values)) {
      try {
        $query = $this->db->prepare($statement);
        return $query->exec($placeholder_values);
      } catch (Exception $e) {
        echo "statement error";
        return null;
      }
    } else {
      echo "please check arguments.";
    }
    return null;
  }
};
?>