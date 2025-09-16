// Global variables
let contracts = [];
let selectedContract = null;
let contractToDelete = null;

// API base URL
const API_BASE = '/nwrcontractregistry/backend/index.php';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    loadContracts();
    setupFormHandlers();
});

// Tab functionality
function initializeTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
            
            // Refresh contracts list when switching to edit or delete tabs
            if (targetTab === 'edit' || targetTab === 'delete') {
                loadContracts();
            }
        });
    });
}

// Load contracts from backend
async function loadContracts() {
    try {
        const response = await fetch(`${API_BASE}?action=list`);
        if (!response.ok) throw new Error('Failed to fetch contracts');
        
        contracts = await response.json();
        console.log('Loaded contracts:', contracts.length);
        
        updateContractLists();
    } catch (error) {
        console.error('Error loading contracts:', error);
        showMessage('Error loading contracts: ' + error.message, 'error');
    }
}

// Update contract lists in edit and delete tabs
function updateContractLists() {
    updateEditContractList();
    updateDeleteContractList();
}

function updateEditContractList() {
    const container = document.getElementById('contract-list');
    
    if (contracts.length === 0) {
        container.innerHTML = '<div class="loading">No contracts found</div>';
        return;
    }
    
    container.innerHTML = contracts.map(contract => `
        <div class="contract-item" onclick="selectContractForEdit(${contract.contractid})">
            <h4>ID: ${contract.contractid} - ${contract.parties}</h4>
            <p><strong>Type:</strong> ${contract.typeOfContract || 'N/A'}</p>
            <p><strong>Value:</strong> ${formatCurrency(contract.contractValue)} | <strong>Expires:</strong> ${contract.expiryDate || 'N/A'}</p>
        </div>
    `).join('');
}

function updateDeleteContractList() {
    const container = document.getElementById('delete-contract-list');
    
    if (contracts.length === 0) {
        container.innerHTML = '<div class="loading">No contracts found</div>';
        return;
    }
    
    container.innerHTML = contracts.map(contract => `
        <div class="contract-item" onclick="selectContractForDelete(${contract.contractid})">
            <h4>ID: ${contract.contractid} - ${contract.parties}</h4>
            <p><strong>Type:</strong> ${contract.typeOfContract || 'N/A'}</p>
            <p><strong>Value:</strong> ${formatCurrency(contract.contractValue)} | <strong>Expires:</strong> ${contract.expiryDate || 'N/A'}</p>
        </div>
    `).join('');
}

// Setup form handlers
function setupFormHandlers() {
    // Create form handler
    document.getElementById('create-form').addEventListener('submit', handleCreateContract);
    
    // Edit form handler
    document.getElementById('edit-form').addEventListener('submit', handleUpdateContract);
}

// Handle create contract
async function handleCreateContract(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    console.log('Creating contract (FormData):', ...formData);
    try {
        const response = await fetch(`${API_BASE}?action=create`, {
            method: 'POST',
            body: formData // Do not set headers, browser will set multipart/form-data
        });

        if (!response.ok) throw new Error('Failed to create contract: ' + response.statusText + error);

        const result = await response.json();
        console.log('Create result:', result);

        showMessage('Contract created successfully!', 'success');

        e.target.reset();
        loadContracts(); // Refresh the lists
        
    } catch (error) {
        console.error('Error creating contract:', error);
        showMessage('Error creating contract: ' + error.message, 'error');
    }
}

// Select contract for editing
function selectContractForEdit(contractId) {
    const contract = contracts.find(c => c.contractid == contractId);
    if (!contract) return;
    
    selectedContract = contract;
    
    // Update selected state in UI
    document.querySelectorAll('#contract-list .contract-item').forEach(item => {
        item.classList.remove('selected');
    });
    event.target.closest('.contract-item').classList.add('selected');
    
    // Show edit form and populate it
    document.getElementById('edit-form').style.display = 'block';
    document.getElementById('edit-placeholder').style.display = 'none';
    
    populateEditForm(contract);
}

// Populate edit form with contract data
function populateEditForm(contract) {
    document.getElementById('edit-contractid').value = contract.contractid;
    document.getElementById('edit-parties').value = contract.parties || '';
    document.getElementById('edit-typeOfContract').value = contract.typeOfContract || '';
    document.getElementById('edit-duration').value = contract.duration || '';
    document.getElementById('edit-contractValue').value = contract.contractValue || '';
    document.getElementById('edit-description').value = contract.description || '';
    document.getElementById('edit-expiryDate').value = contract.expiryDate || '';
    document.getElementById('edit-reviewByDate').value = contract.reviewByDate || '';
}

// Clear edit form
function clearEditForm() {
    document.getElementById('edit-form').style.display = 'none';
    document.getElementById('edit-placeholder').style.display = 'block';
    document.getElementById('edit-form').reset();
    selectedContract = null;
    
    // Remove selected state
    document.querySelectorAll('#contract-list .contract-item').forEach(item => {
        item.classList.remove('selected');
    });
}

// Handle update contract
async function handleUpdateContract(e) {
    e.preventDefault();
    
    if (!selectedContract) {
        showMessage('No contract selected for editing', 'error');
        return;
    }

    const formData = new FormData(e.target);
    const contractId = formData.get('contractid');
    formData.delete('contractid'); // backend probably uses the ?id= param
    
    console.log('Updating contract (FormData):', ...formData);

    try {
        const response = await fetch(`${API_BASE}?action=update&id=${contractId}`, {
            method: 'POST',
            body: formData // same style as create
        });

        if (!response.ok) throw new Error('Failed to update contract');

        const result = await response.json();
        console.log('Update result:', result);

        showMessage('Contract updated successfully!', 'success');
        clearEditForm();
        loadContracts();

    } catch (error) {
        console.error('Error updating contract:', error);
        showMessage('Error updating contract: ' + error.message, 'error');
    }
}



// Select contract for deletion
function selectContractForDelete(contractId) {
    const contract = contracts.find(c => c.contractid == contractId);
    if (!contract) return;
    
    contractToDelete = contract;
    
    // Update selected state in UI
    document.querySelectorAll('#delete-contract-list .contract-item').forEach(item => {
        item.classList.remove('selected');
    });
    event.target.closest('.contract-item').classList.add('selected');
    
    // Show confirmation
    document.getElementById('delete-confirmation').style.display = 'block';
    document.getElementById('delete-contract-id').textContent = contract.contractid;
    document.getElementById('delete-contract-parties').textContent = contract.parties || 'N/A';
    document.getElementById('delete-contract-type').textContent = contract.typeOfContract || 'N/A';
    document.getElementById('delete-contract-value').textContent = formatCurrency(contract.contractValue);
}

// Confirm deletion
async function confirmDelete() {
    if (!contractToDelete) return;
    
    const contractId = contractToDelete.contractid;
    console.log('Deleting contract:', contractId);
    
    try {
        const response = await fetch(`${API_BASE}?action=delete&id=${contractId}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) throw new Error('Failed to delete contract');
        
        const result = await response.json();
        console.log('Delete result:', result);
        
        showMessage('Contract deleted successfully!', 'success');
        cancelDelete();
        loadContracts(); // Refresh the lists
        
    } catch (error) {
        console.error('Error deleting contract:', error);
        showMessage('Error deleting contract: ' + error.message, 'error');
    }
}

// Cancel deletion
function cancelDelete() {
    contractToDelete = null;
    document.getElementById('delete-confirmation').style.display = 'none';
    
    // Remove selected state
    document.querySelectorAll('#delete-contract-list .contract-item').forEach(item => {
        item.classList.remove('selected');
    });
}

// Show message to user
function showMessage(message, type) {
    const messageEl = document.getElementById('message');
    messageEl.textContent = message;
    messageEl.className = `message ${type}`;
    messageEl.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageEl.style.display = 'none';
    }, 5000);
}

// Format currency
function formatCurrency(value) {
    if (!value || isNaN(value)) return 'N/A';
    
    const numValue = parseFloat(value);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(numValue);
}