async function loadContracts() {
    try {
        const response = await fetch('../../backend/index.php?action=getContracts');
        const contracts = await response.json();

        const tbody = document.querySelector('#contracts-table tbody');
        tbody.innerHTML = '';

        contracts.forEach(contract => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${contract.contractid}</td>
                <td>${contract.parties}</td>
                <td>${contract.typeOfContract}</td>
                <td>${contract.duration}</td>
                <td>${contract.description || ''}</td>
                <td>${contract.expiryDate}</td>
                <td>${contract.reviewByDate}</td>
                <td>${contract.contractValue}</td>
            `;

            // highlight expired contracts
            const today = new Date();
            if (new Date(contract.expiryDate) < today) {
                tr.style.backgroundColor = 'red';
                tr.style.color = 'white';
            }

            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error('Error loading contracts:', err);
    }
}

document.addEventListener('DOMContentLoaded', loadContracts);
