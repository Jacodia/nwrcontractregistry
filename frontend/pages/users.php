<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/users.css">
</head>

<body>
    <div class="navbar" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: nowrap; padding: 0 1rem; min-height: 70px; background: #0758aa;">
        <ul class="nav-links" id="nav-links" style="display: flex; align-items: center; gap: 1.5rem; margin: 0; padding: 0; list-style: none;">
            <li><a href="dashboard.html" style="color: #fff; text-decoration: none; font-weight: 500;">Dashboard</a></li>
            <li><a href="manage_contract.html" style="color: #fff; text-decoration: none; font-weight: 500;">Manage Contract</a></li>
            <li id="users-nav" style="display: none;"><a href="users.php" style="color: #fff; text-decoration: none; font-weight: 500;">Users</a></li>
        </ul>
        <div class="user-info" id="user-info" style="display: flex; align-items: center; gap: 1rem; margin-left: 1.5rem;">
            <span id="username-display">Loading...</span>
            <span id="role-badge" class="user-badge">user</span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
        <div class="nwr_logo" style="margin-left: 1.5rem;">
            <img src="../images/nwr_logo.png" alt="NWR Logo" style="height:60px;">
        </div>
    </div>
    
    <style>@media (max-width: 900px) {.navbar {flex-direction: row;flex-wrap: nowrap;padding: 0 0.5rem;}.nav-links {gap: 0.7rem !important;}.user-info {gap: 0.5rem !important;}.nwr_logo img {height: 40px !important;}}@media (max-width: 600px) {.navbar {flex-direction: row;flex-wrap: nowrap;padding: 0 0.2rem;}.nav-links {gap: 0.3rem !important;}.user-info {gap: 0.3rem !important;font-size: 0.85rem;}.nwr_logo img {height: 30px !important;}}.navbar .nav-links li a:hover {text-decoration: underline;}</style>

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