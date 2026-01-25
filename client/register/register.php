<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../session.php';
require_once __DIR__ . '/../../repository.php';
$error = '';
$errors = [];

////////////////////
$username = $_POST['username'] ?? '';
$email    = $_POST['email'] ?? '';
$pass     = $_POST['password'] ?? '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
/*
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $pass     = $_POST['password'] ?? '';
*/


    if (trim($username) === '' || strlen(trim($username)) < 3) {
        $errors['username'] = "Username must be at least 3 characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email address.";
    }

    if (strlen($pass) < 8) {
        $errors['password'] = "Password must be at least 8 characters.";
    }

    if (empty($errors)) {
    $res = Repository::registerUser($username, $pass, $email);
        if ($res > 0) {
            $_SESSION['user_id'] = $res;
            $_SESSION['username'] = $username;
            session_regenerate_id(true);
            header("Location: /forms/client/dashboard/dashboard.php");
            exit;
        } elseif ($res == -1) {
            $error = "Internal Server Error";
        } elseif ($res == -2) {
        $error = "Username or email already exists.";
        } else {
        $error = "Unknown error";
        }
    }
}
/*
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

      header("Location: /forms/client/dashboard/dashboard.php");
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
*/


?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Register - PuffinForms</title>
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/error.css">
    <link rel="stylesheet" href="/forms/client/register/register.css">
</head>
<body>

<div class="page-container">
    <div class="register-card">
        <h1>Register</h1>

        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <input name="username" type="text" placeholder="Username" value="<?= htmlspecialchars($username) ?>"
                                                                          style="border:1px solid <?= isset($errors['username']) ? 'red' : '#ccc' ?>"
                                                                          required>

                 <?php if (isset($errors['username'])): ?>
                     <div style="color:red"><?= $errors['username'] ?></div>
                 <?php endif; ?>
            </div>


            <div class="form-group">
                <input name="email" type="email" placeholder="Email Address" value="<?= htmlspecialchars($email) ?>"
                                                                             style="border:1px solid <?= isset($errors['email']) ? 'red' : '#ccc' ?>"
                                                                             required>

                 <?php if (isset($errors['email'])): ?>
                    <div style="color:red"><?= $errors['email'] ?></div>
                 <?php endif; ?>
            </div>


            <div class="form-group">
                <input name="password" type="password" placeholder="Password" style="border:1px solid <?= isset($errors['password']) ? 'red' : '#ccc' ?>"
                                                                              required>
                <?php if (isset($errors['password'])): ?>
                    <div style="color:red"><?= $errors['password'] ?></div>
                <?php endif; ?>

            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <a href="/forms/client/login/login.php" class="login-link">
            Already have an account? <span>Login</span>
        </a>
    </div>
</div>

</body>



</html>
