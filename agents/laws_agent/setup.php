<?php
/**
 * Web-Based RAG Setup Interface
 * Navigate to: http://192.168.0.155/agents/laws_agent/setup.php
 */

session_start();
require_once __DIR__ . '/../../includes/connect.php';

// Simple password protection
$SETUP_PASSWORD = 'vbn2024'; // Change this!

$authenticated = $_SESSION['setup_authenticated'] ?? false;

if (isset($_POST['password'])) {
    if ($_POST['password'] === $SETUP_PASSWORD) {
        $_SESSION['setup_authenticated'] = true;
        $authenticated = true;
    } else {
        $error = "Invalid password";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['setup_authenticated']);
    header('Location: setup.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAG Setup - VbN Laws Agent</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #1a0f0f;
            color: #ccc;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #8b0000;
            border-radius: 8px;
            padding: 30px;
        }
        h1 {
            color: #8b0000;
            margin-bottom: 10px;
        }
        h2 {
            color: #8b0000;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .step {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .step-number {
            display: inline-block;
            background: #8b0000;
            color: #fff;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }
        button {
            background: #8b0000;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        button:hover { background: #a00000; }
        button:disabled {
            background: #555;
            cursor: not-allowed;
        }
        .success {
            background: rgba(0, 150, 0, 0.2);
            border: 1px solid #0f0;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            background: rgba(150, 0, 0, 0.2);
            border: 1px solid #f00;
            color: #ff6b6b;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            background: rgba(150, 150, 0, 0.2);
            border: 1px solid #ff0;
            color: #ffff6b;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .output {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        input[type="file"], input[type="password"] {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #8b0000;
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
            margin: 10px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-badge.complete {
            background: rgba(0, 150, 0, 0.3);
            border: 1px solid #0f0;
            color: #0f0;
        }
        .status-badge.pending {
            background: rgba(150, 150, 0, 0.3);
            border: 1px solid #ff0;
            color: #ff0;
        }
        .status-badge.error {
            background: rgba(150, 0, 0, 0.3);
            border: 1px solid #f00;
            color: #f00;
        }
        .login-form {
            max-width: 400px;
            margin: 100px auto;
        }
    </style>
</head>
<body>

<?php if (!$authenticated): ?>
    <div class="container login-form">
        <h1>🔐 RAG Setup - Authentication</h1>
        <p style="margin: 20px 0;">Enter the setup password to continue.</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="password" name="password" placeholder="Setup Password" required autofocus>
            <button type="submit">Login</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 13px; color: #666;">
            Default password: vbn2024 (change in setup.php)
        </p>
    </div>
<?php else: ?>

<div class="container">
    <h1>🧛 RAG System Setup</h1>
    <p style="color: #666; margin-bottom: 20px;">
        Web-based setup for the VbN Laws Agent RAG system
        <a href="?logout" style="color: #8b0000; float: right;">Logout</a>
    </p>

    <h2>Setup Progress</h2>
    
    <div class="step">
        <span class="step-number">1</span>
        <strong>Database Tables</strong>
        <span class="status-badge" id="status-db">pending</span>
        <p style="margin: 10px 0 10px 40px; color: #999;">Create the required database tables for the RAG system</p>
        <button onclick="setupDatabase()" id="btn-db">Create Database Tables</button>
        <div id="output-db"></div>
    </div>

    <div class="step">
        <span class="step-number">2</span>
        <strong>Import Book Data</strong>
        <span class="status-badge" id="status-import">pending</span>
        <p style="margin: 10px 0 10px 40px; color: #999;">Upload and import your rag_documents.json file</p>
        <input type="file" id="jsonFile" accept=".json">
        <button onclick="importData()" id="btn-import">Import Data</button>
        <div id="output-import"></div>
    </div>

    <div class="step">
        <span class="step-number">3</span>
        <strong>Test System</strong>
        <span class="status-badge" id="status-test">pending</span>
        <p style="margin: 10px 0 10px 40px; color: #999;">Verify everything is working correctly</p>
        <button onclick="testSystem()" id="btn-test">Run Tests</button>
        <div id="output-test"></div>
    </div>

    <div class="step">
        <span class="step-number">4</span>
        <strong>Launch Laws Agent</strong>
        <span class="status-badge" id="status-launch">pending</span>
        <p style="margin: 10px 0 10px 40px; color: #999;">Open the Laws Agent interface</p>
        <button onclick="window.location.href='index.php'" id="btn-launch" disabled>
            Open Laws Agent
        </button>
    </div>

</div>

<script>
function updateStatus(step, status, message = '') {
    const badge = document.getElementById('status-' + step);
    badge.className = 'status-badge ' + status;
    badge.textContent = status;
    
    if (message) {
        const output = document.getElementById('output-' + step);
        output.innerHTML = '<div class="output">' + escapeHtml(message) + '</div>';
    }
}

function setupDatabase() {
    const btn = document.getElementById('btn-db');
    btn.disabled = true;
    btn.textContent = 'Creating...';
    updateStatus('db', 'pending', 'Creating database tables...');
    
    fetch('setup_api.php?action=setup_database')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatus('db', 'complete', data.message || 'Database tables created successfully!');
            } else {
                updateStatus('db', 'error', 'Error: ' + (data.error || 'Unknown error'));
                btn.disabled = false;
                btn.textContent = 'Retry';
            }
        })
        .catch(error => {
            updateStatus('db', 'error', 'Connection error: ' + error.message);
            btn.disabled = false;
            btn.textContent = 'Retry';
        });
}

function importData() {
    const fileInput = document.getElementById('jsonFile');
    const btn = document.getElementById('btn-import');
    
    if (!fileInput.files[0]) {
        alert('Please select a JSON file first!');
        return;
    }
    
    btn.disabled = true;
    btn.textContent = 'Importing...';
    updateStatus('import', 'pending', 'Reading file and importing data...');
    
    const formData = new FormData();
    formData.append('jsonFile', fileInput.files[0]);
    
    fetch('setup_api.php?action=import_data', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatus('import', 'complete', data.message || 'Data imported successfully!');
            document.getElementById('btn-launch').disabled = false;
            updateStatus('launch', 'complete');
        } else {
            updateStatus('import', 'error', 'Error: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = 'Retry Import';
        }
    })
    .catch(error => {
        updateStatus('import', 'error', 'Connection error: ' + error.message);
        btn.disabled = false;
        btn.textContent = 'Retry Import';
    });
}

function testSystem() {
    const btn = document.getElementById('btn-test');
    btn.disabled = true;
    btn.textContent = 'Testing...';
    updateStatus('test', 'pending', 'Running system tests...');
    
    fetch('setup_api.php?action=test_system')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatus('test', 'complete', data.message || 'All tests passed!');
            } else {
                updateStatus('test', 'error', 'Tests failed: ' + (data.error || 'Unknown error'));
            }
            btn.disabled = false;
            btn.textContent = 'Run Tests Again';
        })
        .catch(error => {
            updateStatus('test', 'error', 'Connection error: ' + error.message);
            btn.disabled = false;
            btn.textContent = 'Run Tests Again';
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Check current status on load
window.onload = function() {
    fetch('setup_api.php?action=check_status')
        .then(response => response.json())
        .then(data => {
            if (data.database_ready) {
                updateStatus('db', 'complete', 'Database tables exist');
                document.getElementById('btn-db').disabled = true;
                document.getElementById('btn-db').textContent = 'Already Created';
            }
            if (data.data_imported) {
                updateStatus('import', 'complete', 'Data already imported (' + data.document_count + ' documents)');
                document.getElementById('btn-import').disabled = true;
                document.getElementById('btn-import').textContent = 'Already Imported';
                document.getElementById('btn-launch').disabled = false;
                updateStatus('launch', 'complete');
            }
        });
};
</script>

<?php endif; ?>

</body>
</html>
