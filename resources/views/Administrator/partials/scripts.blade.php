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

            // The Dummy Data
            const chartDataSets = {
                today: {
                    labels: ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM'],
                    data: [1500, 3200, 2100, 4800, 2400, 1400, 0]
                },
                weekly: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                    data: [25000, 32000, 28000, 45000, 18000]
                },
                monthly: {
                    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    data: [120000, 145000, 110000, 185000, 210000, 195000, 240000, 270000, 220000, 280000, 310000, 360000]
                },
                yearly: {
                    labels: ['2021', '2022', '2023', '2024', '2025', '2026'],
                    data: [1450000, 1680000, 1920000, 2150000, 2480000, 2650000]
                }
            };

            // Initialize chart with 'today' data as the default
            let revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartDataSets.today.labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: chartDataSets.today.data,
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
                            beginAtZero: true
                        }
                    }
                }
            });

            // Listen for dropdown changes
            document.getElementById('revenueFilter').addEventListener('change', function (e) {
                const selectedFilter = e.target.value;
                const newData = chartDataSets[selectedFilter];

                revenueChart.data.labels = newData.labels;
                revenueChart.data.datasets[0].data = newData.data;
                revenueChart.update();
            });
        }

        // ==========================================
        // 2. Booking Sources Donut Chart (Overview Section)
        // ==========================================
        const donutCanvas = document.getElementById('bookingSourceChart');

        if (donutCanvas) {
            new Chart(donutCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Online Reservation', 'Walk-in'],
                    datasets: [{
                        data: [65, 35], // Dummy data percentages
                        backgroundColor: [
                            '#30AFFF', // Bright blue for Online
                            '#FFCED8'  // Light pink for Walk-in
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%', // Makes the donut ring thinner and more modern
                    plugins: {
                        legend: {
                            position: 'bottom', // Moves the labels to the bottom
                            labels: {
                                padding: 20,
                                font: { weight: 'bold' },
                                color: '#443627' // Your primary brown for the text
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // 3. Occupancy Trend Line Chart (Sales and Revenue)
        // ==========================================
        const trendCanvas = document.getElementById('occupancyTrendChart');
        if (trendCanvas) {
            const trendDataSets = {
                weekly: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                    data: [68, 74, 80, 88, 65]
                },
                monthly: {
                    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    data: [55, 62, 58, 72, 78, 75, 82, 85, 70, 80, 86, 95]
                },
                yearly: {
                    labels: ['2021', '2022', '2023', '2024', '2025', '2026'],
                    data: [62, 68, 71, 76, 81, 84]
                }
            };

            let occupancyTrendChart = new Chart(trendCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendDataSets.weekly.labels,
                    datasets: [
                        {
                            label: 'Occupancy Rate (%)',
                            data: trendDataSets.weekly.data,
                            borderColor: '#30AFFF',
                            backgroundColor: 'rgba(48, 175, 255, 0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true,
                        }
                    ]
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
            if (trendFilterBtns.length > 0) {
                trendFilterBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const selectedFilter = this.getAttribute('data-period');
                        const newData = trendDataSets[selectedFilter];

                        if (newData) {
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

                            occupancyTrendChart.data.labels = newData.labels;
                            occupancyTrendChart.data.datasets[0].data = newData.data;
                            occupancyTrendChart.update();
                        }
                    });
                });
            }
        }

        // ==========================================
        // 4. Revenue Target Gauge Chart (Sales and Revenue)
        // ==========================================
        const gaugeElement = document.querySelector("#revenueGaugeChart");
        if (gaugeElement) {
            const gaugeOptions = {
                series: [83], // ₱124,500 out of ₱150,000 target = 83%
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

            const gaugeChart = new ApexCharts(gaugeElement, gaugeOptions);
            gaugeChart.render();
        }

        // ==========================================
        // 5. Revenue Breakdown Stacked Bar Chart (Sales and Revenue)
        // ==========================================
        const breakdownCanvas = document.getElementById('revenueBreakdownChart');
        if (breakdownCanvas) {
            const breakdownDataSets = {
                weekly: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                    roomRate: [24000, 29000, 26500, 31000, 18000],
                    extraCharges: [3000, 3500, 3200, 4300, 2000]
                },
                monthly: {
                    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    roomRate: [95000, 112000, 88000, 145000, 168000, 155000, 190000, 215000, 175000, 220000, 245000, 285000],
                    extraCharges: [14000, 16500, 12500, 21000, 24500, 22000, 28000, 31500, 25000, 32000, 36000, 42000]
                }
            };

            let revenueBreakdownChart = new Chart(breakdownCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: breakdownDataSets.weekly.labels,
                    datasets: [
                        {
                            label: 'Base Room Rate',
                            data: breakdownDataSets.weekly.roomRate,
                            backgroundColor: '#30AFFF', // Established bright blue
                            borderRadius: 4
                        },
                        {
                            label: 'Extra Charges & Add-ons',
                            data: breakdownDataSets.weekly.extraCharges,
                            backgroundColor: '#FFCED8', // Established flat pink
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true, // Enables stacking on the X axis
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true, // Enables stacking on the Y axis
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '₱' + value.toLocaleString();
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
                                color: '#443627' // Applies your established brown to the legend text
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += '₱' + context.parsed.y.toLocaleString();
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            const breakdownFilterBtns = document.querySelectorAll('.revenue-breakdown-btn');
            if (breakdownFilterBtns.length > 0) {
                breakdownFilterBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const selectedFilter = this.getAttribute('data-period');
                        const newData = breakdownDataSets[selectedFilter];

                        if (newData) {
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

                            revenueBreakdownChart.data.labels = newData.labels;
                            revenueBreakdownChart.data.datasets[0].data = newData.roomRate;
                            revenueBreakdownChart.data.datasets[1].data = newData.extraCharges;
                            revenueBreakdownChart.update();
                        }
                    });
                });
            }
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
        // 7. Amenities Comparison Ranked Horizontal Chart (Sales and Revenue)
        // ==========================================
        const amenitiesListEl = document.getElementById('amenitiesChartList');
        const amenitiesSubtitleEl = document.getElementById('amenitiesDateSubtitle');
        const amenitiesFilterBtns = document.querySelectorAll('.amenities-filter-btn');

        if (amenitiesListEl) {
            const amenitiesDataSets = {
                weekly: {
                    subtitle: 'Nov 11 – Nov 17, 2026',
                    items: [
                        { name: 'Extra Bed', count: 35, revenue: 17500 },
                        { name: 'Extra Pillow & Blanket', count: 25, revenue: 5000 },
                        { name: 'Breakfast Set', count: 20, revenue: 6000 },
                        { name: 'Toiletries Kit', count: 12, revenue: 1800 },
                        { name: 'Extra Towel Set', count: 8, revenue: 1600 }
                    ]
                },
                monthly: {
                    subtitle: 'November 2026 (Nov 1 – Nov 30)',
                    items: [
                        { name: 'Extra Bed', count: 140, revenue: 70000 },
                        { name: 'Breakfast Set', count: 96, revenue: 28800 },
                        { name: 'Extra Pillow & Blanket', count: 84, revenue: 16800 },
                        { name: 'Toiletries Kit', count: 48, revenue: 7200 },
                        { name: 'Extra Towel Set', count: 32, revenue: 6400 }
                    ]
                }
            };

            function renderAmenitiesChart(period) {
                const dataset = amenitiesDataSets[period];
                if (!dataset) return;

                if (amenitiesSubtitleEl) {
                    amenitiesSubtitleEl.textContent = dataset.subtitle;
                }

                // Sort descending by purchase count to ensure accurate ranking
                const sortedItems = [...dataset.items].sort((a, b) => b.count - a.count);
                const totalPurchases = sortedItems.reduce((sum, item) => sum + item.count, 0);
                const maxCount = sortedItems.length > 0 ? sortedItems[0].count : 1;

                amenitiesListEl.innerHTML = sortedItems.map((item, index) => {
                    const rank = index + 1;
                    const barWidth = maxCount > 0 ? ((item.count / maxCount) * 100).toFixed(1) : 0;
                    const sharePercent = totalPurchases > 0 ? ((item.count / totalPurchases) * 100).toFixed(1) : '0.0';

                    return `
                <div class="p-2 p-sm-3 rounded" style="background-color: rgba(115, 79, 70, 0.05); border: 1px solid rgba(115, 79, 70, 0.15);">
                    <!-- Top Row: Rank Badge + Amenity Name + Purchase Count -->
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

                    <!-- Middle Row: Proportional Horizontal Bar -->
                    <div class="w-100 rounded-pill overflow-hidden mb-2" style="height: 10px; background-color: rgba(115, 79, 70, 0.15);">
                        <div class="h-100 rounded-pill" style="width: ${barWidth}%; background-color: #734F46; transition: width 0.35s ease;"></div>
                    </div>

                    <!-- Bottom Row: Secondary Info (Revenue Generated & Percentage Share) -->
                    <div class="d-flex justify-content-between align-items-center small text-muted">
                        <span>Revenue Generated: <strong class="text-dark">₱${item.revenue.toLocaleString()}</strong></span>
                        <span>Share: <strong style="color: #734F46;">${sharePercent}%</strong></span>
                    </div>
                </div>
            `;
                }).join('');
            }

            // Filter Button Toggle Logic
            amenitiesFilterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const selectedPeriod = this.getAttribute('data-period');

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

                    renderAmenitiesChart(selectedPeriod);
                });
            });

            // Initial render
            renderAmenitiesChart('weekly');
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