/**
 * Laws Agent v3 - Form handling and follow-up flow
 * POSTs to api_query.php, displays answer, supports "Ask for More Information"
 */
(function () {
    'use strict';

    const API_URL = 'api_query.php';
    const form = document.getElementById('lawsAgentForm');
    const questionInput = document.getElementById('questionInput');
    const submitBtn = document.getElementById('submitBtn');
    const moreInfoBtn = document.getElementById('moreInfoBtn');
    const resultSection = document.getElementById('resultSection');
    const answerDisplay = document.getElementById('answerDisplay');
    const sourcesDisplay = document.getElementById('sourcesDisplay');
    const metaDisplay = document.getElementById('metaDisplay');
    const errorDisplay = document.getElementById('errorDisplay');
    const loadingDisplay = document.getElementById('loadingDisplay');

    let lastUserQuestion = '';
    let lastAnswer = '';

    function showError(msg, fullBody) {
        errorDisplay.innerHTML = '';
        const p = document.createElement('p');
        p.textContent = msg;
        errorDisplay.appendChild(p);
        if (fullBody && fullBody.length > 0) {
            const pre = document.createElement('pre');
            pre.className = 'bg-dark border border-danger rounded p-2 mt-2 text-start small';
            pre.style.maxHeight = '400px';
            pre.style.overflow = 'auto';
            pre.style.whiteSpace = 'pre-wrap';
            pre.style.wordBreak = 'break-all';
            pre.textContent = fullBody;
            errorDisplay.appendChild(pre);
        }
        errorDisplay.classList.remove('d-none');
        resultSection.classList.add('d-none');
    }

    function hideError() {
        errorDisplay.classList.add('d-none');
    }

    function setLoading(loading) {
        loadingDisplay.classList.toggle('d-none', !loading);
        submitBtn.disabled = loading;
        const spinner = submitBtn.querySelector('.spinner-border');
        const btnText = submitBtn.querySelector('.btn-text');
        if (spinner) spinner.classList.toggle('d-none', !loading);
        if (btnText) btnText.textContent = loading ? 'Searching…' : 'Ask';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatAnswer(text) {
        return escapeHtml(text)
            .replace(/\n/g, '<br>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*]+)\*/g, '<em>$1</em>')
            .replace(/\[([^\]]+),\s*Page\s*(\d+)\]/g, '<cite class="laws-cite">[$1, Page $2]</cite>');
    }

    function renderResult(data, rawQuestion) {
        hideError();
        resultSection.classList.remove('d-none');
        lastUserQuestion = rawQuestion || '';
        lastAnswer = data.answer || '';

        answerDisplay.innerHTML = formatAnswer(data.answer || 'No answer returned.');
        metaDisplay.textContent = data.ai_model ? `Powered by ${data.ai_model}` : '';

        sourcesDisplay.innerHTML = '';
        if (data.sources && data.sources.length > 0) {
            const list = document.createElement('ul');
            list.className = 'list-unstyled mb-0';
            data.sources.forEach(function (s) {
                const li = document.createElement('li');
                li.className = 'mb-1';
                li.textContent = `${s.book} (Page ${s.page}) — ${s.category}, ${s.system}`;
                list.appendChild(li);
            });
            const heading = document.createElement('h4');
            heading.className = 'h6 text-light mb-2';
            heading.textContent = 'Sources';
            sourcesDisplay.appendChild(heading);
            sourcesDisplay.appendChild(list);
        }

        moreInfoBtn.classList.remove('d-none');
    }

    function postQuery(payload) {
        return fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
    }

    function handleSubmit(e) {
        e.preventDefault();
        const question = questionInput.value.trim();
        if (!question) return;

        setLoading(true);
        hideError();

        const payload = { question: question };
        if (moreInfoBtn.classList.contains('active-follow-up') && lastUserQuestion && lastAnswer) {
            payload.previous_question = lastUserQuestion;
            payload.previous_answer = lastAnswer;
        }

        postQuery(payload)
            .then(function (res) {
                return res.text().then(function (text) {
                    var data = {};
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (_) {}
                    if (!res.ok) {
                        data.success = false;
                        if (!data.error) { data.error = 'Request failed: ' + res.status; }
                    }
                    return data;
                });
            })
            .then(function (data) {
                setLoading(false);
                if (data.success) {
                    renderResult(data, question);
                    questionInput.value = '';
                    moreInfoBtn.classList.remove('active-follow-up');
                } else {
                    var errMsg = data.error || 'An error occurred.';
                    if (data.tried_exe) { errMsg += ' Tried: ' + data.tried_exe; }
                    showError(errMsg, data.error_response_body);
                }
            })
            .catch(function (err) {
                setLoading(false);
                showError(err.message || 'Network or server error. Check console.');
            });
    }

    function handleMoreInfo() {
        moreInfoBtn.classList.add('active-follow-up');
        questionInput.placeholder = 'e.g. Can you give an example? What happens if…?';
        questionInput.focus();
    }

    if (form) form.addEventListener('submit', handleSubmit);
    if (moreInfoBtn) moreInfoBtn.addEventListener('click', handleMoreInfo);
})();
