document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('search');
  if (!searchInput) return;
  const rows = document.querySelectorAll('#products-table tbody tr');
  searchInput.addEventListener('input', function () {
    const term = this.value.toLowerCase();
    rows.forEach(function (row) {
      const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
      row.style.display = name.includes(term) ? '' : 'none';
    });
  });
});
