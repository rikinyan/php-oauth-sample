<html>
  <head>
    <title>index</title>
  </head>
  
  <body>
    <?php
    if (isset($_SESSION['user_email'])) {
      $user_name = $_SESSION['user_email'];
    ?>
      <p>logged is: <?php echo $user_name; ?></p> 
    <?php
    } else {
    ?>
      <form id="login_form" action="./login" method="POST">
        <div>mail:<input type="address" name="mail" id="mail"></div>
        <div>password:<input type="password" name="password" id="password"></div>
        <div><input type="submit" value="login"></div>
      </form>
    <?php
    }
    ?>
  </body>
</html>