<?php
session_start();

if (preg_match('/^(\/auth_redirect)/', $_SERVER['REQUEST_URI'])) {
  readfile('pages/auth_redirect.html');
  return;
}

// top
require('pages/index.php');
return;
?>