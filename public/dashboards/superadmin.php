<?php
/**
 * Superadmin Dashboard
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../includes/Auth.php';

\Auth::requireRole('superadmin');

$session = \Auth::getSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard</title>
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
        .sidebar li {
            margin-bottom: 8px;
        }
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
        .toggle-switch {
            display: inline-block;
            width: 50px;
            height: 24px;
            background: #ccc;
            border-radius: 12px;
            cursor: pointer;
            position: relative;
            transition: 0.3s;
        }
        .toggle-switch.on { background: #27ae60; }
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: 0.3s;
        }
        .toggle-switch.on::after { left: 28px; }
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
        <h1>🔐 Superadmin Dashboard</h1>
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
                <li><button class="nav-btn" data-section="system">System Control</button></li>
                <li><button class="nav-btn active" data-section="admins">Manage Admins</button></li>
                <li><button class="nav-btn" data-section="countries">Manage Countries</button></li>
                <li><button class="nav-btn" data-section="target-urls">Target URLs</button></li>
                <li><button class="nav-btn" data-section="tags">Manage Tags</button></li>
                <li><button class="nav-btn" data-section="logs">Audit Logs</button></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- System Control -->
            <div class="section" id="system">
                <h2>System Control</h2>
                <div class="card">
                    <div class="form-group">
                        <label>Global System Status</label>
                        <div class="toggle-switch on" id="systemToggle"></div>
                        <p style="margin-top:10px; color:#666; font-size:14px;">
                            <span id="statusText">System is currently ON</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Manage Admins -->
            <div class="section active" id="admins">
                <h2>Manage Admins</h2>

                <div class="card">
                    <h3>Create New Admin</h3>
                    <form id="createAdminForm">
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
                        <button type="submit">Create Admin</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Existing Admins</h3>
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
                        <tbody id="adminsList">
                            <tr><td colspan="6">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Manage Countries -->
            <div class="section" id="countries">
                <h2>Global Country Management</h2>
                <div class="card">
                    <form id="countriesForm">
                        <div class="form-group">
                            <label>Countries (ISO alpha-2 codes, comma-separated)</label>
                            <textarea name="countries" placeholder="US, GB, CA, DE..." required></textarea>
                            <small style="color:#999;">Example: US, GB, CA, DE, FR, IT, ES</small>
                        </div>
                        <button type="submit">Update Countries</button>
                    </form>
                </div>
            </div>

            <!-- Target URLs -->
            <div class="section" id="target-urls">
                <h2>Global Target URLs</h2>
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

            <!-- Tags -->
            <div class="section" id="tags">
                <h2>Manage Tags</h2>
                <div class="card">
                    <h3>Create Tag</h3>
                    <form id="createTagForm">
                        <div class="form-group">
                            <label>Tag Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <button type="submit">Create Tag</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Existing Tags</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tagsList">
                            <tr><td colspan="3">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audit Logs -->
            <div class="section" id="logs">
                <h2>Audit Logs</h2>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Resource</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="logsList">
                            <tr><td colspan="5">Loading...</td></tr>
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

        // System toggle
        document.getElementById('systemToggle').addEventListener('click', function() {
            this.classList.toggle('on');
            const status = this.classList.contains('on') ? 'ON' : 'OFF';
            document.getElementById('statusText').textContent = `System is currently ${status}`;
        });

        // Load initial data
        loadAdmins();
        loadTags();

        async function loadAdmins() {
            const response = await fetch('/api/admins.php?action=list');
            const data = await response.json();
            const tbody = document.getElementById('adminsList');
            tbody.innerHTML = '';

            if (data.admins && data.admins.length) {
                data.admins.forEach(admin => {
                    const tags = admin.tags.map(t => t.name).join(', ');
                    tbody.innerHTML += `
                        <tr>
                            <td>${admin.username}</td>
                            <td>${admin.email}</td>
                            <td>${tags || 'None'}</td>
                            <td>${new Date(admin.created_at).toLocaleDateString()}</td>
                            <td><span class="status-badge status-${admin.is_active ? 'active' : 'inactive'}">
                                ${admin.is_active ? 'Active' : 'Inactive'}
                            </span></td>
                            <td>
                                <button class="btn btn-small" onclick="resetAdminPassword(${admin.id})">Reset Pass</button>
                                <button class="btn btn-danger btn-small" onclick="deleteAdmin(${admin.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6">No admins found</td></tr>';
            }
        }

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

            const tbody = document.getElementById('tagsList');
            tbody.innerHTML = '';
            if (data.tags && data.tags.length) {
                data.tags.forEach(tag => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${tag.name}</td>
                            <td>${new Date(tag.created_at).toLocaleDateString()}</td>
                            <td><button class="btn btn-danger btn-small" onclick="deleteTag(${tag.id})">Delete</button></td>
                        </tr>
                    `;
                });
            }
        }

        document.getElementById('createAdminForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);
            const tags = Array.from(form.getAll('tags')).map(Number);

            const response = await fetch('/api/admins.php?action=create', {
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
                alert('Admin created successfully');
                e.target.reset();
                loadAdmins();
            } else {
                alert('Error creating admin');
            }
        });

        document.getElementById('createTagForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(e.target);

            const response = await fetch('/api/tags.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: form.get('name') })
            });

            if (response.ok) {
                alert('Tag created successfully');
                e.target.reset();
                loadTags();
            } else {
                alert('Error creating tag');
            }
        });

        async function deleteAdmin(id) {
            if (confirm('Delete this admin?')) {
                const response = await fetch(`/api/admins.php?action=delete&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadAdmins();
                }
            }
        }

        async function deleteTag(id) {
            if (confirm('Delete this tag?')) {
                const response = await fetch(`/api/tags.php?action=delete&id=${id}`, { method: 'DELETE' });
                if (response.ok) {
                    loadTags();
                }
            }
        }

        function resetAdminPassword(id) {
            const newPass = prompt('Enter new password:');
            if (newPass && newPass.length >= 8) {
                fetch('/api/admins.php?action=reset_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_id: id, new_password: newPass })
                }).then(r => {
                    if (r.ok) alert('Password reset');
                });
            }
        }
    </script>
</body>
</html>
