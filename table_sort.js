// table_sort.js
document.addEventListener('DOMContentLoaded', function () {
    // Find all tables on the page
    const tables = document.querySelectorAll('.table');

    tables.forEach(table => {
        const headers = table.querySelectorAll('thead th');
        
        headers.forEach((header, index) => {
            if (header.innerText.trim().includes('Action')) return;

            header.style.cursor = 'pointer';
            header.title = "Click to sort";
            header.innerHTML += ' <i class="bi bi-chevron-expand text-muted float-end" style="font-size: 0.9rem; margin-top: 3px;"></i>';

            header.addEventListener('click', () => {
                const tBody = table.querySelector('tbody');
                const rows = Array.from(tBody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sort-asc');

                // Reset all headers back to default state
                headers.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                    let icon = h.querySelector('i');
                    if(icon) icon.className = 'bi bi-chevron-expand text-muted float-end';
                });

                // Set the current header to ascending or descending
                let direction = 1;
                if (!isAsc) {
                    header.classList.add('sort-asc');
                    header.querySelector('i').className = 'bi bi-chevron-up float-end text-dark';
                } else {
                    header.classList.add('sort-desc');
                    header.querySelector('i').className = 'bi bi-chevron-down float-end text-dark';
                    direction = -1;
                }

                // Sort the rows based on the text inside the targeted column
                rows.sort((a, b) => {
                    let aText = a.querySelectorAll('td')[index].innerText.trim();
                    let bText = b.querySelectorAll('td')[index].innerText.trim();

                    // Try to extract numbers for accurate numeric sorting 
                    let aNum = parseFloat(aText.replace(/[^0-9.-]+/g, ""));
                    let bNum = parseFloat(bText.replace(/[^0-9.-]+/g, ""));

                    if (!isNaN(aNum) && !isNaN(bNum) && aText.match(/\d/) && bText.match(/\d/)) {
                        return (aNum - bNum) * direction;
                    }

                    // sort alphabetically
                    return aText.localeCompare(bText) * direction;
                });

                tBody.append(...rows);
            });
        });
    });
});