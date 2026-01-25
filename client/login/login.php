<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../session.php';
require_once __DIR__ . '/../../repository.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login = trim($_POST['login'] ?? ''); // username OR email
  $pass  = $_POST['password'] ?? '';
  $user = Repository::loginUser($login, $pass);
  if ($user) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['username'] = $user['username'];

      session_regenerate_id(true);

      header("Location: /forms/client/dashboard/dashboard.php");
      exit;
  } else {
      $error = "Invalid username or password.";
  }

}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/error.css">
    <link rel="stylesheet" href="/forms/client/login/login.css">
    <link rel="stylesheet" href="/forms/client/bird.css">
</head>
<body>




<div class="page-container">

<!--  <div class="clover">
                                      <div class="img1">gdfg</div>
                                      <div class="img2">dgd</div>
                                      <div class="img3">dgd</div>
                                      <div class="img4">dgdgd</div>
                                    </div>-->




    <div class="login-card">

<table width="100%">
    <tr>
        <td width="0%"> <div class="bird" style="transform: scaleX(-1)"></div></td>
        <td width="100%"><h1 style="text-aligh: center; size: 2vw">Mockingbird Forms</h1></td>
        <td width="0%"><div class="bird"></div></td>
    </tr>
</table>




        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <input name="login" type="text" placeholder="Username or Email" required>
            </div>
            <div class="form-group">
                <input name="password" type="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <a href="/forms/client/register/register.php" class="register-link">
            Not a member? <span>Sign up now</span>
        </a>
    </div>
</div>

</body>
</html>
