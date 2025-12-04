export default (initialChartData, initialChartFilter, initialTransactionFilter) => ({
    salesChart: null,
    chartData: initialChartData,

    init() {
        this.initChart();
        this.setupFilters();
    },

    initChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        if (this.salesChart) {
            this.salesChart.destroy();
        }

        this.salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.chartData.labels,
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: this.chartData.values,
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
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    },

    setupFilters() {
        // Kita bisa attach event listener manual atau pakai x-on di blade
        // Tapi karena kita pakai pendekatan handler terpusat:
        
        const transactionFilterSelect = document.getElementById('transactionFilterSelect');
        if (transactionFilterSelect) {
            transactionFilterSelect.addEventListener('change', (e) => {
                this.updateUrl('transaction_filter', e.target.value, 'chart_filter', initialChartFilter);
            });
        }

        const chartFilterSelect = document.getElementById('chartFilterSelect');
        if (chartFilterSelect) {
            chartFilterSelect.addEventListener('change', (e) => {
                this.updateUrl('chart_filter', e.target.value, 'transaction_filter', initialTransactionFilter);
            });
        }
    },

    updateUrl(newParam, newValue, existingParam, existingValue) {
        const url = new URL(window.location.href);
        url.searchParams.set(newParam, newValue);
        url.searchParams.set(existingParam, existingValue);
        window.location.href = url.toString();
    }
});