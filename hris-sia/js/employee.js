// Employee search functionality (if needed)
// Note: employees.php uses form-based filtering, so this may not be used
function searchEmployees() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const department = document.getElementById('departmentFilter')?.value.toLowerCase() || '';

    const rows = document.querySelectorAll('#employeeTableBody tr');
    rows.forEach(row => {
        const position = row.cells[2]?.textContent.toLowerCase() || '';
        const dept = row.cells[3]?.textContent.toLowerCase() || '';

        const matchesSearch = searchTerm === '' || position.includes(searchTerm);
        const matchesDept = department === '' || dept.includes(department);

        row.style.display = (matchesSearch && matchesDept) ? '' : 'none';
    });

    const cards = document.querySelectorAll('.employee-card');
    cards.forEach(card => {
        const position = card.dataset.position || '';
        const dept = card.dataset.department || '';

        const matchesSearch = searchTerm === '' || position.includes(searchTerm);
        const matchesDept = department === '' || dept.includes(department);

        card.style.display = (matchesSearch && matchesDept) ? '' : 'none';
    });
}

// Event listeners (only if elements exist)
if (document.getElementById('searchInput')) {
    document.getElementById('searchInput').addEventListener('keyup', searchEmployees);
}
if (document.getElementById('departmentFilter')) {
    document.getElementById('departmentFilter').addEventListener('keyup', searchEmployees);
}