document.addEventListener("DOMContentLoaded", () => {
    console.log("Dashboard loaded");

    fetch("../../backend/contracts.php")
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector("#contracts-table tbody");
            tableBody.innerHTML = ""; // clear dummy rows

            data.forEach(contract => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${contract.parties}</td>
                    <td>${contract.typeOfContract}</td>
                    <td>${contract.description || "-"}</td>
                    <td>${contract.expiryDate}</td>
                    <td>${contract.reviewByDate}</td>
                    <td>$${Number(contract.contractValue).toFixed(2)}</td>
                    <td class="${new Date(contract.expiryDate) < new Date() ? "expired" : "active"}">
                        ${new Date(contract.expiryDate) < new Date() ? "Expired" : "Active"}
                    </td>
                `;

                tableBody.appendChild(row);
            });
        })
        .catch(err => console.error("Error loading contracts:", err));
});
