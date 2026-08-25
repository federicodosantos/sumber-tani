@props(['chartData'])

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow">
  <div class="mb-4 flex items-center justify-between">
    <h3 class="text-lg font-medium text-gray-700">Grafik Penjualan</h3>
  </div>
  <div class="h-64">
    <canvas id="salesChart"></canvas>
  </div>
</div>

@push('scripts')
<script>
  let salesChart = null;

  function initChart() {
    if (salesChart) {
      salesChart.destroy();
    }

    const ctx = document.getElementById('salesChart');
    if (!ctx) return;

    const chartData = @json($chartData);

    salesChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartData.labels,
        datasets: [{
          label: 'Penjualan (Rp)',
          data: chartData.values,
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true,
          pointBackgroundColor: 'rgb(59, 130, 246)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return 'Rp ' + context.parsed.y.toLocaleString('id-ID', { maximumFractionDigits: 3 });
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                if (value >= 1000000) {
                  return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                } else if (value >= 1000) {
                  return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                }
                return 'Rp ' + value.toLocaleString('id-ID');
              }
            },
            grid: { color: 'rgba(0, 0, 0, 0.05)' }
          },
          x: { grid: { display: false } }
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChart);
  } else {
    initChart();
  }
</script>
@endpush