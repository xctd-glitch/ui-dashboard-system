<?php
/**
 * Admin (Pre-admin) Dashboard
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../includes/Auth.php';

\Auth::requireRole('admin');

$session = \Auth::getSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .nav-menu a:hover { background: rgba(255,255,255,0.1); border-radius: 4px; }
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
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👨‍💼 Admin Dashboard</h1>
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
                <li><button class="nav-btn active" data-section="users">Manage Users</button></li>
                <li><button class="nav-btn" data-section="domains">Parked Domains</button></li>
                <li><button class="nav-btn" data-section="countries">Countries</button></li>
                <li><button class="nav-btn" data-section="target-urls">Target URLs</button></li>
                <li><button class="nav-btn" data-section="logs">Logs</button></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Manage Users -->
            <div class="section active" id="users">
                <h2>Manage Users</h2>

                <div class="card">
                    <h3>Create New User</h3>
                    <form id="createUserForm">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Tags (select multiple)</label>
                            <select name="tags" multiple style="height: auto;">
                                <!-- Loaded via JavaScript -->
                            </select>
                        </div>
                        <button type="submit">Create User</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Existing Users</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Tags</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersList">
                            <tr><td colspan="6">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Parked Domains -->
            <div class="section" id="domains">
                <h2>Parked Domains</h2>

                <div class="card">
                    <h3>Add Domains</h3>
                    <form id="addDomainsForm">
                        <div class="form-group">
                            <label>Domains (one per line)</label>
                            <textarea name="domains_text" placeholder="domain1.com&#10;domain2.com&#10;domain3.com" required></textarea>
                            <small style="color:#999;">Each line becomes one domain. Wildcard handling is automatic.</small>
                        </div>
                        <button type="submit">Add Domains</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Existing Domains (Max 10)</h3>
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

            <!-- Countries -->
            <div class="section" id="countries">
                <h2>Countries</h2>
                <div class="card">
                    <h3>Manage Countries</h3>
                    <form id="countriesForm">
                        <div class="form-group">
                            <label>Countries (ISO alpha-2 codes, comma-separated)</label>
                            <textarea name="countries_text" placeholder="US, GB, CA, DE..." required></textarea>
                            <small style="color:#999;">Example: US, GB, CA, DE, FR, IT, ES</small>
                        </div>
                        <button type="submit">Update Countries</button>
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

            <!-- Logs -->
            <div class="section" id="logs">
                <h2>Activity Logs</h2>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Action</th>
                                <th>Resource</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="logsList">
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
            });
        });

        // Load initial data
        loadUsers();
        loadDomains();
        loadURLs();
        loadCountries();
        loadTags();

        async function loadTags() {
            const response = await fetch('/api/tags.php?action=list');
            const data = await response.json();
            const select = document.querySelector('select[name="tags"]');
            select.innerHTML = '';

            if (data.tags && data.tags.length) {
                data.tags.forEach(tag => {
                    const option = document.createElement('option');
                    option.value = tag.id;
                    option.textContent = tag.name;
                    select.appendChild(option);
                });
            }
        }

        async function loadUsers() {
            const response = await fetch('/api/users.php?action=list');
            const data = await response.json();
            const tbody = document.getElementById('usersList');
            tbody.innerHTML = '';

            if (data.users && data.users.length) {
                data.users.forEach(user => {
                    const tags = user.tags.map(t => t.name).join(', ');
                    tbody.innerHTML += `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${tags || 'None'}</td>
                            <td>${new Date(user.created_at).toLocaleDateString()}</td>
                            <td><span class="status-badge status-${user.is_active ? 'active' : 'inactive'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span></td>
                            <td>
                                <button class="btn btn-small" onclick="resetUserPassword(${user.id})">Reset Pass</button>
                                <button class="btn btn-danger btn-small" onclick="deleteUser(${user.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6">No users found</td></tr>';
            }
        }

        async function loadDomains() {
            const response = await fetch('/api/domains.php?action=admin_domains');
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
            const response = await fetch('/api/target_urls.php?action=admin_urls');
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
            const response = await fetch('/api/countries.php?action=admin_countries');
            const data = await response.json();
            if (data.countries && data.countries.length) {
                document.querySelector('textarea[name="countries_text"]').value = data.countries.join(', ');
            }
        }

        document.getElementById('createUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);
            const tags = Array.from(form.getAll('tags')).map(Number);

            const response = await fetch('/api/users.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: form.get('username'),
                    email: form.get('email'),
                    password: form.get('password'),
                    tags: tags
                })
            });

            if (response.ok) {
                alert('User created successfully');
                e.target.reset();
                loadUsers();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.error || 'Unknown error'));
            }
        });

        document.getElementById('addDomainsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);

            const response = await fetch('/api/domains.php?action=add_admin_domains', {
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

            const response = await fetch('/api/countries.php?action=update_admin_countries', {
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

            const response = await fetch('/api/target_urls.php?action=add_admin_url', {
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

        async function deleteUser(id) {
            if (confirm('Delete this user?')) {
                const response = await fetch(`/api/users.php?action=delete&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadUsers();
                }
            }
        }

        async function deleteDomain(id) {
            if (confirm('Delete this domain?')) {
                const response = await fetch(`/api/domains.php?action=delete_admin_domain&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadDomains();
                }
            }
        }

        async function deleteURL(id) {
            if (confirm('Delete this URL?')) {
                const response = await fetch(`/api/target_urls.php?action=delete_admin_url&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadURLs();
                }
            }
        }

        function resetUserPassword(id) {
            const newPass = prompt('Enter new password:');
            if (newPass && newPass.length >= 8) {
                fetch('/api/users.php?action=reset_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: id, new_password: newPass })
                }).then(r => {
                    if (r.ok) alert('Password reset');
                    else alert('Error resetting password');
                });
            }
        }
    </script>
</body>
</html>
