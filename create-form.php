<?php
require_once __DIR__ . '/session.php';
require_login();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Create Form</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 24px auto; padding: 0 12px; }
    .card { border: 1px solid #ddd; border-radius: 10px; padding: 14px; margin: 12px 0; }
    .row { display: flex; gap: 12px; flex-wrap: wrap; }
    .row > * { flex: 1; min-width: 220px; }
    label { display:block; font-size: 14px; margin: 6px 0 4px; }
    input[type="text"], select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 8px; }
    button { padding: 8px 10px; border: 1px solid #aaa; background: #f7f7f7; border-radius: 8px; cursor: pointer; }
    button:hover { background: #eee; }
    .muted { color:#666; font-size: 13px; }
    .danger { border-color:#e3a3a3; background:#fff5f5; }
    .options { margin-top: 10px; padding-left: 10px; border-left: 3px solid #eee; }
    .opt-row { display:flex; gap: 8px; align-items:center; margin: 6px 0; }
    .opt-row input[type="text"] { flex:1; }
    pre { background:#111; color:#eee; padding: 12px; border-radius: 10px; overflow:auto; }
  </style>
</head>
<body>

  <h1>Create Form</h1>
  <p class="muted">HTML + JS only. This builds a JSON payload you can later send to PHP.</p>

  <div class="card">
    <div class="row">
      <div>
        <label>Form name</label>
        <input id="formName" type="text" placeholder="e.g. Employee Feedback" />
      </div>
      <div>
        <label>Requires code?</label>
        <select id="requiresCode">
          <option value="0">No</option>
          <option value="1">Yes</option>
        </select>
      </div>
      <div>
        <label>Code (5 chars)</label>
        <input id="formCode" type="text" maxlength="5" placeholder="e.g. A1B2C" disabled />
      </div>
    </div>
  </div>

  <div class="row" style="align-items:center;">
    <h2 style="flex:2; margin: 10px 0;">Questions</h2>
    <div style="flex:1; text-align:right;">
      <button id="addQuestionBtn" type="button">+ Add question</button>
    </div>
  </div>

  <div id="questions"></div>

  <div class="card">
    <button id="buildJsonBtn" type="button">Build JSON</button>
    <button id="clearBtn" type="button">Clear</button>
    <p class="muted">When you’re ready, you’ll POST this JSON to a PHP endpoint.</p>
    <pre id="output">{}</pre>
  </div>
   <button id="saveFormBtn" type="button">Save Form</button>
    <p id="saveStatus"></p>
<script>
  const questionsEl = document.getElementById('questions');
  const formNameEl = document.getElementById('formName');
  const requiresCodeEl = document.getElementById('requiresCode');
  const formCodeEl = document.getElementById('formCode');
  const outputEl = document.getElementById('output');

  let qCounter = 0;

  requiresCodeEl.addEventListener('change', () => {
    const needs = requiresCodeEl.value === '1';
    formCodeEl.disabled = !needs;
    if (!needs) formCodeEl.value = '';
  });

  document.getElementById('addQuestionBtn').addEventListener('click', () => addQuestionCard());
  document.getElementById('buildJsonBtn').addEventListener('click', () => buildJson());

  document.getElementById('saveFormBtn').addEventListener('click', () => {
  const payload = buildJson();   // change buildJson to RETURN the payload
  if (payload) {
    saveFormToBackend(payload);
  }
  });  
    document.getElementById('clearBtn').addEventListener('click', () => {
    formNameEl.value = '';
    requiresCodeEl.value = '0';
    formCodeEl.value = '';
    formCodeEl.disabled = true;
    questionsEl.innerHTML = '';
    outputEl.textContent = '{}';
    qCounter = 0;
  });

  function addQuestionCard() {
    qCounter += 1;
    const qId = `q_${Date.now()}_${qCounter}`;

    const card = document.createElement('div');
    card.className = 'card';
    card.dataset.qid = qId;

    card.innerHTML = `
      <div class="row">
        <div style="flex:3;">
          <label>Question text</label>
          <input type="text" class="q-text" placeholder="e.g. What is your team?" />
        </div>
        <div style="flex:1;">
          <label>Type</label>
          <select class="q-type">
            <option value="OPEN">OPEN (text)</option>
            <option value="SINGLE_CHOICE">SINGLE_CHOICE (radio)</option>
            <option value="MULTI_CHOICE">MULTI_CHOICE (checkbox)</option>
          </select>
        </div>
        <div style="flex:1;">
          <label>Order</label>
          <input type="text" class="q-order" value="${getNextOrder()}" />
          <div class="muted">1,2,3… within this form</div>
        </div>
      </div>

      <div class="options" style="display:none;">
        <div class="row" style="align-items:center;">
          <div><strong>Options</strong> <span class="muted">(for radio/checkbox)</span></div>
          <div style="text-align:right;">
            <button type="button" class="add-opt">+ Add option</button>
          </div>
        </div>
        <div class="opt-list"></div>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
        <span class="muted">Question id: <code>${qId}</code></span>
        <button type="button" class="remove-q">Remove question</button>
      </div>
    `;

    // wire events
    const typeEl = card.querySelector('.q-type');
    const optionsBox = card.querySelector('.options');
    const addOptBtn = card.querySelector('.add-opt');
    const optList = card.querySelector('.opt-list');

    typeEl.addEventListener('change', () => {
      const t = typeEl.value;
      const needsOptions = (t === 'SINGLE_CHOICE' || t === 'MULTI_CHOICE');
      optionsBox.style.display = needsOptions ? 'block' : 'none';

      if (needsOptions && optList.children.length === 0) {
        addOptionRow(optList);
        addOptionRow(optList);
      }
    });

    addOptBtn.addEventListener('click', () => addOptionRow(optList));

    card.querySelector('.remove-q').addEventListener('click', () => {
      card.remove();
    });

    questionsEl.appendChild(card);
  }

  function addOptionRow(optList) {
    const row = document.createElement('div');
    row.className = 'opt-row';
    row.innerHTML = `
      <input type="text" class="opt-text" placeholder="Option text (e.g. Yes)" />
      <input type="text" class="opt-order" style="width:90px;" value="${optList.children.length + 1}" />
      <button type="button" class="remove-opt">Remove</button>
    `;

    row.querySelector('.remove-opt').addEventListener('click', () => row.remove());
    optList.appendChild(row);
  }

  function getNextOrder() {
    // default order = number of existing questions + 1
    return questionsEl.querySelectorAll('.card').length + 1;
  }

  function buildJson() {
    // Basic validation + build payload
    const name = formNameEl.value.trim();
    const requiresCode = requiresCodeEl.value === '1';
    const code = formCodeEl.value.trim();

    const errors = [];

    if (!name) errors.push("Form name is required.");
    if (requiresCode && code.length !== 5) errors.push("Code must be exactly 5 characters if requires_code is Yes.");

    const qCards = [...questionsEl.querySelectorAll('.card')];
    if (qCards.length === 0) errors.push("Add at least one question.");

    const questions = qCards.map((card) => {
      const question_text = card.querySelector('.q-text').value.trim();
      const question_type = card.querySelector('.q-type').value;
      const orderRaw = card.querySelector('.q-order').value.trim();
      const question_order = Number(orderRaw);

      if (!question_text) errors.push("Every question must have text.");
      if (!Number.isInteger(question_order) || question_order < 1) errors.push("Question order must be a positive integer.");

      let options = [];
      if (question_type === 'SINGLE_CHOICE' || question_type === 'MULTI_CHOICE') {
        const optRows = [...card.querySelectorAll('.opt-row')];
        options = optRows.map((r) => ({
          option_text: r.querySelector('.opt-text').value.trim(),
          option_order: Number(r.querySelector('.opt-order').value.trim())
        }));

        if (options.length < 2) errors.push("Choice questions should have at least 2 options.");
        for (const opt of options) {
          if (!opt.option_text) errors.push("All options must have text.");
          if (!Number.isInteger(opt.option_order) || opt.option_order < 1) errors.push("Option order must be a positive integer.");
        }
      }

      return { question_text, question_type, question_order, options };
    });

    // prevent duplicate question_order within the form
    const orders = questions.map(q => q.question_order);
    const uniqueOrders = new Set(orders);
    if (uniqueOrders.size !== orders.length) errors.push("Question order numbers must be unique.");

    if (errors.length) {
      outputEl.textContent = "ERRORS:\n- " + errors.join("\n- ");
      return;
    }

    const payload = {
      form: {
        name,
        requires_code: requiresCode ? 1 : 0,
        code: requiresCode ? code : null
      },
      questions: questions
        .sort((a,b) => a.question_order - b.question_order)
        .map(q => ({
          question_text: q.question_text,
          question_type: q.question_type,
          question_order: q.question_order,
          options: (q.options || []).sort((a,b) => a.option_order - b.option_order)
        }))
    };

    outputEl.textContent = JSON.stringify(payload, null, 2);
    return payload;
  }

  async function saveFormToBackend(payload) {
  const statusEl = document.getElementById('saveStatus');
  statusEl.textContent = 'Saving...';

    try {
      const res = await fetch('/forms/api/create_form.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      if (res.status === 401) {
        window.location.href = '/forms/login.php';
        return;
      }

      const data = await res.json();

      if (!res.ok) {
        statusEl.textContent = data.error || 'Failed to save form';
        return;
      }

      statusEl.textContent = `Form created (ID: ${data.form_id})`;
    } catch (err) {
      statusEl.textContent = 'Network error';
    }
}


  // Start with 1 question by default
  addQuestionCard();
</script>
 
</body>
</html>
