<html>
  <head>
    <title>index</title>
  </head>
  
  <body>
    <?php
    if (isset($_SESSION['user_id'])) {
      $user_name = $_SESSION['name'];
    ?>
      <p>logged is: <?php echo $user_name; ?></p> 
      <a href="/logout">logout</a>
    <?php
    } else {
    ?>
      <form id="login_form" action="./login" method="POST">
        <div>mail:<input type="address" name="email" id="email"></div>
        <div>password:<input type="password" name="password" id="password"></div>
        <div><input type="submit" value="login"></div>
      </form>
    <?php
    }
    ?>
  </body>
</html>