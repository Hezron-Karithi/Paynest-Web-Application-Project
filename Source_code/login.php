<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login</title>
<style>
:root{--orange:#ff7a00;--orange-dark:#e86f00}
body{
  margin:0;
  min-height:100vh;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  display:flex;
  align-items:center;
  justify-content:center;
  font-family:system-ui,-apple-system,Segoe UI,Roboto;
  padding:20px;
}
.card{
  background:#fff;
  color:#333;
  width:100%;
  max-width:360px;
  padding:28px;
  border-radius:14px;
}
input{
  width:100%;
  padding:12px;
  margin:10px 0; /* ensures spacing inside form */
  border:2px solid var(--orange);
  border-radius:8px;
  box-sizing:border-box; /* prevents overflow */
}
.btn{
  width:100%;
  margin-top:18px;
  padding:12px;
  border:none;
  border-radius:8px;
  background:var(--orange);
  color:#fff;
  font-size:16px;
  cursor:pointer;
}
.btn:hover{background:var(--orange-dark)}
.footer{
  text-align:center;
  margin-top:22px;
}
.footer a{color:var(--orange);text-decoration:none;font-weight:600}
</style>
</head>
<body>

<div class="card">
  <h2 style="text-align:center">Login</h2>
<form method="post" action="process_login.php">
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button class="btn">Login</button>
  </form>
  <div class="footer">
    <p>Don’t have an account?</p>
    <a href="business_register.php">Register</a><br><br>
    <a href="forgot_password.php">Forgot password?</a>
  </div>
</div>
</body>
</html>
