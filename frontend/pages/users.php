<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .user-badge.admin { background: #dc3545; }
        .user-badge.manager { background: #28a745; }
        .user-badge.user { background: #6c757d; }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .logout-btn:hover { background: #c82333; }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .filters {
            margin-bottom: 1rem;
        }

        .search-bar {
            padding: 8px;
            width: 100%;
            max-width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .users-table-container {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .role-select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }

        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }

        .btn-small { padding: 4px 8px; font-size: 0.8rem; }

        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .access-denied {
            text-align: center;
            padding: 3rem;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            border-radius: 8px;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <ul class="nav-links" id="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li id="manage-contract-nav" style="display: none;">
                <a href="manage_contract.php">Manage Contract</a>
            </li>
            <li id="users-nav" style="display: none;">
                <a href="users.php" class="active">Users</a>
            </li>
        </ul>

        <div class="user-info" id="user-info">
            <span id="username-display">Loading...</span>
            <span id="role-badge" class="user-badge">admin</span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>

        <div class="nwr_logo">
            <img src="../images/nwr_logo.png" alt="NWR Logo" style="height:60px;">
        </div>
    </div>

    <div class="container">
        <h1>User Management</h1>

        <div id="message-area"></div>

        <div id="access-denied" class="access-denied" style="display: none;">
            <h2>Access Denied</h2>
            <p>You don't have permission to access this page. Only administrators can manage users.</p>
            <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>

        <div id="main-content" style="display: none;">
            <div class="filters">
                <input type="text" class="search-bar" placeholder="Search by Username or Email...">
                <button id="download-csv" class="btn btn-primary">Download Users as CSV</button>
            </div>

            <div class="users-table-container">
                <table class="users-table" id="users-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        <!-- Users will be inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let currentUser = null;
        let allUsers = [];

        // Check authentication and authorization
        async function checkAuth() {
            try {
                const response = await fetch('/nwrcontractregistry/backend/auth_handler.php?action=check');
                const result = await response.json();

                if (!result.loggedIn) {
                    window.location.href = '../index.php';
                    return false;
                }

                currentUser = result.user;
                updateUI();
                if (currentUser.role !== 'admin') {
                    document.getElementById('access-denied').style.display = 'block';
                    document.getElementById('main-content').style.display = 'none';
                    return false;
                }

                document.getElementById('main-content').style.display = 'block';
                return true;
            } catch (error) {
                console.error('Auth check failed:', error);
                window.location.href = '../index.php';
                return false;
            }
        }

        function updateUI() {
            if (!currentUser) return;

            document.getElementById('username-display').textContent = currentUser.username;
            const roleBadge = document.getElementById('role-badge');
            roleBadge.textContent = currentUser.role;
            roleBadge.className = `user-badge ${currentUser.role}`;

            // Show/hide navigation items based on role
            if (currentUser.role === 'manager' || currentUser.role === 'admin') {
                const manageNav = document.getElementById('manage-contract-nav');
                if (manageNav) manageNav.style.display = 'block';
            }

            if (currentUser.role === 'admin') {
                const usersNav = document.getElementById('users-nav');
                if (usersNav) usersNav.style.display = 'block';
            }
        }

        async function logout() {
            try {
                await fetch('/nwrcontractregistry/backend/auth_handler.php?action=logout', {
                    method: 'POST'
                });
                window.location.href = '../index.php';
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = '../index.php';
            }
        }

        // Load all users
        async function loadUsers() {
            try {
                const response = await fetch('/nwrcontractregistry/backend/index.php?action=users');
                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../index.php';
                        return;
                    }
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                allUsers = await response.json();
                renderUsersTable();

                document.getElementById('users-table').style.display = 'table';
            } catch (error) {
                console.error('Error loading users:', error);
                showMessage('Error loading users: ' + error.message, 'error');
                const tbody = document.getElementById('users-tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: red;">Error: ${error.message}</td></tr>`;
                }
            }
        }

        // Render users table
        function renderUsersTable() {
            const tbody = document.getElementById('users-tbody');
            tbody.innerHTML = '';

            if (Array.isArray(allUsers) && allUsers.length > 0) {
                // Sort users by role (admin > manager > user)
                allUsers.sort((a, b) => {
                    const roles = { admin: 3, manager: 2, user: 1 };
                    return roles[b.role] - roles[a.role];
                });

                allUsers.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${user.userid || 'N/A'}</td>
                        <td>${user.username || 'N/A'}</td>
                        <td>${user.email || 'N/A'}</td>
                        <td>
                            <select class="role-select" onchange="changeUserRole(${user.userid}, this.value)" 
                                    ${user.userid == currentUser.id ? 'disabled' : ''}>
                                <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                                <option value="manager" ${user.role === 'manager' ? 'selected' : ''}>Manager</option>
                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                            </select>
                        </td>
                        <td>${user.created_at || 'N/A'}</td>
                        <td>
                            ${user.userid != currentUser.id ?
                                `<button class="btn btn-danger btn-small" onclick="deleteUser(${user.userid}, '${user.username}')">Delete</button>` :
                                '<span style="color: #666; font-size: 0.8rem;">Current User</span>'
                            }
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No users found</td></tr>';
            }
        }

        // Change user role
        async function changeUserRole(userId, newRole) {
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('role', newRole);

                const response = await fetch('/nwrcontractregistry/backend/index.php?action=update_user_role', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    showMessage('User role updated successfully', 'success');
                    const user = allUsers.find(u => u.userid == userId);
                    if (user) user.role = newRole;
                    renderUsersTable();
                } else {
                    throw new Error(result.error || 'Failed to update role');
                }
            } catch (error) {
                console.error('Error updating role:', error);
                showMessage('Error updating user role: ' + error.message, 'error');
                loadUsers(); // Reload to reset select
            }
        }

        // Delete user
        async function deleteUser(userId, username) {
            if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('user_id', userId);

                const response = await fetch('/nwrcontractregistry/backend/index.php?action=delete_user', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    showMessage('User deleted successfully', 'success');
                    allUsers = allUsers.filter(u => u.userid != userId);
                    renderUsersTable();
                } else {
                    throw new Error(result.error || 'Failed to delete user');
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                showMessage('Error deleting user: ' + error.message, 'error');
            }
        }

        // Show message
        function showMessage(message, type) {
            const messageArea = document.getElementById('message-area');
            messageArea.innerHTML = `<div class="message ${type}">${message}</div>`;
            setTimeout(() => {
                messageArea.innerHTML = '';
            }, 5000);
        }

        // Search filter
        function filterUsers() {
            const searchInput = document.querySelector('.search-bar');
            const tableBody = document.getElementById('users-tbody');
            const searchText = searchInput?.value.toLowerCase() || '';

            Array.from(tableBody.querySelectorAll('tr')).forEach(row => {
                const username = row.children[1].textContent.toLowerCase();
                const email = row.children[2].textContent.toLowerCase();
                const matchesSearch = !searchText || username.includes(searchText) || email.includes(searchText);
                row.style.display = matchesSearch ? '' : 'none';
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', async () => {
            const hasAccess = await checkAuth();
            if (hasAccess) {
                await loadUsers();

                // Search filter
                const searchInput = document.querySelector('.search-bar');
                searchInput?.addEventListener('input', filterUsers);

                // CSV Export
                document.getElementById('download-csv')?.addEventListener('click', function () {
                    const rows = [
                        ['User ID', 'Username', 'Email', 'Role', 'Joined Date'],
                        ...allUsers.map(user => [
                            user.userid || 'N/A',
                            user.username || 'N/A',
                            user.email || 'N/A',
                            user.role || 'N/A',
                            user.created_at || 'N/A'
                        ])
                    ];

                    const csvContent = 'data:text/csv;charset=utf-8,' +
                        rows.map(r => r.map(v => `"${v.replace(/"/g, '""')}"`).join(',')).join('\n');

                    const downloadLink = document.createElement('a');
                    downloadLink.href = encodeURI(csvContent);
                    downloadLink.download = 'users.csv';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                });
            }
        });
    </script>
</body>
</html>