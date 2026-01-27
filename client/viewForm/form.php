<?php
require_once __DIR__ . '/../../session.php';

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($formId <= 0) {
    http_response_code(404);
    exit('Form not found');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Form View</title>
    
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/bird.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
    <link rel="stylesheet" href="/forms/client/login/login.css"> 
    <link rel="stylesheet" href="/forms/client/viewForm/form.css">
</head>
<body>

<div class="dashboard-wrapper">
    <header class="main-header">
        <div class="header-left">
            <a href="/forms/client/dashboard/dashboard.php">
                <div class="mockingbird" style="transform: scaleX(-1); height: 49px; margin-right: 0; padding-right: 0"></div>
            </a>
            <h1 class="project-title">Mockingbird Forms</h1>
        </div>
        <div class="header-right">
            <a href="/forms/client/allForms/allForms.php" class="btn btn-secondary">Back to Forms List</a>
        </div>
    </header>

    <main class="dashboard-content" style="padding-top: 0;">
        
        <div id="codeGate" class="hidden">
            <div class="gate-container">
                <div class="login-card">
                    <h1>Private Form</h1>
                    <p style="color: #666; margin-bottom: 24px;">This form requires an access code.</p>
                    
                    <div class="form-group">
                        <input id="codeInput" type="text" maxlength="5" class="code-input-styled" placeholder="•••••">
                    </div>
                    
                    <button id="codeBtn" type="button" class="btn btn-primary" style="width: 100%;">Unlock Form</button>
                    
                    <p id="codeMsg" class="error-msg"></p>
                </div>
            </div>
        </div>

        <div id="loadingStatus" style="margin-top: 40px; text-align: center; color: #666;">Loading form data...</div>

        <div id="formContainer" class="form-view-container hidden">
            <h1 id="formTitle" style="text-align: center; margin-bottom: 30px; color: var(--color-primary); margin-top: 0;"></h1>
            
            <div id="questionsList"></div>
            
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            
            <div class="form-actions" style="text-align: right;">
                <p id="submitMsg" class="error-msg" style="text-align: right; margin-bottom: 10px;"></p>
                <button id="submitBtn" type="button" class="btn btn-primary" style="font-size: 1.1rem; padding: 12px 24px;">Submit Answers</button>
            </div>
        </div>

    </main>

    <footer class="main-footer" style="text-align: center; padding: 20px; color: #888; font-size: 14px;">
        <span>&copy; <?= date('Y') ?> Mockingbird Forms.</span>
        <span>Created by Veneta, Gabriel, Petar</span>
    </footer>
</div>

<script>
    const formId = <?= $formId ?>;
    
    const codeGateEl = document.getElementById('codeGate');
    const formContainerEl = document.getElementById('formContainer');
    const loadingStatusEl = document.getElementById('loadingStatus');
    
    const codeInputEl = document.getElementById('codeInput');
    const codeBtnEl = document.getElementById('codeBtn');
    const codeMsgEl = document.getElementById('codeMsg');

    const formTitleEl = document.getElementById('formTitle');
    const questionsListEl = document.getElementById('questionsList');
    const submitBtnEl = document.getElementById('submitBtn');
    const submitMsgEl = document.getElementById('submitMsg');

    let currentFormData = null;

    async function init() {
        await fetchForm();
    }

    async function fetchForm() {
        loadingStatusEl.classList.remove('hidden');
        loadingStatusEl.textContent = 'Loading form...';
        
        try {
            const res = await fetch(`/forms/api/form.php?id=${formId}`);

            if (res.status === 401) {
                window.location.href = '/forms/client/loginPage/login.php';
                return;
            }

            if (res.status === 403) {
                loadingStatusEl.classList.add('hidden');
                showCodeGate();
                return;
            }

            if (!res.ok) {
                loadingStatusEl.textContent = 'Error loading form. It may not exist.';
                return;
            }

            const data = await res.json();
            currentFormData = data.data;
            
            loadingStatusEl.classList.add('hidden');
            hideCodeGate();
            renderForm(currentFormData);

        } catch (err) {
            loadingStatusEl.textContent = 'Network error occurred.';
            console.error(err);
        }
    }

    function showCodeGate() {
        codeGateEl.classList.remove('hidden');
        formContainerEl.classList.add('hidden');
        
        codeInputEl.value = '';
        codeMsgEl.textContent = '';
        codeInputEl.focus();
    }

    function hideCodeGate() {
        codeGateEl.classList.add('hidden');
        formContainerEl.classList.remove('hidden');
    }

    codeBtnEl.addEventListener('click', async () => {
        const code = codeInputEl.value.trim();
        if (code.length !== 5) {
            codeMsgEl.textContent = 'Code must be exactly 5 characters.';
            return;
        }

        codeBtnEl.disabled = true;
        codeMsgEl.textContent = 'Verifying...';
        codeMsgEl.style.color = '#666';

        try {
            const res = await fetch('/forms/api/verify_form_code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({form_id: formId, code})
            });

            const data = await res.json();

            if (!res.ok) {
                codeMsgEl.textContent = data.error || 'Invalid code.';
                codeMsgEl.style.color = '#d32f2f';
                codeBtnEl.disabled = false;
                return;
            }

            await fetchForm();

        } catch (e) {
            codeMsgEl.textContent = 'Network error.';
            codeMsgEl.style.color = '#d32f2f';
        } finally {
            codeBtnEl.disabled = false;
        }
    });

    function renderForm(data) {
        formTitleEl.textContent = data.form.name;
        questionsListEl.innerHTML = '';

        const questions = data.questions ?? [];
        const optionsMap = data.questionOptions ?? {};

        questions.sort((a, b) => (a.question_order ?? 0) - (b.question_order ?? 0));

        if (questions.length === 0) {
            questionsListEl.innerHTML = '<p style="text-align:center; color: #666;">This form has no questions.</p>';
            submitBtnEl.style.display = 'none';
            return;
        }

        questions.forEach(q => {
            const card = document.createElement('div');
            card.className = 'question-card';
            
            const title = document.createElement('h3');
            title.textContent = `${q.question_order}. ${q.question_text}`;
            
            const typeLabel = document.createElement('span');
            typeLabel.className = 'question-type-label';
            
            let typeText = 'Text Answer';
            if (q.question_type === 'SINGLE_CHOICE') typeText = 'Single Choice';
            if (q.question_type === 'MULTI_CHOICE') typeText = 'Multiple Choice';
            typeLabel.textContent = typeText;

            card.appendChild(title);
            card.appendChild(typeLabel);

            const inputContainer = document.createElement('div');
            inputContainer.style.marginTop = '10px';

            const options = optionsMap[q.id] ?? [];
            options.sort((a, b) => (a.option_order ?? 0) - (b.option_order ?? 0));

            if (q.question_type === 'OPEN') {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'question-input';
                input.name = `q_${q.id}`;
                input.placeholder = 'Type your answer here...';
                inputContainer.appendChild(input);
            } 
            else if (q.question_type === 'SINGLE_CHOICE' || q.question_type === 'MULTI_CHOICE') {
                if (options.length === 0) {
                    inputContainer.innerHTML = '<p class="error-msg" style="color:#666">No options defined.</p>';
                } else {
                    options.forEach(opt => {
                        const label = document.createElement('label');
                        label.className = 'option-label';
                        
                        const input = document.createElement('input');
                        input.type = (q.question_type === 'SINGLE_CHOICE') ? 'radio' : 'checkbox';
                        
                        if (q.question_type === 'SINGLE_CHOICE') {
                            input.name = `q_${q.id}`;
                        } else {
                            input.name = `q_${q.id}[]`;
                        }
                        input.value = opt.id;

                        label.appendChild(input);
                        label.appendChild(document.createTextNode(opt.option_text));
                        inputContainer.appendChild(label);
                    });
                }
            }

            card.appendChild(inputContainer);
            questionsListEl.appendChild(card);
        });
    }

    submitBtnEl.addEventListener('click', async () => {
        submitMsgEl.textContent = '';
        submitMsgEl.style.color = '#d32f2f';

        try {
            const payload = buildAnswersPayload();
            
            submitBtnEl.textContent = 'Submitting...';
            submitBtnEl.disabled = true;

            const res = await fetch('/forms/api/submit_form.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const text = await res.text();
                throw new Error(text || 'Submission failed');
            }

            submitMsgEl.textContent = 'Form submitted successfully!';
            submitMsgEl.style.color = '#2e7d32';
            submitBtnEl.textContent = 'Submitted';
            
            setTimeout(() => {
                window.location.href = '/forms/client/dashboard/dashboard.php';
            }, 1500);

        } catch (err) {
            submitMsgEl.textContent = err.message || 'Validation error.';
            submitBtnEl.disabled = false;
            submitBtnEl.textContent = 'Submit Answers';
        }
    });

    function buildAnswersPayload() {
        if (!currentFormData) throw new Error('Form data not loaded');

        const formIdOut = currentFormData.form.id;
        const questions = currentFormData.questions ?? [];
        const answers = [];

        for (const q of questions) {
            const qid = q.id;
            const type = q.question_type;

            if (type === 'OPEN') {
                const input = document.querySelector(`input[name="q_${qid}"]`);
                const val = input ? input.value.trim() : '';
                if (!val) throw new Error(`Question ${q.question_order} is required.`);
                answers.push({question_id: qid, type: 'OPEN', value: val});
            } 
            else if (type === 'SINGLE_CHOICE') {
                const checked = document.querySelector(`input[name="q_${qid}"]:checked`);
                if (!checked) throw new Error(`Question ${q.question_order} is required.`);
                answers.push({question_id: qid, type: 'SINGLE_CHOICE', option_id: Number(checked.value)});
            } 
            else if (type === 'MULTI_CHOICE') {
                const checked = document.querySelectorAll(`input[name="q_${qid}[]"]:checked`);
                if (checked.length === 0) throw new Error(`Question ${q.question_order} is required.`);
                const ids = Array.from(checked).map(cb => Number(cb.value));
                answers.push({question_id: qid, type: 'MULTI_CHOICE', option_ids: ids});
            }
        }

        console.log(answers);
        return {form_id: formIdOut, answers};
    }

    init();
</script>

</body>
</html>