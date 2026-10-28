// ==========================================================
// Computer Shop Inventory Management System - Client Script
// ==========================================================

document.addEventListener('DOMContentLoaded', () => {
    initTableSearch();
    initFlashToasts();
});

// Real-time table search & filter
function initTableSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const targetTableId = input.getAttribute('data-table-search');
        const table = document.getElementById(targetTableId);
        if (!table) return;

        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
}

// Category / Status dropdown filters
function filterTableByColumn(tableId, columnIndex, selectedValue) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cell = row.children[columnIndex];
        if (!cell) return;
        const cellText = cell.innerText.trim();
        if (!selectedValue || selectedValue === 'ALL' || cellText.includes(selectedValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// CSV Exporter for audit tables & inventory lists
function exportTableToCSV(tableId, filename = 'inventory_export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        // Skip hidden rows or action column if marked .no-export
        if (rows[i].style.display === 'none') continue;
        
        let row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length; j++) {
            if (cols[j].classList.contains('no-export')) continue;
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
            // Escape double quotes
            data = data.replace(/"/g, '""');
            row.push(`"${data}"`);
        }
        csv.push(row.join(','));
    }

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Toast / Flash auto dismiss
function initFlashToasts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
}
