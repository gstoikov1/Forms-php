<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Forms</title>

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
        <h2>All created forms</h2>

        <div id="formsList" class="forms-list"></div>
    </main>
</div>

<script>

    const CURRENT_USER = <?= json_encode($_SESSION['username']) ?>;

async function fetchAllForms(){
    const res = await fetch('/forms/api/all_forms.php');

    if (res.status === 401) {
        window.location.href = '/forms/client/loginPage/login.php';
        return [];
    }

    const data = await res.json();
    return data.forms || [];
}

function openForm(formId, requiresCode) {
    if (requiresCode == 1) {
        const code = prompt("This form is private. Enter access code:");
        if (!code) return;

        fetch('/forms/api/verify_form_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ form_id: formId, code })
        }).then(res => {
            if (res.ok) {
                window.location.href = `/forms/form.php?id=${formId}`;
            } else {
                alert("Invalid access code");
            }
        });
    } else {
        window.location.href = `/forms/form.php?id=${formId}`;
    }
}

(async () => {
    const forms = await fetchAllForms();
    const container = document.getElementById('formsList');

    if (!forms.length){
        container.innerHTML = '<p>No forms created yet.</p>';
        return;
    }

    for (const form of forms) {
        const card = document.createElement('div');
        card.className = 'form-card';

        const isMe = form.owner === CURRENT_USER;

        card.innerHTML = `
            <h3>${avoidXSSattacks(form.name)}</h3>

            <div class="form-meta">
                Created by <b>
                    ${avoidXSSattacks(form.owner)}
                    ${isMe ? ' (Me)' : ''}
                    </b>
                <br>
                ${form.requires_code == 1 ? '🔒 Private form' : '🌍 Public form'}
            </div>

            <div class="form-actions">
                <button class="btn btn-primary"
                    onclick="openForm(${form.id}, ${form.requires_code})" style="background-color: #E0C5D9">
                    Fill form
                </button>
            </div>
        `;

        container.appendChild(card);
    }
})();

function avoidXSSattacks(str) {
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
