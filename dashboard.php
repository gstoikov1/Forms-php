<?php
require_once __DIR__ . '/session.php';
require_login();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
    <script>
        async function fetchForms() {
            const res = await fetch('/forms/api/forms.php');

            if (res.status === 401) {
            window.location.href = '/forms/login.php';
            return;
            }

            const data = await res.json();
            console.log(data.forms);

        }
        fetchForms();
        
    </script>


<h1>Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? '') ?>!</p>
<p><a href="/forms/logout.php">Logout</a></p>
</body>
</html>
