function loadContracts() {
  fetch("../../controllers/contractController.php?action=list")
    .then(res => res.json())
    .then(data => {
      const tbody = document.querySelector("#contracts-table tbody");
      tbody.innerHTML = "";
      data.forEach(contract => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${contract.contractid}</td>
          <td>${contract.parties}</td>
          <td>${contract.typeOfContract}</td>
          <td>${contract.description}</td>
          <td>${contract.expiryDate}</td>
          <td>${contract.reviewByDate}</td>
          <td>${contract.contractValue}</td>
          <td>
            <button onclick="editContract(${contract.contractid})">Edit</button>
            <button onclick="deleteContract(${contract.contractid})">Delete</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    });
}

// Call on page load
loadContracts();
