<?php
require_once __DIR__ . '/../../session.php';
require_login();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
    <link rel="stylesheet" href="/forms/client/bird.css">
</head>
<body link="#00000033">

<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <div class="mockingbird" style="transform: scaleX(-1); height: 49px; margin-right: 0; padding-right: 0"></div>
            <h1 class="project-title">Mockingbird Forms</h1>
            <a href="/forms/client/createForm/create-form.php" class="btn btn-secondary">Create Form</a>
        </div>

        <div class = "header-left">
           <!-- <h1 class = "project-title">Ranking</h1>  -->
            <a href="/forms/client/Ranking/ranking.php" class="btn btn-secondary">Visit user ranking</a>
        </div>
        
        <div class="header-right">
            <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong></span>
            <a href="/forms/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </header>

    <main class="dashboard-content">
        <h2>Your Dashboard</h2>
        <section id="Forms">
            </section>

        <h2 style="margin-top: 6vh"><a href = "/forms/client/allForms/allForms.php">Fill forms</a></h2>

    </main>
</div>

<script>
    async function fetchForms() {
        const res = await fetch('/forms/api/forms.php');

        if (res.status === 401) {
            window.location.href = '/forms/login.php';
            return;
        }

        const data = await res.json();
        console.log(data.forms);
        console.log(data);
        return data.forms;
    }

    (async () => {
        let forms = await fetchForms();
        const formsSection = document.querySelector('#Forms');

        for (const form of forms) {
            const element = document.createElement('div');

            const link = document.createElement('a');
            link.href = `/forms/client/viewForm/form.php?id=${form.id}`;
            link.textContent = 'Open form';

            const deleteLink = document.createElement('a');
            deleteLink.href = '#';
            deleteLink.textContent = 'Delete form';
            deleteLink.style.marginLeft = '12px';
            deleteLink.style.color = 'red';

            deleteLink.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!confirm(`Are you sure you want to delete "${form.name}"?`))
                    return;
                const res = await fetch('/forms/api/delete_form.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ form_id: form.id })
                });

                if(res.ok){
                    element.remove();
                    alert('Form deleted successfully');
                }
                else{
                    alert('Failed to delete form');
                }
            });

            const title = document.createElement('h2');
            title.textContent = form.name;

            element.append(title, link, deleteLink);
            formsSection.appendChild(element);
        }
    })();

</script>
</body>
</html>
