<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Forms</title>

    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
    <link rel="stylesheet" href="/forms/client/allForms/allForms.css">
    <link rel="stylesheet" href="/forms/client/bird.css">
</head>

<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <a href = "/forms/client/dashboard/dashboard.php"><div class="mockingbird" style="transform: scaleX(-1); height: 49px; margin-right: 0; padding-right: 0"></div></a>
            <h1 class="project-title">Mockingbird Forms</h1>
        </div>

        <div class="header-right">
            <a href="/forms/client/dashboard/dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
    </header>

    <main class="dashboard-content">
        <h2>My Forms</h2>

        <div id="formsList" class="forms-list"></div>
    </main>
</div>

<script>

    const CURRENT_USER = <?= json_encode($_SESSION['username']) ?>;

async function fetchMyForms(){
    const res = await fetch('/forms/api/my_forms.php');

    if (res.status === 401) {
        window.location.href = '/forms/client/loginPage/login.php';
        return [];
    }

    const data = await res.json();
    return data.forms || [];
}

(async () => {
    const forms = await fetchMyForms();
    const container = document.getElementById('formsList');

    if (!forms.length){
        container.innerHTML = '<p>No forms created yet.</p>';
        return;
    }

    for (const form of forms) {
        const card = document.createElement('div');
        card.className = 'form-card';

        const codeDisplay = form.requires_code == 1 
            ? `<br><span style="color:#666; font-size:0.9em;">Access Code: <b>${avoidXSSattacks(form.code || '')}</b></span>` 
            : `<br><span style="color:#666; font-size:0.9em;">Access Code: <b>None</b></span>` ;

        card.innerHTML = `
            <h3>${avoidXSSattacks(form.name)}</h3>

            <div class="form-meta">
                Created by <b>${avoidXSSattacks(CURRENT_USER)} (Me)</b>
                <br>
                ${form.requires_code == 1 ? '🔒 Private form' : '🌍 Public form'}
                ${codeDisplay}
            </div>

            <div class="form-actions">
                <a href="/forms/client/viewForm/form.php?id=${form.id}" class="btn btn-primary" style="background-color: #E0C5D9; text-decoration: none; display: inline-block; text-align: center;">
                    Fill form
                </a>
            </div>
        `;

        container.appendChild(card);
    }
})();

function avoidXSSattacks(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[c]));
}

</script>

</body>
</html>