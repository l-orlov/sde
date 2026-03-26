<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fill EN fields</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f4f6f9;
            color: #333;
            padding: 40px 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            padding: 36px 40px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }
        h1 { font-size: 22px; margin-bottom: 6px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 28px; }

        #btn-start {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 28px;
            font-size: 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background .2s;
        }
        #btn-start:hover { background: #1d4ed8; }
        #btn-start:disabled { background: #93c5fd; cursor: not-allowed; }

        #status-block { margin-top: 28px; display: none; }

        .progress-bar-wrap {
            background: #e5e7eb;
            border-radius: 6px;
            height: 10px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: #2563eb;
            width: 0%;
            transition: width .3s;
            border-radius: 6px;
        }

        #counter { font-size: 14px; color: #555; margin-bottom: 16px; }

        #log {
            max-height: 360px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 13px;
            font-family: monospace;
            background: #fafafa;
        }
        .log-ok   { color: #16a34a; margin-bottom: 4px; }
        .log-skip { color: #d97706; margin-bottom: 4px; }
        .log-err  { color: #dc2626; margin-bottom: 4px; }
        .log-info { color: #2563eb; margin-bottom: 4px; }

        #summary {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            display: none;
        }
        .summary-ok  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .summary-err { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    </style>
</head>
<body>
<div class="container">
    <h1>Fill EN fields</h1>
    <p class="subtitle">Translates missing English fields for all companies and their products via Gemini API.</p>

    <button id="btn-start" onclick="startFill()">Fill EN fields</button>

    <div id="status-block">
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="progress-bar"></div>
        </div>
        <div id="counter">Preparing...</div>
        <div id="log"></div>
        <div id="summary"></div>
    </div>
</div>

<script>
async function startFill() {
    const btn = document.getElementById('btn-start');
    btn.disabled = true;

    const statusBlock = document.getElementById('status-block');
    const log         = document.getElementById('log');
    const counter     = document.getElementById('counter');
    const bar         = document.getElementById('progress-bar');
    const summary     = document.getElementById('summary');

    statusBlock.style.display = 'block';
    summary.style.display = 'none';
    log.innerHTML = '';

    addLog('info', 'Loading list of companies with missing EN fields...');

    let companies;
    try {
        const res  = await fetch('api.php?action=list');
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed to load list');
        companies = data.companies;
    } catch (e) {
        addLog('err', 'Error: ' + e.message);
        btn.disabled = false;
        return;
    }

    if (companies.length === 0) {
        addLog('info', 'No companies with missing EN fields found. Nothing to do.');
        counter.textContent = 'Done — all fields already filled.';
        bar.style.width = '100%';
        btn.disabled = false;
        return;
    }

    addLog('info', 'Found ' + companies.length + ' company(-ies) to process.');

    const total = companies.length;
    let done = 0, errors = 0;

    for (const company of companies) {
        counter.textContent = 'Processing ' + done + ' / ' + total + '...';
        bar.style.width = Math.round(done / total * 100) + '%';

        try {
            const body = new URLSearchParams({ company_id: company.id });
            const res  = await fetch('api.php?action=process', { method: 'POST', body });
            const data = await res.json();

            if (!data.success) throw new Error(data.error || 'Unknown error');

            if (data.filled) {
                addLog('ok', '✓ #' + company.id + ' — ' + escHtml(company.name));
            } else {
                addLog('skip', '⚠ #' + company.id + ' — ' + escHtml(company.name) + ' (processed but name_en still empty — check Gemini API key)');
            }
        } catch (e) {
            addLog('err', '✗ #' + company.id + ' — ' + escHtml(company.name) + ': ' + e.message);
            errors++;
        }

        done++;
        // Small pause to avoid rate limiting on Gemini API
        await sleep(400);
    }

    bar.style.width = '100%';
    counter.textContent = 'Done: ' + done + ' / ' + total + ' processed.';

    summary.style.display = 'block';
    if (errors === 0) {
        summary.className = 'summary-ok';
        summary.textContent = '✓ All ' + total + ' companies processed successfully.';
    } else {
        summary.className = 'summary-err';
        summary.textContent = '✗ Completed with errors: ' + errors + ' failed out of ' + total + '. Check the log above.';
    }

    btn.disabled = false;
}

function addLog(type, text) {
    const log = document.getElementById('log');
    const div = document.createElement('div');
    div.className = 'log-' + type;
    div.textContent = text;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}
</script>
</body>
</html>
