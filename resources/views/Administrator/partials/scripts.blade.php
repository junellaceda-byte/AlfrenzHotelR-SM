<!-- Custom JavaScript for Mobile Sidebar Toggle -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebarMenu');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');

        if (openBtn && sidebar) {
            openBtn.addEventListener('click', function () {
                if (window.bootstrap && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebar);
                    bsOffcanvas.show();
                } else {
                    sidebar.classList.add('show');
                }
            });
        }

        if (closeBtn && sidebar) {
            closeBtn.addEventListener('click', function () {
                if (window.bootstrap && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebar);
                    if (bsOffcanvas) bsOffcanvas.hide();
                } else {
                    sidebar.classList.remove('show');
                }
            });
        }
    });
</script>

<!-- Local Chart.js Script -->
<script src="{{ asset('js/Administrator JS/chart.min.js') }}"></script>

<!-- ApexCharts CDN for Gauge Chart (Sales and Revenue) -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // ==========================================
        // 1. Revenue Bar Chart (Overview Section)
        // ==========================================
        const chartCanvas = document.getElementById('revenueChart');

        // Only run chart script if the canvas exists on the current section
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');

            let revenueChartDataSets = {
                today: {
                    labels: ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM'],
                    data: [0, 0, 0, 0, 0, 0, 0]
                },
                weekly: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                    data: [0, 0, 0, 0, 0]
                },
                monthly: {
                    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                },
                yearly: {
                    labels: ['2021', '2022', '2023', '2024', '2025', '2026'],
                    data: [0, 0, 0, 0, 0, 0]
                }
            };

            let currentRevenueFilter = 'today';

            // Initialize chart with 'today' data as the default
            let revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: revenueChartDataSets.today.labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: revenueChartDataSets.today.data,
                        backgroundColor: '#212529',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '₱' + Number(value).toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return 'Revenue: ₱' + Number(context.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    }
                }
            });

            function applyRevenueChartData(datasets) {
                if (!datasets) return;
                revenueChartDataSets = datasets;
                const activeData = revenueChartDataSets[currentRevenueFilter];
                if (activeData) {
                    revenueChart.data.labels = activeData.labels;
                    revenueChart.data.datasets[0].data = activeData.data;
                    revenueChart.update('none');
                }
            }

            // Expose updater for real-time polling in updateAdminCards
            window.updateOverviewRevenueChart = function (datasets) {
                applyRevenueChartData(datasets);
            };

            // Fetch live initial data from Receptionist Billing endpoint
            fetch('/api/admin/revenue-stats', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data && data.revenue_chart) {
                        applyRevenueChartData(data.revenue_chart);
                    }
                })
                .catch(err => {
                    console.error('Failed to load live revenue statistics:', err);
                });

            // Listen for dropdown changes
            const revenueFilterEl = document.getElementById('revenueFilter');
            if (revenueFilterEl) {
                revenueFilterEl.addEventListener('change', function (e) {
                    currentRevenueFilter = e.target.value;
                    const newData = revenueChartDataSets[currentRevenueFilter];
                    if (newData) {
                        revenueChart.data.labels = newData.labels;
                        revenueChart.data.datasets[0].data = newData.data;
                        revenueChart.update();
                    }
                });
            }
        }

        // ==========================================
        // 2. Booking Stay Type Donut Chart (Overview Section)
        // ==========================================
        const donutCanvas = document.getElementById('bookingSourceChart');

        if (donutCanvas) {
            let activeStayTypeTimeframe = 'monthly';
            let stayTypeChart = null;

            const datePicker = document.getElementById('stayTypeDatePicker');
            const clearDateBtn = document.getElementById('clearStayTypeDateBtn');
            const scopeLabel = document.getElementById('stayTypeScopeLabel');
            const exactDateBadge = document.getElementById('stayTypeExactDateBadge');
            const monthSelect = document.getElementById('stayTypeMonthSelect');
            const weekSelect = document.getElementById('stayTypeWeekSelect');
            const yearSelect = document.getElementById('stayTypeYearSelect');
            const timeframeBtns = document.querySelectorAll('.stay-tf-btn');
            const legendRows = document.getElementById('stayTypeLegendRows');

            const stayTypeLabels = ['3 Hours', '6 Hours', '12 Hours', '24 Hours'];
            const stayTypeColorArray = ['#30AFFF', '#10B981', '#F59E0B', '#6A4435'];

            function renderStayTypeLegend(categories, total, isExactDate, dateFormatted) {
                if (!legendRows) return;
                legendRows.innerHTML = '';

                if (!categories || categories.length === 0 || total === 0) {
                    legendRows.innerHTML = `
                        <div class="col-12 text-center text-muted py-2">
                            <small class="fw-semibold">${isExactDate ? `No bookings found on ${dateFormatted}.` : 'No booking records found for this period.'}</small>
                        </div>
                    `;
                    return;
                }

                categories.forEach(cat => {
                    const col = document.createElement('div');
                    col.className = 'col-6';
                    col.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between p-1 px-1.5 rounded bg-light border">
                            <div class="d-flex align-items-center gap-1.5 text-truncate">
                                <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 10px; height: 10px; background-color: ${cat.color};"></span>
                                <span class="fw-semibold text-dark text-truncate" style="font-size: 0.72rem;">${cat.label}</span>
                            </div>
                            <div class="text-end ps-1">
                                <span class="fw-bold text-dark" style="font-size: 0.75rem;">${cat.count}</span>
                                <span class="text-muted" style="font-size: 0.68rem;">(${cat.percentage}%)</span>
                            </div>
                        </div>
                    `;
                    legendRows.appendChild(col);
                });
            }

            // Initialize Chart.js doughnut
            stayTypeChart = new Chart(donutCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: stayTypeLabels,
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: stayTypeColorArray,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false // Using our clean custom badges below
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    return ` ${label}: ${value} booking(s) (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });

            function updateStayTypeVisibility() {
                if (activeStayTypeTimeframe === 'weekly') {
                    if (weekSelect) weekSelect.classList.remove('d-none');
                    if (monthSelect) monthSelect.classList.remove('d-none');
                    if (yearSelect) yearSelect.classList.remove('d-none');
                } else if (activeStayTypeTimeframe === 'monthly') {
                    if (weekSelect) weekSelect.classList.add('d-none');
                    if (monthSelect) monthSelect.classList.remove('d-none');
                    if (yearSelect) yearSelect.classList.remove('d-none');
                } else if (activeStayTypeTimeframe === 'yearly') {
                    if (weekSelect) weekSelect.classList.add('d-none');
                    if (monthSelect) monthSelect.classList.add('d-none');
                    if (yearSelect) yearSelect.classList.remove('d-none');
                }
            }

            let isFetchingStayType = false;

            function fetchStayTypeData() {
                if (isFetchingStayType) return;
                isFetchingStayType = true;

                const hasSpecificDate = Boolean(datePicker && datePicker.value);
                const params = new URLSearchParams({
                    timeframe: activeStayTypeTimeframe,
                    year: yearSelect ? yearSelect.value : '2026',
                    month: monthSelect ? monthSelect.value : 'all',
                    week: (activeStayTypeTimeframe === 'weekly' && weekSelect) ? weekSelect.value : 'all'
                });

                if (hasSpecificDate) {
                    params.set('date', datePicker.value);
                    if (clearDateBtn) clearDateBtn.classList.remove('d-none');
                } else {
                    if (clearDateBtn) clearDateBtn.classList.add('d-none');
                }

                fetch(`/api/admin/stay-type-stats?${params.toString()}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!stayTypeChart) return;

                        const counts = data.counts || [0, 0, 0, 0];
                        const total = data.total || 0;

                        if (total === 0) {
                            // If all 0, show empty placeholder ring with light grey
                            stayTypeChart.data.labels = stayTypeLabels;
                            stayTypeChart.data.datasets[0].data = [1, 0, 0, 0];
                            stayTypeChart.data.datasets[0].backgroundColor = ['#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB'];
                        } else {
                            stayTypeChart.data.labels = data.labels || stayTypeLabels;
                            stayTypeChart.data.datasets[0].data = counts;
                            stayTypeChart.data.datasets[0].backgroundColor = data.colors || stayTypeColorArray;
                        }

                        stayTypeChart.update('none');
                        renderStayTypeLegend(data.categories || [], total, data.is_exact_date, data.selected_date_formatted);

                        // Update Status Labels
                        if (scopeLabel) {
                            if (data.is_exact_date) {
                                scopeLabel.textContent = `Exact Day: ${data.selected_date_formatted}`;
                            } else {
                                const tfTitle = activeStayTypeTimeframe.charAt(0).toUpperCase() + activeStayTypeTimeframe.slice(1);
                                if (activeStayTypeTimeframe === 'weekly') {
                                    const wText = (weekSelect && weekSelect.value !== 'all') ? `Week ${weekSelect.value}` : 'All Weeks';
                                    scopeLabel.textContent = `Timeframe: Weekly (${wText}, ${monthSelect ? monthSelect.options[monthSelect.selectedIndex]?.text : ''})`;
                                } else if (activeStayTypeTimeframe === 'yearly') {
                                    scopeLabel.textContent = `Timeframe: Yearly (${yearSelect ? yearSelect.value : ''})`;
                                } else {
                                    const mText = (monthSelect && monthSelect.value !== 'all') ? monthSelect.options[monthSelect.selectedIndex]?.text : 'All Months';
                                    scopeLabel.textContent = `Timeframe: Monthly (${mText} ${yearSelect ? yearSelect.value : ''})`;
                                }
                            }
                        }

                        if (exactDateBadge) {
                            if (data.is_exact_date) {
                                exactDateBadge.textContent = 'Exact Date Filter Active';
                                exactDateBadge.className = 'badge bg-success-subtle text-success border border-success-subtle fw-semibold';
                                exactDateBadge.classList.remove('d-none');
                            } else {
                                exactDateBadge.classList.add('d-none');
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching stay type stats:', err);
                    })
                    .finally(() => {
                        isFetchingStayType = false;
                    });
            }

            // Timeframe button switching (Weekly, Monthly, Yearly)
            timeframeBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    timeframeBtns.forEach(b => {
                        b.classList.remove('active');
                        b.classList.add('btn-outline-secondary');
                        b.style.backgroundColor = '';
                        b.style.borderColor = '';
                        b.style.color = '';
                    });

                    this.classList.add('active');
                    this.classList.remove('btn-outline-secondary');
                    this.style.backgroundColor = '#6A4435';
                    this.style.borderColor = '#6A4435';
                    this.style.color = '#ffffff';

                    activeStayTypeTimeframe = this.getAttribute('data-tf');
                    updateStayTypeVisibility();
                    fetchStayTypeData();
                });
            });

            // Specific day calendar date picker changes
            if (datePicker) {
                datePicker.addEventListener('change', function () {
                    if (this.value) {
                        const [y, m, d] = this.value.split('-').map(Number);
                        if (yearSelect) yearSelect.value = y;
                        if (monthSelect) monthSelect.value = m;
                        if (weekSelect) {
                            const w = Math.min(5, Math.ceil(d / 7));
                            weekSelect.value = w;
                        }
                        if (clearDateBtn) clearDateBtn.classList.remove('d-none');
                    } else {
                        if (clearDateBtn) clearDateBtn.classList.add('d-none');
                    }
                    fetchStayTypeData();
                });
            }

            // Clear date filter button: restores the data based on selected timeframe
            if (clearDateBtn) {
                clearDateBtn.addEventListener('click', function () {
                    if (datePicker) {
                        datePicker.value = '';
                    }
                    this.classList.add('d-none');
                    fetchStayTypeData();
                });
            }

            // Month select changes (switching month clears specific day filter and restores timeframe)
            if (monthSelect) {
                monthSelect.addEventListener('change', function () {
                    if (datePicker && datePicker.value) {
                        datePicker.value = '';
                        if (clearDateBtn) clearDateBtn.classList.add('d-none');
                    }
                    fetchStayTypeData();
                });
            }

            // Year select changes (switching year clears specific day filter and restores timeframe)
            if (yearSelect) {
                yearSelect.addEventListener('change', function () {
                    if (datePicker && datePicker.value) {
                        datePicker.value = '';
                        if (clearDateBtn) clearDateBtn.classList.add('d-none');
                    }
                    fetchStayTypeData();
                });
            }

            // Week select changes (switching week clears specific day filter and restores timeframe)
            if (weekSelect) {
                weekSelect.addEventListener('change', function () {
                    if (datePicker && datePicker.value) {
                        datePicker.value = '';
                        if (clearDateBtn) clearDateBtn.classList.add('d-none');
                    }
                    fetchStayTypeData();
                });
            }

            // Initial load
            updateStayTypeVisibility();
            fetchStayTypeData();

            // Real-time synchronization every 5 seconds with Receptionist bookings
            setInterval(fetchStayTypeData, 5000);
        }

        // ==========================================
        // 3. Sales Reports Integration (Receptionist Billing & Targets)
        // ==========================================
        const trendCanvas = document.getElementById('occupancyTrendChart');
        const gaugeElement = document.querySelector("#revenueGaugeChart");
        const breakdownCanvas = document.getElementById('revenueBreakdownChart');
        const amenitiesListEl = document.getElementById('amenitiesChartList');

        if (trendCanvas || gaugeElement || breakdownCanvas || amenitiesListEl) {
            let activeOccupancyPeriod = 'weekly';
            let activeBreakdownPeriod = 'weekly';
            let activeAmenitiesPeriod = 'weekly';
            let cachedSalesData = null;

            // Chart Instances
            let occupancyTrendChart = null;
            let gaugeChart = null;
            let revenueBreakdownChart = null;

            // Initialize Occupancy Trend Line Chart
            if (trendCanvas) {
                occupancyTrendChart = new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                        datasets: [{
                            label: 'Occupancy Rate (%)',
                            data: [0, 0, 0, 0, 0],
                            borderColor: '#30AFFF',
                            backgroundColor: 'rgba(48, 175, 255, 0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function (value) { return value + '%'; }
                                }
                            }
                        }
                    }
                });

                const trendFilterBtns = document.querySelectorAll('.occupancy-trend-btn');
                trendFilterBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        activeOccupancyPeriod = this.getAttribute('data-period');
                        trendFilterBtns.forEach(b => {
                            b.classList.remove('active');
                            b.style.backgroundColor = 'transparent';
                            b.style.borderColor = '#734F46';
                            b.style.color = '#734F46';
                        });
                        this.classList.add('active');
                        this.style.backgroundColor = '#443627';
                        this.style.borderColor = '#443627';
                        this.style.color = '#ffffff';

                        updateOccupancyChart();
                    });
                });
            }

            // Initialize Revenue Target Gauge Chart (ApexCharts)
            if (gaugeElement && window.ApexCharts) {
                const gaugeOptions = {
                    series: [0],
                    chart: {
                        type: 'radialBar',
                        height: 280,
                        offsetY: -10
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -135,
                            endAngle: 135,
                            track: {
                                background: "#f2f2f2",
                                strokeWidth: '97%',
                                margin: 5,
                            },
                            dataLabels: {
                                name: {
                                    fontSize: '14px',
                                    color: '#443627',
                                    offsetY: 120
                                },
                                value: {
                                    offsetSum: 0,
                                    fontSize: '22px',
                                    fontWeight: 'bold',
                                    color: '#212529',
                                    offsetY: 60,
                                    formatter: function (val) {
                                        return val + "%";
                                    }
                                }
                            }
                        }
                    },
                    fill: {
                        type: "solid",
                        colors: ["#30AFFF"]
                    },
                    stroke: {
                        dashArray: 4
                    },
                    labels: ['Achieved of ₱150k Goal'],
                };
                gaugeChart = new ApexCharts(gaugeElement, gaugeOptions);
                gaugeChart.render();
            }

            // Initialize Revenue Breakdown Stacked Bar Chart
            if (breakdownCanvas) {
                revenueBreakdownChart = new Chart(breakdownCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                        datasets: [
                            {
                                label: 'Base Room Rate',
                                data: [0, 0, 0, 0, 0],
                                backgroundColor: '#30AFFF',
                                borderRadius: 4
                            },
                            {
                                label: 'Extra Charges & Add-ons',
                                data: [0, 0, 0, 0, 0],
                                backgroundColor: '#FFCED8',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true,
                                grid: { display: false }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return '₱' + Number(value).toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    color: '#443627'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += '₱' + Number(context.parsed.y).toLocaleString();
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });

                const breakdownFilterBtns = document.querySelectorAll('.revenue-breakdown-btn');
                breakdownFilterBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        activeBreakdownPeriod = this.getAttribute('data-period');
                        breakdownFilterBtns.forEach(b => {
                            b.classList.remove('active');
                            b.style.backgroundColor = 'transparent';
                            b.style.borderColor = '#734F46';
                            b.style.color = '#734F46';
                        });
                        this.classList.add('active');
                        this.style.backgroundColor = '#443627';
                        this.style.borderColor = '#443627';
                        this.style.color = '#ffffff';

                        updateBreakdownChart();
                    });
                });
            }

            // Amenities Filter Buttons
            const amenitiesFilterBtns = document.querySelectorAll('.amenities-filter-btn');
            amenitiesFilterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    activeAmenitiesPeriod = this.getAttribute('data-period');
                    amenitiesFilterBtns.forEach(b => {
                        b.classList.remove('active');
                        b.style.backgroundColor = 'transparent';
                        b.style.borderColor = '#734F46';
                        b.style.color = '#734F46';
                    });
                    this.classList.add('active');
                    this.style.backgroundColor = '#443627';
                    this.style.borderColor = '#443627';
                    this.style.color = '#ffffff';

                    renderAmenitiesComparison();
                });
            });

            // Helpers to update UI from cachedSalesData
            function updateOccupancyChart() {
                if (!occupancyTrendChart || !cachedSalesData || !cachedSalesData.occupancy_trend) return;
                const dataset = cachedSalesData.occupancy_trend[activeOccupancyPeriod];
                if (dataset) {
                    occupancyTrendChart.data.labels = dataset.labels;
                    occupancyTrendChart.data.datasets[0].data = dataset.data;
                    occupancyTrendChart.update('none');
                }
            }

            function updateBreakdownChart() {
                if (!revenueBreakdownChart || !cachedSalesData || !cachedSalesData.revenue_breakdown) return;
                const dataset = cachedSalesData.revenue_breakdown[activeBreakdownPeriod];
                if (dataset) {
                    revenueBreakdownChart.data.labels = dataset.labels;
                    revenueBreakdownChart.data.datasets[0].data = dataset.roomRate;
                    revenueBreakdownChart.data.datasets[1].data = dataset.extraCharges;
                    revenueBreakdownChart.update('none');
                }
            }

            function renderAmenitiesComparison() {
                if (!amenitiesListEl || !cachedSalesData) return;
                const amenitiesSubtitleEl = document.getElementById('amenitiesDateSubtitle');
                if (amenitiesSubtitleEl) {
                    amenitiesSubtitleEl.textContent = cachedSalesData.period_title || '';
                }

                const items = (cachedSalesData.amenities_comparison && cachedSalesData.amenities_comparison.items)
                    ? cachedSalesData.amenities_comparison.items
                    : [];

                if (items.length === 0) {
                    amenitiesListEl.innerHTML = '<div class="text-center text-muted py-4 small">No amenities recorded for this period.</div>';
                    return;
                }

                const sortedItems = [...items].sort((a, b) => b.count - a.count);
                const totalPurchases = sortedItems.reduce((sum, item) => sum + item.count, 0);
                const maxCount = sortedItems.length > 0 ? Math.max(1, sortedItems[0].count) : 1;

                amenitiesListEl.innerHTML = sortedItems.map((item, index) => {
                    const rank = index + 1;
                    const barWidth = maxCount > 0 ? ((item.count / maxCount) * 100).toFixed(1) : 0;
                    const sharePercent = totalPurchases > 0 ? ((item.count / totalPurchases) * 100).toFixed(1) : '0.0';

                    return `
                        <div class="p-2 p-sm-3 rounded" style="background-color: rgba(115, 79, 70, 0.05); border: 1px solid rgba(115, 79, 70, 0.15);">
                            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                <div class="d-flex align-items-center gap-2 text-truncate">
                                    <span class="badge rounded-pill fw-bold" style="background-color: #734F46; color: #ffffff; min-width: 34px;">
                                        #${rank}
                                    </span>
                                    <span class="fw-semibold text-dark text-truncate">${item.name}</span>
                                </div>
                                <span class="fw-bold text-nowrap" style="color: #734F46;">
                                    ${item.count.toLocaleString()} ${item.count === 1 ? 'purchase' : 'purchases'}
                                </span>
                            </div>
                            <div class="w-100 rounded-pill overflow-hidden mb-2" style="height: 10px; background-color: rgba(115, 79, 70, 0.15);">
                                <div class="h-100 rounded-pill" style="width: ${barWidth}%; background-color: #734F46; transition: width 0.35s ease;"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <span>Revenue Generated: <strong class="text-dark">₱${Number(item.revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></span>
                                <span>Share: <strong style="color: #734F46;">${sharePercent}%</strong></span>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            let isFetchingSalesReport = false;

            // Main Data Fetcher
            function fetchSalesReport() {
                if (isFetchingSalesReport) return;
                isFetchingSalesReport = true;

                const yearSelect = document.getElementById('salesFilterYear');
                const monthSelect = document.getElementById('salesFilterMonth');
                const dateInput = document.getElementById('salesFilterDate');
                const subtitleEl = document.getElementById('salesFilterSubtitle');

                const yearVal = yearSelect ? yearSelect.value : new Date().getFullYear();
                const monthVal = monthSelect ? monthSelect.value : (new Date().getMonth() + 1);
                const dateVal = dateInput ? dateInput.value : '';

                const params = new URLSearchParams();
                if (yearVal) params.append('year', yearVal);
                if (monthVal) params.append('month', monthVal);
                if (dateVal) params.append('date', dateVal);

                fetch('/api/admin/sales-report?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('Failed to fetch sales reports');
                        return res.json();
                    })
                    .then(data => {
                        cachedSalesData = data;

                        // Subtitle
                        if (subtitleEl) {
                            subtitleEl.textContent = 'Showing live sales data for: ' + (data.period_title || 'Selected Period');
                        }

                        // Top Metric Cards
                        const revTitleEl = document.getElementById('salesTotalRevenueTitle');
                        const revValEl = document.getElementById('salesTotalRevenueValue');
                        const adrValEl = document.getElementById('salesAdrValue');
                        const bookingsValEl = document.getElementById('salesTotalBookingsValue');
                        const targetValEl = document.getElementById('monthlyTargetValue');

                        if (revTitleEl) revTitleEl.textContent = data.revenue_card_title || 'Total Revenue';
                        if (revValEl) revValEl.textContent = data.total_revenue_formatted || '₱0.00';
                        if (adrValEl) adrValEl.textContent = data.adr_formatted || '₱0.00';
                        if (bookingsValEl) bookingsValEl.textContent = (data.total_bookings || 0).toLocaleString();
                        if (targetValEl) targetValEl.textContent = data.monthly_target_formatted || '₱150,000';

                        // Gauge Chart Update
                        if (gaugeChart) {
                            const progress = data.monthly_target_progress || 0;
                            gaugeChart.updateSeries([progress], false);
                            gaugeChart.updateOptions({
                                labels: ['Achieved of ' + (data.monthly_target_formatted || '₱150k') + ' Goal']
                            }, false, false);
                        }

                        // Lost Revenue (Cancellations)
                        const cancelBadgeEl = document.getElementById('salesCancellationsBadge');
                        const cancelLostEl = document.getElementById('salesCancellationsLost');
                        if (cancelBadgeEl) {
                            cancelBadgeEl.textContent = (data.cancellations_count || 0) + ' Booking' + ((data.cancellations_count === 1) ? '' : 's');
                        }
                        if (cancelLostEl) {
                            cancelLostEl.textContent = data.lost_revenue_formatted || '-₱0.00';
                        }

                        // Pending Payments
                        const pendingBadgeEl = document.getElementById('salesPendingGuestsBadge');
                        const pendingAmountEl = document.getElementById('salesPendingAmount');
                        if (pendingBadgeEl) {
                            pendingBadgeEl.textContent = (data.pending_guests_count || 0) + ' Guest' + ((data.pending_guests_count === 1) ? '' : 's');
                        }
                        if (pendingAmountEl) {
                            pendingAmountEl.textContent = data.pending_amount_formatted || '₱0.00';
                        }

                        // Update charts & amenities
                        updateOccupancyChart();
                        updateBreakdownChart();
                        renderAmenitiesComparison();
                    })
                    .catch(err => {
                        console.error('Sales report fetch error:', err);
                    })
                    .finally(() => {
                        isFetchingSalesReport = false;
                    });
            }

            // Filter Event Listeners
            const yearSelect = document.getElementById('salesFilterYear');
            const monthSelect = document.getElementById('salesFilterMonth');
            const dateInput = document.getElementById('salesFilterDate');
            const clearDateBtn = document.getElementById('salesFilterClearDateBtn');
            const thisMonthBtn = document.getElementById('salesFilterThisMonthBtn');
            const todayBtn = document.getElementById('salesFilterTodayBtn');

            if (yearSelect) yearSelect.addEventListener('change', () => {
                if (dateInput) { dateInput.value = ''; }
                if (clearDateBtn) { clearDateBtn.classList.add('d-none'); }
                fetchSalesReport();
            });

            if (monthSelect) monthSelect.addEventListener('change', () => {
                if (dateInput) { dateInput.value = ''; }
                if (clearDateBtn) { clearDateBtn.classList.add('d-none'); }
                fetchSalesReport();
            });

            if (dateInput) {
                dateInput.addEventListener('change', () => {
                    if (dateInput.value) {
                        if (clearDateBtn) clearDateBtn.classList.remove('d-none');
                    } else {
                        if (clearDateBtn) clearDateBtn.classList.add('d-none');
                    }
                    fetchSalesReport();
                });
            }

            if (clearDateBtn) {
                clearDateBtn.addEventListener('click', () => {
                    if (dateInput) dateInput.value = '';
                    clearDateBtn.classList.add('d-none');
                    fetchSalesReport();
                });
            }

            if (thisMonthBtn) {
                thisMonthBtn.addEventListener('click', () => {
                    const now = new Date();
                    if (yearSelect) yearSelect.value = now.getFullYear();
                    if (monthSelect) monthSelect.value = (now.getMonth() + 1);
                    if (dateInput) dateInput.value = '';
                    if (clearDateBtn) clearDateBtn.classList.add('d-none');
                    fetchSalesReport();
                });
            }

            if (todayBtn) {
                todayBtn.addEventListener('click', () => {
                    const now = new Date();
                    const y = now.getFullYear();
                    const m = String(now.getMonth() + 1).padStart(2, '0');
                    const d = String(now.getDate()).padStart(2, '0');
                    const dateStr = `${y}-${m}-${d}`;
                    if (yearSelect) yearSelect.value = y;
                    if (monthSelect) monthSelect.value = (now.getMonth() + 1);
                    if (dateInput) {
                        dateInput.value = dateStr;
                        if (clearDateBtn) clearDateBtn.classList.remove('d-none');
                    }
                    fetchSalesReport();
                });
            }

            // Target Modal AJAX Submission
            const setTargetForm = document.getElementById('setTargetForm');
            if (setTargetForm) {
                setTargetForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formAction = this.getAttribute('action') || '/api/admin/monthly-target';
                    const targetInput = document.getElementById('monthlyTargetInput');
                    const modalMonthSelect = document.getElementById('monthlyTargetMonthSelect');
                    const modalYearSelect = document.getElementById('monthlyTargetYearSelect');
                    const alertBox = document.getElementById('setTargetAlert');
                    const submitBtn = document.getElementById('saveTargetSubmitBtn');

                    if (alertBox) alertBox.classList.add('d-none');
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                    fetch(formAction, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            year: parseInt(modalYearSelect ? modalYearSelect.value : new Date().getFullYear(), 10),
                            month: parseInt(modalMonthSelect ? modalMonthSelect.value : (new Date().getMonth() + 1), 10),
                            target_amount: parseFloat(targetInput ? targetInput.value : 0)
                        })
                    })
                        .then(res => res.json())
                        .then(resData => {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Save Target'; }

                            if (resData.status === 'success') {
                                const modalEl = document.getElementById('setTargetModal');
                                if (modalEl && window.bootstrap && bootstrap.Modal) {
                                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                                    if (modalInstance) modalInstance.hide();
                                }
                                fetchSalesReport();
                            } else {
                                if (alertBox) {
                                    alertBox.textContent = resData.message || 'Error updating monthly target.';
                                    alertBox.classList.remove('d-none');
                                }
                            }
                        })
                        .catch(err => {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Save Target'; }
                            if (alertBox) {
                                alertBox.textContent = 'Failed to save monthly target. Please check your inputs.';
                                alertBox.classList.remove('d-none');
                            }
                        });
                });
            }

            // Initial fetch
            fetchSalesReport();

            // Real-time synchronization every 5 seconds with Receptionist billing and transactions
            setInterval(fetchSalesReport, 5000);
        }

        // ==========================================
        // 6. Real-Time Overview Cards Sync
        // ==========================================
        const occupancyEl = document.getElementById('admin-occupancy-count');
        const totalRoomsEl = document.getElementById('admin-total-rooms');
        const revenueEl = document.getElementById('admin-revenue-today');
        const checkinsEl = document.getElementById('admin-checkins-today');
        const checkoutsEl = document.getElementById('admin-checkouts-today');
        const checkinsModalBody = document.getElementById('checkinsModalBody');
        const checkoutsModalBody = document.getElementById('checkoutsModalBody');

        if (occupancyEl && revenueEl && checkinsEl && checkoutsEl) {
            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function updateAdminCards() {
                fetch('/api/admin/overview-stats', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (occupancyEl) occupancyEl.textContent = data.occupancy_today;
                        if (totalRoomsEl) totalRoomsEl.textContent = data.total_rooms;
                        if (revenueEl) revenueEl.textContent = data.revenue_today_formatted;
                        if (checkinsEl) checkinsEl.textContent = data.checkins_today;
                        if (checkoutsEl) checkoutsEl.textContent = data.checkouts_today;

                        if (data.revenue_chart && window.updateOverviewRevenueChart) {
                            window.updateOverviewRevenueChart(data.revenue_chart);
                        }

                        if (checkinsModalBody && Array.isArray(data.checkins_list)) {
                            if (data.checkins_list.length === 0) {
                                checkinsModalBody.innerHTML = '<tr><td colspan="2" class="py-4 text-center text-muted">No check-ins recorded today.</td></tr>';
                            } else {
                                checkinsModalBody.innerHTML = data.checkins_list.map(item => `
                                <tr>
                                    <td class="py-3 px-4 fw-semibold">${escapeHtml(item.name)}</td>
                                    <td class="py-3 px-4 text-muted">${escapeHtml(item.data)}</td>
                                </tr>
                            `).join('');
                            }
                        }

                        if (checkoutsModalBody && Array.isArray(data.checkouts_list)) {
                            if (data.checkouts_list.length === 0) {
                                checkoutsModalBody.innerHTML = '<tr><td colspan="2" class="py-4 text-center text-muted">No check-outs recorded today.</td></tr>';
                            } else {
                                checkoutsModalBody.innerHTML = data.checkouts_list.map(item => `
                                <tr>
                                    <td class="py-3 px-4 fw-semibold">${escapeHtml(item.name)}</td>
                                    <td class="py-3 px-4 text-muted">${escapeHtml(item.data)}</td>
                                </tr>
                            `).join('');
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Failed to sync admin overview stats:', err);
                    });
            }

            // Sync every 5 seconds
            setInterval(updateAdminCards, 5000);
        }

        // ==========================================
        // 7. Admin Notifications Sync (Due checkouts & cancellations)
        // ==========================================
        const adminNotifList = document.getElementById('admin-notification-list');
        const adminNotifModalBody = document.getElementById('adminNotificationsModalBody');

        if (adminNotifList || adminNotifModalBody) {
            function escapeNotifHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function buildDueGuestItem(guest, isModal = false) {
                const timeText = guest.is_overdue
                    ? `<span class="text-danger fw-semibold">was due at ${escapeNotifHtml(guest.checkout_time)} (overdue)</span>`
                    : `${escapeNotifHtml(guest.checkout_time)} is due checkout (${escapeNotifHtml(guest.minutes)}m left)`;

                const roomDetails = (isModal && guest.booked_rooms_details)
                    ? `<div class="text-secondary mt-1" style="font-size: 0.73rem;"><strong>Booked Room(s):</strong> ${escapeNotifHtml(guest.booked_rooms_details)} &bull; <strong>Stay:</strong> ${escapeNotifHtml(guest.stay_type)}</div>`
                    : '';

                return `
                    <div class="d-flex align-items-start gap-2 py-2 border-bottom small">
                        <span class="text-warning fw-bold fs-6">&bull;</span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-dark">RM ${escapeNotifHtml(guest.room_number)} &mdash; ${escapeNotifHtml(guest.name)}</span>
                                <span class="badge ${guest.is_overdue ? 'bg-danger' : 'bg-warning text-dark'}" style="font-size: 0.65rem;">${guest.is_overdue ? 'Overdue' : 'Due Soon'}</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">${timeText}</div>
                            ${roomDetails}
                        </div>
                    </div>
                `;
            }

            function buildCancellationItem(item, isModal = false) {
                const roomDetails = (isModal && item.booked_rooms_details)
                    ? `<div class="text-secondary mt-1" style="font-size: 0.73rem;"><strong>Room(s):</strong> ${escapeNotifHtml(item.booked_rooms_details)} &bull; <strong>Dates:</strong> ${item.dates || 'N/A'}</div>`
                    : '';

                return `
                    <div class="d-flex align-items-start gap-2 py-2 border-bottom small">
                        <span class="text-danger fw-bold fs-6">&times;</span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-dark">${escapeNotifHtml(item.guest_name)}</span>
                                <span class="badge bg-secondary" style="font-size: 0.65rem;">${escapeNotifHtml(item.type)} Cancelled</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted" style="font-size: 0.72rem;">Room: ${escapeNotifHtml(item.room_number)} &bull; ${escapeNotifHtml(item.cancelled_at)}</span>
                                <span class="fw-bold text-danger" style="font-size: 0.75rem;">-${escapeNotifHtml(item.deposit_formatted)}</span>
                            </div>
                            ${roomDetails}
                        </div>
                    </div>
                `;
            }

            function updateAdminNotificationsUI(data) {
                const dueGuests = Array.isArray(data.due_guests) ? data.due_guests : [];
                const cancellations = Array.isArray(data.cancellations) ? data.cancellations : [];

                // 1. Overview Card: Show only the 3 most recent notifications
                if (adminNotifList) {
                    const cardItems = [];
                    dueGuests.forEach(g => cardItems.push(buildDueGuestItem(g, false)));
                    cancellations.forEach(c => cardItems.push(buildCancellationItem(c, false)));

                    if (cardItems.length === 0) {
                        adminNotifList.innerHTML = `
                            <div class="text-center text-muted mt-4">
                                <small>No recent notifications.</small>
                            </div>
                        `;
                    } else {
                        // Display only the 3 most recent notifications
                        adminNotifList.innerHTML = cardItems.slice(0, 3).join('');
                    }
                }

                // 2. See More Modal: Show all revenue losses and due/overdue records with booked room details
                if (adminNotifModalBody) {
                    const dueHtml = dueGuests.length > 0
                        ? dueGuests.map(g => buildDueGuestItem(g, true)).join('')
                        : `
                            <div class="text-center py-3 text-secondary">
                                <p class="mb-0 fw-semibold fst-italic" style="font-style: italic;">No rooms due within the next 30 minutes.</p>
                                <small class="text-muted d-block mt-1">Guests nearing their check-out time appear here automatically.</small>
                            </div>
                        `;

                    const cancelHtml = cancellations.length > 0
                        ? cancellations.map(c => buildCancellationItem(c, true)).join('')
                        : `
                            <div class="text-center py-3 text-secondary">
                                <p class="mb-0 fw-semibold fst-italic" style="font-style: italic;">No cancellation records found.</p>
                                <small class="text-muted d-block mt-1">Cancelled reservations and bookings with revenue loss appear here.</small>
                            </div>
                        `;

                    adminNotifModalBody.innerHTML = `
                        <!-- Top Summary Statistics Bar -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light text-center">
                                    <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Upcoming & Due</small>
                                    <span class="fs-6 fw-bold text-dark">${dueGuests.length} Guest${dueGuests.length === 1 ? '' : 's'}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light text-center">
                                    <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Total Lost Revenue</small>
                                    <span class="fs-6 fw-bold text-danger">${escapeNotifHtml(data.lost_revenue_total_formatted || '₱0.00')}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 1: Upcoming & Due Checkouts -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
                                <span>UPCOMING &amp; DUE CHECKOUTS (30-MIN ALERT)</span>
                                <span class="badge bg-secondary" style="font-size: 0.65rem;">${dueGuests.length}</span>
                            </h6>
                            <div>${dueHtml}</div>
                        </div>

                        <!-- Section 2: Cancellation Revenue Losses -->
                        <div>
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
                                <span>CANCELLATION REVENUE LOSSES</span>
                                <span class="badge bg-danger" style="font-size: 0.65rem;">${cancellations.length} Cancelled</span>
                            </h6>
                            <div>${cancelHtml}</div>
                        </div>
                    `;
                }
            }

            function fetchAdminNotifications() {
                fetch('/api/admin/notifications', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network error loading notifications');
                        return response.json();
                    })
                    .then(data => {
                        updateAdminNotificationsUI(data);
                    })
                    .catch(err => {
                        console.error('[Admin Notifications] Failed to fetch:', err);
                    });
            }

            // Initial fetch & poll every 5 seconds
            fetchAdminNotifications();
            setInterval(fetchAdminNotifications, 5000);
        }

    });
</script>

{{-- ==========================================
     Staff Section — Mobile Mode & Row Actions
     ========================================== --}}
<script>
(function () {
    function initStaffSection() {
        let activeStaffMode = null; // null | 'edit' | 'delete'

        const editBtn    = document.getElementById('staffMobileEditBtn');
        const deleteBtn  = document.getElementById('staffMobileDeleteBtn');
        const noticeBox  = document.getElementById('staffMobileSelectionNotice');
        const noticeText = document.getElementById('staffMobileSelectionNoticeText');
        const closeBtn   = document.getElementById('closeStaffMobileNoticeBtn');
        const staffTable = document.querySelector('.staff-table');

        // Only initialize when Staff section elements are present
        if (!staffTable && !editBtn && !deleteBtn) {
            return;
        }

        function setStaffMode(mode) {
            if (activeStaffMode === mode) {
                clearStaffMode();
                return;
            }

            activeStaffMode = mode;

            if (staffTable) {
                staffTable.classList.remove('mode-edit', 'mode-delete');
                if (mode) { staffTable.classList.add('mode-' + mode); }
            }

            if (editBtn) {
                if (mode === 'edit') {
                    editBtn.classList.add('btn-dark', 'active');
                    editBtn.classList.remove('btn-outline-dark');
                } else {
                    editBtn.classList.remove('btn-dark', 'active');
                    editBtn.classList.add('btn-outline-dark');
                }
            }

            if (deleteBtn) {
                if (mode === 'delete') {
                    deleteBtn.classList.add('active', 'border-2', 'border-dark');
                } else {
                    deleteBtn.classList.remove('active', 'border-2', 'border-dark');
                }
            }

            if (mode === 'edit') {
                if (noticeBox)  { noticeBox.className = 'alert alert-info alert-dismissible fade show py-2 px-3 small mb-3 d-md-none shadow-sm'; }
                if (noticeText) { noticeText.textContent = 'Please select a staff member by clicking its table row to edit.'; }
                if (noticeBox)  { noticeBox.classList.remove('d-none'); noticeBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            } else if (mode === 'delete') {
                if (noticeBox)  { noticeBox.className = 'alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3 d-md-none shadow-sm'; }
                if (noticeText) { noticeText.textContent = 'Please select a staff member by clicking its table row to delete.'; }
                if (noticeBox)  { noticeBox.classList.remove('d-none'); noticeBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            } else {
                if (noticeBox) { noticeBox.classList.add('d-none'); }
            }
        }

        function clearStaffMode() {
            activeStaffMode = null;
            if (staffTable)  { staffTable.classList.remove('mode-edit', 'mode-delete'); }
            if (editBtn)     { editBtn.classList.remove('btn-dark', 'active'); editBtn.classList.add('btn-outline-dark'); }
            if (deleteBtn)   { deleteBtn.classList.remove('active', 'border-2', 'border-dark'); }
            if (noticeBox)   { noticeBox.classList.add('d-none'); }
        }

        if (closeBtn) { closeBtn.addEventListener('click', clearStaffMode); }

        function executeStaffEdit(id) {
            const modalEl = document.getElementById('editStaffModal' + id);
            if (modalEl) {
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else {
                    $(modalEl).modal('show');
                }
            }
        }

        function executeStaffDelete(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"?')) {
                const form = document.getElementById('staffDeleteForm' + id);
                if (form) { form.submit(); }
            }
        }

        document.querySelectorAll('.staff-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                if (e.target.closest('button') || e.target.closest('form')) { return; }
                const id   = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                if (activeStaffMode === 'edit') {
                    clearStaffMode();
                    executeStaffEdit(id);
                } else if (activeStaffMode === 'delete') {
                    clearStaffMode();
                    executeStaffDelete(id, name);
                }
            });
        });

        if (editBtn)   { editBtn.addEventListener('click',   function () { setStaffMode('edit'); }); }
        if (deleteBtn) { deleteBtn.addEventListener('click', function () { setStaffMode('delete'); }); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStaffSection);
    } else {
        initStaffSection();
    }
})();
</script>

{{-- ==========================================
     Admin Room Management Filter & Live Sync
     ========================================== --}}
<script>
(function () {
    function initAdminRoomsSection() {
        const filterGroup = document.getElementById('adminRoomFilterGroup');
        const hotelRoomGrid = document.getElementById('hotelRoomGrid');
        if (!filterGroup || !hotelRoomGrid) return;

        const filterButtons = filterGroup.querySelectorAll('.btn-filter');
        let currentFilter = 'all';

        function applyCurrentFilter() {
            const roomCards = hotelRoomGrid.querySelectorAll('.hotel-room-card');
            roomCards.forEach(function (card) {
                const cardStatus = card.getAttribute('data-status');
                if (currentFilter === 'all') {
                    card.style.display = '';
                } else if (cardStatus === currentFilter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        filterButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filterButtons.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                currentFilter = this.getAttribute('data-filter') || 'all';
                applyCurrentFilter();
            });
        });

        // Real-time synchronization of room statuses, times, and KPI counts with Receptionist side
        const kpiAvailable = document.getElementById('admin-kpi-available');
        const kpiOccupied = document.getElementById('admin-kpi-occupied');
        const kpiReserved = document.getElementById('admin-kpi-reserved');
        const kpiMaintenance = document.getElementById('admin-kpi-maintenance');

        const badgeHtmlMap = {
            available: '<span class="badge bg-success">Available</span>',
            occupied: '<span class="badge bg-danger">Occupied</span>',
            reserved: '<span class="badge bg-warning text-dark">Reserved</span>',
            under_maintenance: '<span class="badge bg-secondary">Under Maintenance</span>',
            maintenance: '<span class="badge bg-secondary">Under Maintenance</span>'
        };

        const overlayClassMap = {
            occupied: 'overlay-occupied',
            reserved: 'overlay-reserved',
            under_maintenance: 'overlay-maintenance',
            maintenance: 'overlay-maintenance'
        };

        let isSyncingRooms = false;

        function syncAdminRooms() {
            if (isSyncingRooms) return;
            isSyncingRooms = true;

            fetch('/api/dashboard/notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (!data) return;

                    // Update KPI counters
                    if (data.kpi) {
                        if (kpiAvailable && data.kpi.available !== undefined) kpiAvailable.textContent = data.kpi.available;
                        if (kpiOccupied && data.kpi.occupied !== undefined) kpiOccupied.textContent = data.kpi.occupied;
                        if (kpiReserved && data.kpi.reserved !== undefined) kpiReserved.textContent = data.kpi.reserved;
                        if (kpiMaintenance && data.kpi.maintenance !== undefined) kpiMaintenance.textContent = data.kpi.maintenance;

                        // Update total room count label in Admin Room Management
                        if (data.kpi.total !== undefined) {
                            const totalRoomsLabel = document.getElementById('admin-total-rooms-label');
                            if (totalRoomsLabel) totalRoomsLabel.textContent = data.kpi.total;
                            // Also update admin-total-rooms in Overview if present on this page
                            const adminTotalRoomsEl = document.getElementById('admin-total-rooms');
                            if (adminTotalRoomsEl) adminTotalRoomsEl.textContent = data.kpi.total;
                        }
                    }

                    if (data.room_statuses) {
                        let hasChanges = false;
                        const roomCards = hotelRoomGrid.querySelectorAll('.hotel-room-card');

                        roomCards.forEach(function (card) {
                            const roomId = card.getAttribute('data-room-id');
                            if (!roomId || !data.room_statuses.hasOwnProperty(roomId)) return;

                            const rawStatus = data.room_statuses[roomId];
                            const normalizedStatus = (rawStatus === 'maintenance') ? 'under_maintenance' : rawStatus;
                            const oldStatus = card.getAttribute('data-status');

                            // Check checkout datetime / overdue
                            let isOverdue = false;
                            if (data.room_checkouts && data.room_checkouts[roomId]) {
                                card.setAttribute('data-checkout', data.room_checkouts[roomId]);
                                const coTime = new Date(data.room_checkouts[roomId]).getTime();
                                if (!isNaN(coTime) && Date.now() > coTime) {
                                    isOverdue = true;
                                }
                            }

                            if (oldStatus !== normalizedStatus) {
                                hasChanges = true;
                                card.setAttribute('data-status', normalizedStatus);
                                card.classList.toggle('is-occupied', normalizedStatus === 'occupied');

                                // Update overlay
                                const wrapper = card.querySelector('.room-image-wrapper');
                                if (wrapper) {
                                    ['overlay-occupied', 'overlay-reserved', 'overlay-maintenance', 'overlay-overdue'].forEach(cls => {
                                        const el = wrapper.querySelector('.' + cls);
                                        if (el) el.remove();
                                    });

                                    let targetOverlayClass = overlayClassMap[normalizedStatus];
                                    if (normalizedStatus === 'occupied' && isOverdue) {
                                        targetOverlayClass = 'overlay-overdue';
                                    }

                                    if (targetOverlayClass) {
                                        const overlay = document.createElement('div');
                                        overlay.className = 'position-absolute top-0 start-0 w-100 h-100 ' + targetOverlayClass;
                                        if (targetOverlayClass === 'overlay-overdue') {
                                            overlay.textContent = 'OVERDUE';
                                        }
                                        wrapper.appendChild(overlay);
                                    }
                                }

                                // Update Badge
                                const badgeContainer = card.querySelector('.room-card-body .d-flex.justify-content-between.align-items-center > div:first-child');
                                if (badgeContainer && badgeHtmlMap[normalizedStatus]) {
                                    badgeContainer.innerHTML = badgeHtmlMap[normalizedStatus];
                                }

                                // Update Delete button visibility inside .room-admin-actions
                                const deleteBtn = card.querySelector('.room-admin-actions .btn-danger');
                                if (deleteBtn) {
                                    deleteBtn.style.display = (normalizedStatus === 'occupied') ? 'none' : '';
                                }
                            }

                            // Update dynamic times text if available
                            const timeTextContainer = card.querySelector('.room-card-body .d-flex.justify-content-between.align-items-center .room-times-text');
                            if (timeTextContainer) {
                                if (normalizedStatus === 'under_maintenance' && data.room_blocking_periods && data.room_blocking_periods[roomId]) {
                                    timeTextContainer.textContent = data.room_blocking_periods[roomId];
                                    timeTextContainer.className = 'room-times-text text-end room-blocking-period text-muted';
                                } else if (normalizedStatus === 'occupied' && data.room_times && data.room_times[roomId]) {
                                    const times = data.room_times[roomId];
                                    timeTextContainer.className = 'room-times-text text-end text-muted';
                                    timeTextContainer.innerHTML = `<div><span class="text-muted">IN:</span> ${times.in}</div><div><span class="text-muted">OUT:</span> ${times.out}</div>`;
                                } else if (normalizedStatus === 'reserved' && data.room_reservation_arrivals && data.room_reservation_arrivals[roomId]) {
                                    timeTextContainer.className = 'room-times-text text-end room-reservation-arrival text-muted';
                                    timeTextContainer.innerHTML = `<div><span class="text-muted">Arrival:</span></div><div>${data.room_reservation_arrivals[roomId]}</div>`;
                                } else if (normalizedStatus === 'available') {
                                    timeTextContainer.innerHTML = '';
                                }
                            }
                        });

                        if (hasChanges) {
                            applyCurrentFilter();
                        }
                    }
                })
                .catch(err => {
                    console.error('Room status sync error:', err);
                })
                .finally(() => {
                    isSyncingRooms = false;
                });
        }

        // Run room status sync every 5 seconds
        setInterval(syncAdminRooms, 5000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminRoomsSection);
    } else {
        initAdminRoomsSection();
    }
})();
</script>