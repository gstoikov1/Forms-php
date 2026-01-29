<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Forms</title>

    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="../button.css">
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <link rel="stylesheet" href="../pill.css">
    <link rel="stylesheet" href="../bird.css">
</head>

<body>
<div class="page-container">
<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <a href = "../dashboard/dashboard.php"><div class="mockingbird" style="transform: scaleX(-1); height: 49px; margin-right: 0; padding-right: 0"></div></a>
            <h1 class="project-title">Mockingbird Forms</h1>
            <a href="../createForm/create-form.php" class="btn btn-secondary">Create Form</a>
        </div>

        <div class="header-right">
            <a href="../dashboard/dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
    </header>

    <main class="dashboard-content">
        <h2>My Forms</h2>

        <div id="formsList" class="forms-list"></div>
    </main>
</div>

</div>
    <footer class="main-footer" style="text-align: center; padding: 20px; color: #888; font-size: 14px;">
        <span>&copy; <?= date('Y') ?> Mockingbird Forms.</span>
        <span>Created by Veneta, Gabriel, Petar</span>
    </footer>

<script>

    const CURRENT_USER = <?= json_encode($_SESSION['username']) ?>;

async function fetchMyForms(){
    const res = await fetch('../../api/my_forms.php');

    if (res.status === 401) {
        window.location.href = '../loginPage/login.php';
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
            const isMe = form.owner === CURRENT_USER;
            const isPrivate = form.requires_code == 1;

            const card = document.createElement('div');
            card.className = 'form-card';

            const codeDisplay = form.requires_code == 1 
                ? `<br><span style="color:#666; font-size:0.9em;">Access Code: <b>${avoidXSSattacks(form.code || '')}</b></span>` 
                : `<br><span style="color:#666; font-size:0.9em;">Access Code: <b>None</b></span>` ;

            deleteBtnHtml = `
                <button class="deletebtn btn btn-primary" onclick="triggerDelete(${form.id}, '${avoidXSSattacks(form.name)}', this)" style="background-color: #C38EB5; text-decoration: none; display: inline-block; text-align: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;

            card.innerHTML = `
                <div class="form-header">
                    <h3>${avoidXSSattacks(form.name)}</h3>
                    <div class="form-header-button">
                    <button class="btn btn-primary exportbtn" onclick="triggerExport(${form.id})" style="background-color: #C38EB5; text-decoration: none; display: inline-block; text-align: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </button>
                      ${deleteBtnHtml}
                    </div>
                </div>

                <div class="form-meta">
                    Created by <b>${avoidXSSattacks(form.owner)} (Me)</b>
                    <br>
                    ${isPrivate ? '🔒 Private form' : '🌍 Public form'}
                    ${codeDisplay}
                </div>

                <div class="form-actions">
                    <a href="../viewForm/form.php?id=${form.id}" class="btn btn-primary" style="text-decoration: none; display: inline-block; text-align: center;">
                    Fill form
                </a>
                
                <button class="btn btn-primary" onclick="triggerStats(${form.id})" style="text-decoration: none; display: inline-block; text-align: center;">
                    View Stats
                </button>
                </div>
            `;

            container.appendChild(card);
        }
})();

window.triggerDelete = async (id, name, btn) => {
        if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
        try {
            const res = await fetch('../../api/delete_form.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ form_id: id })
            });
            if (res.ok) {
                btn.closest('.form-card').remove();
            } else {
                alert('Failed to delete.');
            }
        } catch (e) {
            alert('Error deleting form.');
        }
    };

    window.triggerExport = (id) => {
        window.location.href = `../../api/export_form_entries.php?id=${id}`;
    };

    window.triggerStats = (id) => {
        window.location.href = `../entries/entries.php?id=${id}`;
    };

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