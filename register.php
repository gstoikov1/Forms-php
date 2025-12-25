<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/repository.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $pass     = $_POST['password'] ?? '';

  if ($username === '' || strlen($username) < 3) {
    $error = "Username must be at least 3 characters.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email address.";
  } elseif (strlen($pass) < 8) {
    $error = "Password must be at least 8 characters.";
  } else {
    $res = Repository::registerUser($username, $pass, $email);
    if ($res > 0) {      // Auto-login after registration
      $_SESSION['user_id'] = $res;
      $_SESSION['username'] = $username;

      session_regenerate_id(true);

      header("Location: /forms/dashboard.php");
      exit;
    } else if ($res == -1) {
        $error = "Internal Server Error";
    } else if ($res == -2) {
        $error = "Username or email already exists.";

    } else {
        $error = "Unknown error";
    }
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title></head>
<body>
<h1>Register</h1>

<?php if ($error): ?>
  <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
  <div>
    <label>Username</label><br>
    <input name="username" required>
  </div>
  <div>
    <label>Email</label><br>
    <input name="email" type="email" required>
  </div>
  <div>
    <label>Password</label><br>
    <input name="password" type="password" required>
  </div>
  <button type="submit">Create account</button>
</form>

<p><a href="/forms/login.php">Already have an account? Login</a></p>
</body>
</html>
