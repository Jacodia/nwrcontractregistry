// ===============================
// Global Variables
// ===============================
let contracts = [];
let selectedContract = null;
let contractToDelete = null;
let currentUser = null;

// API base URL (backend entrypoint)
const API_BASE = "/nwrcontractregistry/backend/index.php";

// ===============================
// Authentication & Authorization
// ===============================
async function checkAuth() {
  try {
    const response = await fetch(
      "/nwrcontractregistry/backend/auth_handler.php?action=check"
    );
    const result = await response.json();

    // Redirect to login if not logged in
    if (!result.loggedIn) {
      window.location.href = "../index.php";
      return false;
    }

    currentUser = result.user;

    // Restrict access → only managers & admins
    if (currentUser.role !== "manager" && currentUser.role !== "admin") {
      const deniedEl = document.getElementById("access-denied");
      const mainEl = document.getElementById("main-container");
      if (deniedEl) deniedEl.style.display = "block";
      if (mainEl) mainEl.style.display = "none";
      return false;
    }

    // Update UI with user info
    updateUserInfo();

    // Show main content if available
    const mainEl = document.getElementById("main-container");
    if (mainEl) mainEl.style.display = "block";

    return true;
  } catch (error) {
    console.error("Auth check failed:", error);
    window.location.href = "../index.php";
    return false;
  }
}

// ===============================
// User Info Display
// ===============================
function updateUserInfo() {
  if (!currentUser) return;

  const usernameEl = document.getElementById("username-display");
  const roleBadge = document.getElementById("role-badge");

  if (usernameEl) usernameEl.textContent = currentUser.username;
  if (roleBadge) {
    roleBadge.textContent = currentUser.role;
    roleBadge.className = `user-badge ${currentUser.role}`;
  }

  // Show "Users" nav link only for admins
  if (currentUser.role === "admin") {
    const usersNav = document.getElementById("users-nav");
    if (usersNav) usersNav.style.display = "block";
  }
}

// ===============================
// Logout
// ===============================
async function logout() {
  try {
    await fetch("/nwrcontractregistry/backend/auth_handler.php?action=logout", {
      method: "POST",
    });
    window.location.href = "../index.php";
  } catch (error) {
    console.error("Logout error:", error);
    window.location.href = "../index.php";
  }
}



// ===============================
// Tabs Handling
// ===============================
function initializeTabs() {
  const tabs = document.querySelectorAll(".tab");
  const tabContents = document.querySelectorAll(".tab-content");

  tabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      const targetTab = this.dataset.tab;

      // Reset all tabs & contents
      tabs.forEach((t) => t.classList.remove("active"));
      tabContents.forEach((tc) => tc.classList.remove("active"));

      // Activate clicked tab & corresponding content
      this.classList.add("active");
      const targetContent = document.getElementById(targetTab + "-tab");
      if (targetContent) targetContent.classList.add("active");

      // Refresh contracts when switching to edit/delete
      if (targetTab === "edit" || targetTab === "delete") {
        loadContracts();
      }
    });
  });
}

// ===============================
// Load & Update Contracts
// ===============================
async function loadContracts() {
  try {
    const response = await fetch(`${API_BASE}?action=list`);
    if (!response.ok) {
      if (response.status === 401) {
        window.location.href = "../index.php";
        return;
      }
      throw new Error("Failed to fetch contracts");
    }

    contracts = await response.json();
    console.log("Loaded contracts:", contracts.length);

    updateContractLists();
  } catch (error) {
    console.error("Error loading contracts:", error);
    showMessage("Error loading contracts: " + error.message, "error");
  }
}


function updateContractLists() {
  updateEditContractList();
  updateDeleteContractList();
}

function updateEditContractList() {
  const container = document.getElementById("contract-list");
  if (!container) return;

  if (contracts.length === 0) {
    container.innerHTML = '<div class="loading">No contracts found</div>';
    return;
  }

  container.innerHTML = contracts
    .map(
      (contract) => `
        <div class="contract-item" onclick="selectContractForEdit(${
          contract.contractid
        }, event)">
            <h4>ID: ${contract.contractid} - ${contract.parties}</h4>
            <p><strong>Type:</strong> ${contract.typeOfContract || "N/A"}</p>
            <p><strong>Value:</strong> ${formatCurrency(
              contract.contractValue
            )} | <strong>Expires:</strong> ${contract.expiryDate || "N/A"}</p>
        </div>
    `
    )
    .join("");
}

function updateDeleteContractList() {
  const container = document.getElementById("delete-contract-list");
  if (!container) return;

  if (contracts.length === 0) {
    container.innerHTML = '<div class="loading">No contracts found</div>';
    return;
  }

  container.innerHTML = contracts
    .map(
      (contract) => `
        <div class="contract-item" onclick="selectContractForDelete(${
          contract.contractid
        }, event)">
            <h4>ID: ${contract.contractid} - ${contract.parties}</h4>
            <p><strong>Type:</strong> ${contract.typeOfContract || "N/A"}</p>
            <p><strong>Value:</strong> ${formatCurrency(
              contract.contractValue
            )} | <strong>Expires:</strong> ${contract.expiryDate || "N/A"}</p>
        </div>
    `
    )
    .join("");
}

// ===============================
// Forms
// ===============================
function setupFormHandlers() {
  const createForm = document.getElementById("create-form");
  const editForm = document.getElementById("edit-form");

  if (createForm) createForm.addEventListener("submit", handleCreateContract);
  if (editForm) editForm.addEventListener("submit", handleUpdateContract);
}

// Handle contract creation
async function handleCreateContract(e) {
  e.preventDefault();
  const formData = new FormData(e.target);

  try {
    const response = await fetch(`${API_BASE}?action=create`, {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      if (response.status === 401) {
        window.location.href = "../index.php";
        return;
      }
      if (response.status === 403) {
        showMessage(
          "Access denied. You do not have permission to create contracts.",
          "error"
        );
        return;
      }
      throw new Error("Failed to create contract: " + response.statusText);
    }

    await response.json();
    showMessage("Contract created successfully!", "success");
    e.target.reset();
    loadContracts();
  } catch (error) {
    console.error("Error creating contract:", error);
    showMessage("Error creating contract: " + error.message, "error");
  }
}

// ===============================
// Edit Contracts
// ===============================
function selectContractForEdit(contractId, event) {
  const contract = contracts.find((c) => c.contractid == contractId);
  if (!contract) return;

  selectedContract = contract;

  // Highlight selected
  document.querySelectorAll("#contract-list .contract-item").forEach((item) => {
    item.classList.remove("selected");
  });
  if (event?.target.closest(".contract-item")) {
    event.target.closest(".contract-item").classList.add("selected");
  }

  // Show form & populate fields
  const editForm = document.getElementById("edit-form");
  const placeholder = document.getElementById("edit-placeholder");
  if (editForm && placeholder) {
    editForm.style.display = "block";
    placeholder.style.display = "none";
  }

  populateEditForm(contract);
}


function populateEditForm(contract) {
  const mapping = {
    "edit-contractid": contract.contractid,
    "edit-parties": contract.parties || "",
    "edit-typeOfContract": contract.typeOfContract || "",
    "edit-duration": contract.duration || "",
    "edit-contractValue": contract.contractValue || "",
    "edit-description": contract.description || "",
    "edit-expiryDate": contract.expiryDate || "",
    "edit-reviewByDate": contract.reviewByDate || "",
  };

  Object.entries(mapping).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value;
  });

  // Handle file links
  const viewContainer = document.getElementById("edit-view-file");
  const viewLink = document.getElementById("view-contract-file");
  const downloadLink = document.getElementById("download-contract-file");

  if (contract.filepath && viewContainer && viewLink && downloadLink) {
    const fileUrl = `/nwrcontractregistry/backend/${contract.filepath}`;
    viewLink.href = fileUrl;
    downloadLink.href = fileUrl;
    viewContainer.style.display = "block";
  } else if (viewContainer && viewLink && downloadLink) {
    viewContainer.style.display = "none";
    viewLink.href = "#";
    downloadLink.href = "#";
  }
}


function clearEditForm() {
  const editForm = document.getElementById("edit-form");
  const placeholder = document.getElementById("edit-placeholder");

  if (editForm && placeholder) {
    editForm.style.display = "none";
    placeholder.style.display = "block";
    editForm.reset();
  }
  selectedContract = null;

  document.querySelectorAll("#contract-list .contract-item").forEach((item) => {
    item.classList.remove("selected");
  });
}


async function handleUpdateContract(e) {
  e.preventDefault();

  if (!selectedContract) {
    showMessage("No contract selected for editing", "error");
    return;
  }

  const formData = new FormData(e.target);
  const contractId = formData.get("contractid");
  formData.delete("contractid");

  try {
    const response = await fetch(`${API_BASE}?action=update&id=${contractId}`, {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      if (response.status === 401) {
        window.location.href = "../index.php";
        return;
      }
      if (response.status === 403) {
        showMessage(
          "Access denied. You do not have permission to edit contracts.",
          "error"
        );
        return;
      }
      throw new Error("Failed to update contract");
    }

    await response.json();
    showMessage("Contract updated successfully!", "success");
    clearEditForm();
    loadContracts();
  } catch (error) {
    console.error("Error updating contract:", error);
    showMessage("Error updating contract: " + error.message, "error");
  }
}

// ===============================
// Delete Contracts
// ===============================
function selectContractForDelete(contractId, event) {
  const contract = contracts.find((c) => c.contractid == contractId);
  if (!contract) return;

  contractToDelete = contract;

  // Highlight selected
  document
    .querySelectorAll("#delete-contract-list .contract-item")
    .forEach((item) => {
      item.classList.remove("selected");
    });
  if (event?.target.closest(".contract-item")) {
    event.target.closest(".contract-item").classList.add("selected");
  }

  // Show confirmation
  const confirmation = document.getElementById("delete-confirmation");
  if (confirmation) confirmation.style.display = "block";

  const mapping = {
    "delete-contract-id": contract.contractid,
    "delete-contract-parties": contract.parties || "N/A",
    "delete-contract-type": contract.typeOfContract || "N/A",
    "delete-contract-value": formatCurrency(contract.contractValue),
  };

  Object.entries(mapping).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  });
}

async function confirmDelete() {
  if (!contractToDelete) return;

  const contractId = contractToDelete.contractid;
  console.log("Deleting contract:", contractId);

  try {
    const response = await fetch(`${API_BASE}?action=delete&id=${contractId}`, {
      method: "DELETE",
    });

    if (!response.ok) {
      if (response.status === 401) {
        window.location.href = "../index.php";
        return;
      }
      if (response.status === 403) {
        showMessage(
          "Access denied. You do not have permission to delete contracts.",
          "error"
        );
        return;
      }
      throw new Error("Failed to delete contract");
    }

    await response.json();
    showMessage("Contract deleted successfully!", "success");
    cancelDelete();
    loadContracts();
  } catch (error) {
    console.error("Error deleting contract:", error);
    showMessage("Error deleting contract: " + error.message, "error");
  }
}


function cancelDelete() {
  contractToDelete = null;
  const confirmation = document.getElementById("delete-confirmation");
  if (confirmation) confirmation.style.display = "none";

  document
    .querySelectorAll("#delete-contract-list .contract-item")
    .forEach((item) => {
      item.classList.remove("selected");
    });
}

// ===============================
// Helpers
// ===============================
function showMessage(message, type) {
  const messageEl = document.getElementById("message");
  if (!messageEl) return;

  messageEl.textContent = message;
  messageEl.className = `message ${type}`;
  messageEl.style.display = "block";

  // Auto-hide after 5 seconds
  setTimeout(() => {
    messageEl.style.display = "none";
  }, 5000);
}


function formatCurrency(value) {
  if (!value || isNaN(value)) return "N/A";
  const numValue = parseFloat(value);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
  }).format(numValue);
}

// ===============================
// Contract Types Handling
// ===============================
async function loadContractTypes() {
  const res = await fetch('/nwrcontractregistry/backend/index.php?action=list_types');
  const types = await res.json();

  const selects = [document.getElementById('typeOfContract'), document.getElementById('edit-typeOfContract')];
  selects.forEach(sel => {
    if (!sel) return;
    sel.innerHTML = '<option value="">Select contract type</option>';
    types.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.name;
      opt.textContent = t.name;
      sel.appendChild(opt);
    });
  });
}

// Show "Add Type" input + save button only for admins
function showAddTypeDialogForAdmins() {
  if (currentUser && currentUser.role === "admin") {
    const dialog = document.getElementById('add-type-dialog');
    if (dialog) {
      dialog.style.display = 'block'; // show for admins
      console.log("✅ Admin detected, showing Add Type dialog");
    }
  } else {
    console.log("❌ Not admin or currentUser missing, dialog stays hidden");
  }
}

async function saveType() {
  const name = document.getElementById('new-type-name').value.trim();
  const userId = currentUser?.userid;

  if (!name) {
    showMessage("Please enter a contract type name.", "error");
    return;
  }

  const res = await fetch('/nwrcontractregistry/backend/index.php?action=add_type', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, userId })
  });

  const result = await res.json();

  if (result.success) {
    loadContractTypes();
    document.getElementById('new-type-name').value = ''; // clear field
    showMessage("Contract type added successfully.", "success");
  } else {
    showMessage(result.message || "Failed to add contract type.", "error");
  }
}

// ===============================
// Initialize Page (after DOM load)
// ===============================
document.addEventListener("DOMContentLoaded", async function () {
  const hasAccess = await checkAuth();
  if (hasAccess) {
    initializeTabs();
    loadContracts();
    setupFormHandlers();
    loadContractTypes();
    showAddTypeDialogForAdmins();

  }
});
