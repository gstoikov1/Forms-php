<?php
require_once __DIR__ . '/session.php';

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($formId <= 0) {
  http_response_code(404);
  exit('Form not found');
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Form</title></head>
<body>
    <script>
        async function fetchForms() {
            const formId = <?= $formId ?>;
            const res = await fetch(`/forms/api/form.php?id=${formId}`);

            if (res.status === 401) {
            window.location.href = '/forms/login.php';
            return;
            }

            const data = await res.json();
            console.log(data);

        }
        fetchForms();
        
    </script>


<h1>Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? '') ?>!</p>
</body>
</html>