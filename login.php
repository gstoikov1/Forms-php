<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/repository.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login = trim($_POST['login'] ?? ''); // username OR email
  $pass  = $_POST['password'] ?? '';
  $user = Repository::loginUser($login, $pass);
  if ($user) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['username'] = $user['username'];

      session_regenerate_id(true);

      header("Location: /forms/dashboard.php");
      exit;
  } else {
      $error = "Invalid username or password.";
  }

}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
<h1>Login</h1>

<?php if ($error): ?>
  <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
  <div>
    <label>Username or Email</label><br>
    <input name="login" required>
  </div>
  <div>
    <label>Password</label><br>
    <input name="password" type="password" required>
  </div>
  <button type="submit">Login</button>
</form>

<p><a href="/forms/register.php">No account? Register</a></p>
</body>
</html>
