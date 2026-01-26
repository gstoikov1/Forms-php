<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/pill.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
    <link rel="stylesheet" href="/forms/client/bird.css">
</head>
<body>

<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <div class="mockingbird" style="transform: scaleX(-1); height: 49px; margin-right: 0; padding-right: 0"></div>
            <h1 class="project-title">Mockingbird Forms</h1>
            <a href="/forms/client/createForm/create-form.php" class="btn btn-secondary">Create Form</a>
            <a href="/forms/client/myForms/myForms.php" class="btn btn-secondary">My Forms</a>
        </div>

        <div class="header-left">
            <a href="/forms/client/ranking/ranking.php" class="btn btn-secondary">User Ranking</a>
        </div>
        
        <div class="header-right">
            <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong></span>
            <a href="/forms/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </header>

    <main class="dashboard-content">
        <h2>Dashboard</h2>
        <section id="Forms"></section>
    </main>
</div>

<div id="accessModal" class="modal-overlay hidden">
    <div class="modal">
        <h3>Restricted Access</h3>
        <p>Please enter the 5-character access code:</p>
        <input type="text" id="modalCodeInput" maxlength="5" placeholder="•••••">
        <p id="modalError" style="color: red; display: none; margin-top: -10px; font-size: 0.9rem;"></p>
        <div class="modal-actions">
            <button id="modalCancelBtn" class="btn btn-secondary">Cancel</button>
            <button id="modalConfirmBtn" class="btn btn-primary">Verify</button>
        </div>
    </div>
</div>

<script>
    const CURRENT_USER = <?= json_encode($_SESSION['username']) ?>;
    const formsSection = document.querySelector('#Forms');
    
    // Modal Elements
    const modal = document.getElementById('accessModal');
    const modalInput = document.getElementById('modalCodeInput');
    const modalError = document.getElementById('modalError');
    const modalConfirm = document.getElementById('modalConfirmBtn');
    const modalCancel = document.getElementById('modalCancelBtn');
    let pendingAction = null; 

    async function fetchForms() {
        const res = await fetch('/forms/api/all_forms.php');
        if (res.status === 401) {
            window.location.href = '/forms/client/loginPage/login.php';
            return [];
        }
        const data = await res.json();
        return data.forms || [];
    }

    (async () => {
        let forms = await fetchForms();
        
        if (!forms.length) {
            formsSection.innerHTML = '<p>No forms found.</p>';
            return;
        }

        for (const form of forms) {
            const isMe = form.owner === CURRENT_USER;
            const isPrivate = form.requires_code == 1;

            const card = document.createElement('div');
            card.className = 'form-card';

            let deleteBtnHtml = '';
            if (isMe) {
                deleteBtnHtml = `
                    <button class="deletebtn btn btn-primary" onclick="triggerDelete(${form.id}, '${avoidXSSattacks(form.name)}', this)" style="background-color: #E0C5D9; text-decoration: none; display: inline-block; text-align: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `;
            }

            card.innerHTML = `
                <div class="form-header">
                    <h3>${avoidXSSattacks(form.name)}</h3>
                    <div class="form-header-button">
                    <button class="btn btn-primary exportbtn" onclick="triggerExport(${form.id}, ${isPrivate}, ${isMe})" style="background-color: #E0C5D9; text-decoration: none; display: inline-block; text-align: center;">
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
                    Created by <b>${avoidXSSattacks(form.owner)} ${isMe ? '(Me)' : ''}</b>
                    <br>
                    ${isPrivate ? '🔒 Private form' : '🌍 Public form'}
                </div>

                <div class="form-actions">
                    <a href="/forms/client/viewForm/form.php?id=${form.id}" class="btn btn-primary" style="background-color: #E0C5D9; text-decoration: none; display: inline-block; text-align: center;">
                    Fill form
                </a>
                
                <button class="btn btn-primary" onclick="triggerStats(${form.id}, ${isPrivate}, ${isMe})" style="background-color: #E0C5D9; text-decoration: none; display: inline-block; text-align: center;">
                    View Stats
                </button>
                </div>
            `;

            formsSection.appendChild(card);
        }
    })();

    window.triggerDelete = async (id, name, btn) => {
        if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
        try {
            const res = await fetch('/forms/api/delete_form.php', {
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

    window.triggerExport = (id, isPrivate, isMe) => {
        handleProtectedNav(`/forms/api/export_form_entries.php?id=${id}`, id, isPrivate, isMe);
    };

    window.triggerStats = (id, isPrivate, isMe) => {
        handleProtectedNav(`/forms/client/entries/entries.php?id=${id}`, id, isPrivate, isMe);
    };

    function handleProtectedNav(url, formId, isPrivate, isMe) {
        if (isMe || !isPrivate) {
            window.location.href = url;
        } else {
            pendingAction = { url, formId };
            openModal();
        }
    }

    // --- Modal Functions ---
    function openModal() {
        modalInput.value = '';
        modalError.style.display = 'none';
        modal.classList.remove('hidden');
        modalInput.focus();
    }

    modalCancel.onclick = () => {
        modal.classList.add('hidden');
        pendingAction = null;
    };

    modalConfirm.onclick = async () => {
        const code = modalInput.value.trim();
        if (code.length !== 5) {
            modalError.textContent = "Code must be 5 chars.";
            modalError.style.display = 'block';
            return;
        }

        modalConfirm.disabled = true;
        modalConfirm.textContent = "Verifying...";

        try {
            const res = await fetch('/forms/api/verify_form_code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ form_id: pendingAction.formId, code })
            });
            
            const data = await res.json();

            if (res.ok && data.ok) {
                window.location.href = pendingAction.url;
            } else {
                modalError.textContent = data.error || "Invalid code.";
                modalError.style.display = 'block';
                modalConfirm.disabled = false;
                modalConfirm.textContent = "Verify";
            }
        } catch (e) {
            modalError.textContent = "Network error.";
            modalError.style.display = 'block';
            modalConfirm.disabled = false;
            modalConfirm.textContent = "Verify";
        }
    };

    function avoidXSSattacks(str) {
        if (!str) return "";
        return str.replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[c]));
    }
</script>
</body>
</html>