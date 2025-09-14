async function loadContracts() {
    console.log("Loading contracts...");

    // Absolute URL
    const backendUrl = '/nwrcontractregistry/backend/index.php?action=list';

    try {
        const response = await fetch(backendUrl);
        console.log("Response status:", response.status);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const contracts = await response.json();
        console.log("Contracts loaded:", contracts.length, "contracts");

        const tbody = document.querySelector('#contracts-table tbody');
        if (!tbody) {
            console.error("Table tbody not found!");
            return;
        }

        tbody.innerHTML = '';

        if (Array.isArray(contracts) && contracts.length > 0) {
            contracts.forEach(contract => {
                const tr = document.createElement('tr');

                // Format contract value as currency
                let formattedValue = 'N/A';
                if (contract.contractValue && !isNaN(contract.contractValue)) {
                    const value = parseFloat(contract.contractValue);
                    formattedValue = 'N$ ' + new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
}).format(value);
                }

                tr.innerHTML = `
                    <td>${contract.contractid || 'N/A'}</td>
                    <td>${contract.parties || 'N/A'}</td>
                    <td>${contract.typeOfContract || 'N/A'}</td>
                    <td>${contract.duration || 'N/A'}</td>
                    <td>${contract.description || 'N/A'}</td>
                    <td>${contract.expiryDate || 'N/A'}</td>
                    <td>${contract.reviewByDate || 'N/A'}</td>
                    <td>${formattedValue}</td>
                `;

                // Highlight expired contracts
                if (contract.expiryDate) {
                    const today = new Date();
                    const expiryDate = new Date(contract.expiryDate);
                    if (expiryDate < today) {
                        tr.style.backgroundColor = '#ffcdd2';
                        tr.style.color = '#c62828';
                    }
                }

                tbody.appendChild(tr);
            });

            console.log(`Successfully loaded ${contracts.length} contracts`);
        } else {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No contracts found</td></tr>';
        }

    } catch (err) {
        console.error('Error loading contracts:', err);
        const tbody = document.querySelector('#contracts-table tbody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Error: ${err.message}</td></tr>`;
        }
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', loadContracts);
