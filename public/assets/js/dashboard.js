let salesChart;

async function loadSales() {
  const year  = document.getElementById('yearSelect').value;
  const month = document.getElementById('monthSelect').value;
  const resp  = await fetch(`api_sales_month.php?year=${year}&month=${month}`);
  const data  = await resp.json();
  renderChart(data);
}

function renderChart(data) {
  const ctx = document.getElementById('salesChart').getContext('2d');
  if (salesChart) salesChart.destroy();
  salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.days,
      datasets: [
        {
          label: 'Toplam',
          data: data.total,
          borderColor: 'white',
          backgroundColor: 'rgba(255,255,255,0.5)'
        },
        {
          label: 'Nakit',
          data: data.cash,
          borderColor: 'green',
          backgroundColor: 'rgba(0,128,0,0.5)'
        },
        {
          label: 'Kart',
          data: data.card,
          borderColor: 'blue',
          backgroundColor: 'rgba(0,0,255,0.5)'
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('monthSelect').addEventListener('change', loadSales);
  document.getElementById('yearSelect').addEventListener('change', loadSales);
  loadSales();
});
