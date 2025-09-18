// Check authentication and load user info
        let currentUser = null;
        
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
                return true;
            } catch (error) {
                console.error('Auth check failed:', error);
                window.location.href = '../index.php';
                return false;
            }
        }
        
        function updateUI() {
            if (!currentUser) return;
            
            // Update user display
            document.getElementById('username-display').textContent = currentUser.username;
            const roleBadge = document.getElementById('role-badge');
            roleBadge.textContent = currentUser.role;
            roleBadge.className = `user-badge ${currentUser.role}`;
            
            // Update role info message
            const roleMessages = {
                'user': 'You have view-only access to contracts. Contact an administrator to request additional permissions.',
                'manager': 'You can view, create, and modify contracts.',
                'admin': 'You have full access to contracts and user management.'
            };
            
            document.getElementById('role-message').textContent = roleMessages[currentUser.role] || 'Access level: ' + currentUser.role;
            
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
                const response = await fetch('/nwrcontractregistry/backend/auth_handler.php?action=logout', {
                    method: 'POST'
                });
                
                // Redirect to login regardless of response
                window.location.href = '../index.php';
            } catch (error) {
                console.error('Logout error:', error);
                // Still redirect to login
                window.location.href = '../index.php';
            }
        }

        async function loadContracts() {
            console.log("Loading contracts...");

            const backendUrl = "/nwrcontractregistry/backend/index.php?action=list";

            try {
                const response = await fetch(backendUrl);
                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../index.php';
                        return;
                    }
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                let contracts = await response.json();
                const tbody = document.querySelector("#contracts-table tbody");
                tbody.innerHTML = "";

                if (Array.isArray(contracts) && contracts.length > 0) {
                    // Sort by expiry date (earliest first, null/invalid at the end)
                    contracts.sort((a, b) => {
                        const dateA = a.expiryDate ? new Date(a.expiryDate) : null;
                        const dateB = b.expiryDate ? new Date(b.expiryDate) : null;

                        if (!dateA && !dateB) return 0;
                        if (!dateA) return 1; // push invalid/empty dates to bottom
                        if (!dateB) return -1;

                        return dateA - dateB; // earliest first
                    });

                    contracts.forEach((contract) => {
                        const tr = document.createElement("tr");

                        let formattedValue = "N/A";
                        if (contract.contractValue && !isNaN(contract.contractValue)) {
                            formattedValue =
                                "N$ " +
                                new Intl.NumberFormat("en-US", {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }).format(parseFloat(contract.contractValue));
                        }

                        tr.innerHTML = `
                            <td>${contract.contractid || "N/A"}</td>
                            <td>${contract.parties || "N/A"}</td>
                            <td>${contract.typeOfContract || "N/A"}</td>
                            <td>${contract.duration || "N/A"}</td>
                            <td>${contract.description || "N/A"}</td>
                            <td>${contract.expiryDate || "N/A"}</td>
                            <td>${contract.reviewByDate || "N/A"}</td>
                            <td>${formattedValue}</td>
                        `;

                        // Highlight expired contracts
                        if (contract.expiryDate) {
                            const today = new Date();
                            today.setHours(0, 0, 0, 0);
                            const expiryDate = new Date(contract.expiryDate);
                            if (expiryDate <= today) {
                                tr.style.backgroundColor = "#ffcdd2";
                                tr.style.color = "#c62828";
                            }
                        }

                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML =
                        '<tr><td colspan="8" style="text-align: center;">No contracts found</td></tr>';
                }
            } catch (err) {
                console.error("Error loading contracts:", err);
                const tbody = document.querySelector("#contracts-table tbody");
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Error: ${err.message}</td></tr>`;
                }
            }
        }

        // Initialize page
        document.addEventListener("DOMContentLoaded", async () => {
            const isAuthenticated = await checkAuth();
            if (isAuthenticated) {
                loadContracts();
                
                // CSV Export
                document
                    .getElementById("download-csv")
                    ?.addEventListener("click", function () {
                        const rows = [
                            [
                                "Contract ID",
                                "Parties",
                                "Type of Contract",
                                "Duration",
                                "Description",
                                "Expiry Date",
                                "Review By",
                                "Contract Value",
                            ],
                        ];

                        document.querySelectorAll("#contracts-table tbody tr").forEach((tr) => {
                            const cols = tr.querySelectorAll("td");
                            const row = Array.from(cols).map((td) => td.innerText.trim());
                            rows.push(row);
                        });

                        const csvContent =
                            "data:text/csv;charset=utf-8," +
                            rows
                                .map((r) => r.map((v) => `"${v.replace(/"/g, '""')}"`).join(","))
                                .join("\n");

                        const downloadLink = document.createElement("a");
                        downloadLink.href = encodeURI(csvContent);
                        downloadLink.download = "contracts.csv";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    });

                // Search filter
                const searchInput = document.querySelector(".search-bar");
                const tableBody = document.querySelector("#contracts-table tbody");

                function filterContracts() {
                    const searchText = searchInput?.value.toLowerCase() || "";

                    Array.from(tableBody.querySelectorAll("tr")).forEach((row) => {
                        const party = row.children[1].textContent.toLowerCase();
                        const type = row.children[2].textContent.toLowerCase();

                        let matchesSearch =
                            !searchText || party.includes(searchText) || type.includes(searchText);

                        row.style.display = matchesSearch ? "" : "none";
                    });
                }

                searchInput?.addEventListener("input", filterContracts);
            }
        });