<html>
  <head>
    <title>index</title>
    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
  </head>
  
  <body>
    <h1>app_b</h1>
    <form method="post" action="http://localhost:8000/auth">
      <input type="hidden" name="response_type" value="code">
      <input type="hidden" name="client_id" value="1">
      <input type="hidden" name="redirect_url" value="http://localhost:3000/auth_redirect/">
      <input type="hidden" name="state" value="aaa">
      <input type="submit">
    </form>
  </body>  
</html>
