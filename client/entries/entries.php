<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Form Statistics - Mockingbird Forms</title>
    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="../button.css">
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <link rel="stylesheet" href="../ranking/ranking.css">
    <link rel="stylesheet" href="../entries/entries.css">
    <link rel="stylesheet" href="../pill.css">
    <link rel="stylesheet" href="../bird.css">
</head>
<body>

<header class="main-header">
    <div style="display: flex; align-items: center; flex-grow: 1;">
        <a href="../dashboard/dashboard.php">
            <div class="mockingbird" style="transform: scaleX(-1); height: 49px;"></div>
        </a>
        <h1 id="formName" class="project-title" style="margin-left: 15px;">Form Statistics</h1>
    </div>
    <div class="header-right">
        <a href="../dashboard/dashboard.php" class="btn btn-secondary">Back to dashboard</a>
    </div>
</header>

<main class="stats-container" id="statsWrapper">
    <div id="loading">Loading statistics...</div>
</main>

<script>
    (async () => {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');
        const wrapper = document.getElementById('statsWrapper');

        if (!id) {
            wrapper.innerHTML = '<p class="error-msg">Missing form ID.</p>';
            return;
        }

        try {
            const res = await fetch(`../../api/form_entries.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            
            if (data.error) throw new Error(data.error);

            document.getElementById('formName').textContent = `Stats: ${data.name}`;
            wrapper.innerHTML = ''; // Clear loading

            // Summary Card
            const summary = document.createElement('section');
            summary.className = 'form-card';
            summary.style.maxWidth = '700px';
            summary.style.width = '100%';
            summary.innerHTML = `<h3 style="margin:0; text-align:center;">Total Submissions: <span style="color:var(--color-main)">${data.filledCount}</span></h3>`;
            wrapper.appendChild(summary);

            // Loop through Questions
            Object.keys(data.questionsFilled).forEach(qId => {
                const q = data.questionsFilled[qId];
                const section = document.createElement('section');
                section.className = 'form-card';
                section.style.maxWidth = '700px';
                section.style.width = '100%';

                let content = `<h3 style="color:var(--color-main); margin-top:0;">${q.text}</h3>`;
                content += `<p style="font-size:12px; color:#888; text-transform:uppercase; margin-bottom:20px;">Type: ${q.type}</p>`;

                if (q.type === 'OPEN') {
                    content += `<div class="open-answers">`;
                    q.givenAnswers.forEach(ans => {
                        content += `<div class="open-answer-item">${ans}</div>`;
                    });
                    content += `</div>`;
                } 
                else if (q.type === 'MULTI_CHOICE' || q.type === 'SINGLE_CHOICE') {
                    const isSingle = q.type === 'SINGLE_CHOICE';
                    const answers = q.givenAnswers;
                    
                    Object.keys(answers).forEach(optId => {
                        const opt = answers[optId];
                        // Calculate percentage
                        const percentage = data.filledCount > 0 ? Math.round((opt.responsesCount / data.filledCount) * 100) : 0;
                        
                        content += `
                            <div class="chart-row">
                                <div class="chart-label">
                                    <span>${opt.text}</span>
                                    <span><strong>${opt.responsesCount}</strong> (${percentage}%)</span>
                                </div>
                                <div class="bar-outer">
                                    <div class="bar-inner" style="width: ${percentage}%; background-color: ${isSingle ? 'var(--color-accent)' : 'var(--color-secondary)'};"></div>
                                </div>
                            </div>`;
                    });
                }

                section.innerHTML = content;
                wrapper.appendChild(section);
            });

        } catch (err) {
            console.error(err);
            wrapper.innerHTML = `<p class="error-msg">Error loading data: ${err.message}</p>`;
        }
    })();
</script>

</body>
</html>