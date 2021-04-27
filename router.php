<?php
if (preg_match('/(\/login)$/', $_SERVER['REQUEST_URI'])) {
  if (isset($_POST['mail']) && isset($_POST['password'])) {
    $mail = $_POST['mail'];
    $password = $_POST['password'];

    $mysql_user = $_ENV['MYSQL_USER'];
    $mysql_password = $_ENV['MYSQL_PASSWORD'];
    $mysql_dbs = 'mysql:host=mysql;port='.$_ENV['MYSQL_PORT'].';dbname='.$_ENV['MYSQL_DATABASE'];

    $db = new PDO(
      $mysql_dbs,
      $mysql_user,
      $mysql_password
    );
    header('Location: http://localhost:8000/');
  }
  return;
}

// top
readfile('pages/index.html');
return;
?>