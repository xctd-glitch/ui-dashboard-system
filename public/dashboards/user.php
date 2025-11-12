<?php
/**
 * User Dashboard
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../includes/Auth.php';

\Auth::requireRole('user');

$session = \Auth::getSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 24px; }
        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .nav-menu a { color: white; text-decoration: none; padding: 8px 16px; }
        .logout-btn {
            background: #e74c3c;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            color: white;
        }
        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: calc(100vh - 60px);
        }
        .sidebar {
            background: white;
            border-right: 1px solid #ddd;
            padding: 20px;
        }
        .sidebar h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sidebar ul { list-style: none; }
        .sidebar li { margin-bottom: 8px; }
        .sidebar button {
            width: 100%;
            text-align: left;
            padding: 10px;
            border: none;
            background: none;
            cursor: pointer;
            color: #333;
            border-radius: 4px;
            font-size: 14px;
            transition: 0.2s;
        }
        .sidebar button:hover { background: #f0f0f0; }
        .sidebar button.active { background: #667eea; color: white; }
        .main-content {
            padding: 30px;
            overflow-y: auto;
        }
        .section { display: none; }
        .section.active { display: block; }
        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        button[type="submit"],
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }
        button[type="submit"]:hover,
        .btn:hover { background: #5568d3; }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover { background: #c0392b; }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: 600;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        table tr:hover { background: #fafafa; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👤 User Dashboard</h1>
        <div class="nav-menu">
            <span>Welcome, <?php echo htmlspecialchars($session['username']); ?></span>
            <form method="POST" action="/api/auth.php?action=logout" style="display:inline;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <h3>Menu</h3>
            <ul>
                <li><button class="nav-btn active" data-section="overview">Overview</button></li>
                <li><button class="nav-btn" data-section="domains">Domains</button></li>
                <li><button class="nav-btn" data-section="routing">Routing Config</button></li>
                <li><button class="nav-btn" data-section="target-urls">Target URLs</button></li>
                <li><button class="nav-btn" data-section="rules">Redirect Rules</button></li>
                <li><button class="nav-btn" data-section="metrics">Metrics</button></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Overview -->
            <div class="section active" id="overview">
                <h2>Overview</h2>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalClicks">0</div>
                        <div class="stat-label">Total Clicks</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="uniqueIPs">0</div>
                        <div class="stat-label">Unique IPs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="countriesCount">0</div>
                        <div class="stat-label">Countries</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="domainsCount">0</div>
                        <div class="stat-label">Domains</div>
                    </div>
                </div>
            </div>

            <!-- Domains -->
            <div class="section" id="domains">
                <h2>Parked Domains</h2>

                <div class="card">
                    <h3>Add Domains</h3>
                    <form id="addDomainsForm">
                        <div class="form-group">
                            <label>Domains (one per line)</label>
                            <textarea name="domains_text" placeholder="domain1.com&#10;domain2.com&#10;domain3.com" required></textarea>
                            <small style="color:#999;">Each line becomes one domain. Max 10 total domains.</small>
                        </div>
                        <button type="submit">Add Domains</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Your Domains</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Cloudflare Sync</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="domainsList">
                            <tr><td colspan="4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Routing Config -->
            <div class="section" id="routing">
                <h2>Routing Configuration</h2>
                <div class="card">
                    <h3>Device Scope</h3>
                    <form id="deviceScopeForm">
                        <div class="form-group">
                            <label>Device Type</label>
                            <select name="device_scope" required>
                                <option value="ALL">All Devices</option>
                                <option value="WEB">Web Only</option>
                                <option value="WAP">Mobile Only</option>
                            </select>
                        </div>
                        <button type="submit">Update Device Scope</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Countries</h3>
                    <form id="countriesForm">
                        <div class="form-group">
                            <label>Countries (ISO alpha-2 codes, comma-separated)</label>
                            <textarea name="countries_text" placeholder="US, GB, CA, DE..." required></textarea>
                            <small style="color:#999;">Example: US, GB, CA, DE, FR, IT, ES</small>
                        </div>
                        <button type="submit">Update Countries</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Domain Selection</h3>
                    <form id="domainSelectionForm">
                        <div class="form-group">
                            <label>Selection Type</label>
                            <select name="selection_type" required onchange="updateSelectionUI()">
                                <option value="random_global">Random Global Domain</option>
                                <option value="random_user">Random User Domain</option>
                                <option value="specific">Specific Domain</option>
                            </select>
                        </div>
                        <div class="form-group" id="specificDomainGroup" style="display:none;">
                            <label>Specific Domain</label>
                            <input type="text" name="specific_domain" placeholder="example.com">
                        </div>
                        <button type="submit">Update Selection</button>
                    </form>
                </div>
            </div>

            <!-- Target URLs -->
            <div class="section" id="target-urls">
                <h2>Target URLs</h2>
                <div class="card">
                    <h3>Add Target URL</h3>
                    <form id="addUrlForm">
                        <div class="form-group">
                            <label>URL</label>
                            <input type="text" name="url" placeholder="https://..." required>
                        </div>
                        <button type="submit">Add URL</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Existing URLs</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="urlsList">
                            <tr><td colspan="3">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Redirect Rules -->
            <div class="section" id="rules">
                <h2>Redirect Rules</h2>

                <div class="card">
                    <h3>Create Rule</h3>
                    <form id="createRuleForm">
                        <div class="form-group">
                            <label>Rule Type</label>
                            <select name="rule_type" required onchange="updateRuleUI()">
                                <option value="mute_unmute">Mute/Unmute Cycle</option>
                                <option value="random_route">Random Route</option>
                                <option value="static_route">Static Route</option>
                            </select>
                        </div>
                        <div class="form-group" id="muteOnGroup" style="display:none;">
                            <label>On Duration (minutes)</label>
                            <input type="number" name="mute_duration_on" value="2" min="1">
                        </div>
                        <div class="form-group" id="muteOffGroup" style="display:none;">
                            <label>Off Duration (minutes)</label>
                            <input type="number" name="mute_duration_off" value="5" min="1">
                        </div>
                        <div class="form-group" id="targetGroup" style="display:none;">
                            <label>Target URL</label>
                            <input type="text" name="target_url" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_enabled" checked>
                                Enabled
                            </label>
                        </div>
                        <button type="submit">Create Rule</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Existing Rules</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Target/Settings</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rulesList">
                            <tr><td colspan="4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Metrics -->
            <div class="section" id="metrics">
                <h2>Metrics & Analytics</h2>

                <div class="card">
                    <h3>Date Range</h3>
                    <form id="dateRangeForm">
                        <div class="form-group">
                            <label>Range</label>
                            <select name="range" onchange="loadMetrics()">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="weekly">Last 7 Days</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="form-group" id="customDateGroup" style="display:none;">
                            <label>Start Date</label>
                            <input type="date" name="start_date">
                            <label>End Date</label>
                            <input type="date" name="end_date">
                            <button type="button" onclick="loadMetrics()">Load</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3>Clicks by Country</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>Clicks</th>
                                <th>Unique IPs</th>
                            </tr>
                        </thead>
                        <tbody id="countryMetrics">
                            <tr><td colspan="3">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h3>Clicks by Device</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Device Type</th>
                                <th>Clicks</th>
                                <th>Unique IPs</th>
                            </tr>
                        </thead>
                        <tbody id="deviceMetrics">
                            <tr><td colspan="3">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h3>Top IPs</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Clicks</th>
                                <th>Device</th>
                                <th>Country</th>
                            </tr>
                        </thead>
                        <tbody id="ipMetrics">
                            <tr><td colspan="4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navigation
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.section).classList.add('active');

                if (btn.dataset.section === 'metrics') {
                    loadMetrics();
                }
            });
        });

        // Load initial data
        loadStats();
        loadDomains();
        loadURLs();
        loadCountries();
        loadDeviceScope();
        loadRules();
        loadMetrics();

        function updateRuleUI() {
            const type = document.querySelector('select[name="rule_type"]').value;
            document.getElementById('muteOnGroup').style.display = type === 'mute_unmute' ? 'block' : 'none';
            document.getElementById('muteOffGroup').style.display = type === 'mute_unmute' ? 'block' : 'none';
            document.getElementById('targetGroup').style.display = type === 'static_route' ? 'block' : 'none';
        }

        function updateSelectionUI() {
            const type = document.querySelector('select[name="selection_type"]').value;
            document.getElementById('specificDomainGroup').style.display = type === 'specific' ? 'block' : 'none';
        }

        async function loadStats() {
            const response = await fetch('/api/metrics.php?action=summary&range=today');
            const data = await response.json();
            document.getElementById('totalClicks').textContent = data.total_clicks || 0;
            document.getElementById('uniqueIPs').textContent = data.unique_ips || 0;
            document.getElementById('countriesCount').textContent = data.countries || 0;

            const domsResponse = await fetch('/api/domains.php?action=user_domains');
            const domsData = await domsResponse.json();
            document.getElementById('domainsCount').textContent = domsData.domains ? domsData.domains.length : 0;
        }

        async function loadDomains() {
            const response = await fetch('/api/domains.php?action=user_domains');
            const data = await response.json();
            const tbody = document.getElementById('domainsList');
            tbody.innerHTML = '';

            if (data.domains && data.domains.length) {
                data.domains.forEach(domain => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${domain.domain}</td>
                            <td>${domain.cloudflare_synced ? '✓ Synced' : '✗ Pending'}</td>
                            <td>${new Date(domain.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-danger btn-small" onclick="deleteDomain(${domain.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4">No domains added yet</td></tr>';
            }
        }

        async function loadURLs() {
            const response = await fetch('/api/target_urls.php?action=user_urls');
            const data = await response.json();
            const tbody = document.getElementById('urlsList');
            tbody.innerHTML = '';

            if (data.urls && data.urls.length) {
                data.urls.forEach(url => {
                    tbody.innerHTML += `
                        <tr>
                            <td style="word-break: break-all;">${url.url}</td>
                            <td>${new Date(url.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-danger btn-small" onclick="deleteURL(${url.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3">No URLs added yet</td></tr>';
            }
        }

        async function loadCountries() {
            const response = await fetch('/api/countries.php?action=user_countries');
            const data = await response.json();
            if (data.countries && data.countries.length) {
                document.querySelector('textarea[name="countries_text"]').value = data.countries.join(', ');
            }
        }

        async function loadDeviceScope() {
            const response = await fetch('/api/countries.php?action=user_countries');
            const data = await response.json();
            // This would fetch device scope if we had that endpoint
        }

        async function loadRules() {
            const response = await fetch('/api/rules.php?action=list');
            const data = await response.json();
            const tbody = document.getElementById('rulesList');
            tbody.innerHTML = '';

            if (data.rules && data.rules.length) {
                data.rules.forEach(rule => {
                    let details = '';
                    if (rule.rule_type === 'mute_unmute') {
                        details = `${rule.mute_duration_on}m on, ${rule.mute_duration_off}m off`;
                    } else if (rule.rule_type === 'static_route') {
                        details = rule.target_url || 'N/A';
                    } else {
                        details = 'Random';
                    }

                    tbody.innerHTML += `
                        <tr>
                            <td>${rule.rule_type}</td>
                            <td>${details}</td>
                            <td>${rule.is_enabled ? '✓ Enabled' : '✗ Disabled'}</td>
                            <td>
                                <button class="btn btn-danger btn-small" onclick="deleteRule(${rule.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4">No rules created yet</td></tr>';
            }
        }

        async function loadMetrics() {
            const range = document.querySelector('select[name="range"]').value;

            // Load by country
            const countryResponse = await fetch(`/api/metrics.php?action=by_country&range=${range}`);
            const countryData = await countryResponse.json();
            const countryTbody = document.getElementById('countryMetrics');
            countryTbody.innerHTML = '';
            if (countryData.data && countryData.data.length) {
                countryData.data.forEach(item => {
                    countryTbody.innerHTML += `
                        <tr>
                            <td>${item.country_iso || 'Unknown'}</td>
                            <td>${item.clicks}</td>
                            <td>${item.unique_ips}</td>
                        </tr>
                    `;
                });
            } else {
                countryTbody.innerHTML = '<tr><td colspan="3">No data</td></tr>';
            }

            // Load by device
            const deviceResponse = await fetch(`/api/metrics.php?action=by_device&range=${range}`);
            const deviceData = await deviceResponse.json();
            const deviceTbody = document.getElementById('deviceMetrics');
            deviceTbody.innerHTML = '';
            if (deviceData.data && deviceData.data.length) {
                deviceData.data.forEach(item => {
                    deviceTbody.innerHTML += `
                        <tr>
                            <td>${item.device_type}</td>
                            <td>${item.clicks}</td>
                            <td>${item.unique_ips}</td>
                        </tr>
                    `;
                });
            } else {
                deviceTbody.innerHTML = '<tr><td colspan="3">No data</td></tr>';
            }

            // Load by IP
            const ipResponse = await fetch(`/api/metrics.php?action=by_ip&range=${range}`);
            const ipData = await ipResponse.json();
            const ipTbody = document.getElementById('ipMetrics');
            ipTbody.innerHTML = '';
            if (ipData.data && ipData.data.length) {
                ipData.data.forEach(item => {
                    ipTbody.innerHTML += `
                        <tr>
                            <td>${item.ip_address}</td>
                            <td>${item.clicks}</td>
                            <td>${item.device_type}</td>
                            <td>${item.country_iso || 'Unknown'}</td>
                        </tr>
                    `;
                });
            } else {
                ipTbody.innerHTML = '<tr><td colspan="4">No data</td></tr>';
            }
        }

        // Form submissions
        document.getElementById('addDomainsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);

            const response = await fetch('/api/domains.php?action=add_user_domains', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ domains_text: form.get('domains_text') })
            });

            if (response.ok) {
                alert('Domains added successfully');
                e.target.reset();
                loadDomains();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.error || 'Unknown error'));
            }
        });

        document.getElementById('countriesForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);

            const response = await fetch('/api/countries.php?action=update_user_countries', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ countries_text: form.get('countries_text') })
            });

            if (response.ok) {
                alert('Countries updated successfully');
            } else {
                alert('Error updating countries');
            }
        });

        document.getElementById('addUrlForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);

            const response = await fetch('/api/target_urls.php?action=add_user_url', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: form.get('url') })
            });

            if (response.ok) {
                alert('URL added successfully');
                e.target.reset();
                loadURLs();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.error || 'Unknown error'));
            }
        });

        document.getElementById('createRuleForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);

            const response = await fetch('/api/rules.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    rule_type: form.get('rule_type'),
                    mute_duration_on: parseInt(form.get('mute_duration_on')),
                    mute_duration_off: parseInt(form.get('mute_duration_off')),
                    target_url: form.get('target_url'),
                    is_enabled: form.has('is_enabled') ? 1 : 0
                })
            });

            if (response.ok) {
                alert('Rule created successfully');
                e.target.reset();
                loadRules();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.error || 'Unknown error'));
            }
        });

        async function deleteDomain(id) {
            if (confirm('Delete this domain?')) {
                const response = await fetch(`/api/domains.php?action=delete_user_domain&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadDomains();
                }
            }
        }

        async function deleteURL(id) {
            if (confirm('Delete this URL?')) {
                const response = await fetch(`/api/target_urls.php?action=delete_user_url&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadURLs();
                }
            }
        }

        async function deleteRule(id) {
            if (confirm('Delete this rule?')) {
                const response = await fetch(`/api/rules.php?action=delete&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadRules();
                }
            }
        }

        // Initialize rule UI
        updateRuleUI();
        updateSelectionUI();
    </script>
</body>
</html>
