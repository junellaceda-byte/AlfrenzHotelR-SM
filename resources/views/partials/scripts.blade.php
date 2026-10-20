<!-- Scripts -->
    <script src="{{ asset('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') }}"></script>
    <!-- For the Auto Fetching of Rooms -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        function fetchRooms(startInputId, endInputId, selectId) {
            const startInput = document.getElementById(startInputId);
            const endInput = document.getElementById(endInputId);
            const selectElement = document.getElementById(selectId);

            function updateRooms() {
                const start = startInput.value;
                const end = endInput.value;

                if (start && end) {
                    if (new Date(end) <= new Date(start)) {
                        selectElement.innerHTML = '<option value="" disabled>Check-out must be after check-in</option>';
                        return;
                    }

                    selectElement.innerHTML = '<option value="" disabled>Loading rooms...</option>';
                    
                    fetch(`/api/available-rooms?start_date=${start}&end_date=${end}`)
                        .then(response => response.json())
                        .then(data => {
                            selectElement.innerHTML = '';
                            if (data.length === 0) {
                                selectElement.innerHTML = '<option value="" disabled>No rooms available</option>';
                            } else {
                                data.forEach(room => {
                                    const option = document.createElement('option');
                                    option.value = room.id;
                                    option.textContent = `Room ${room.room_number} (${room.room_type || 'Standard'})`;
                                    selectElement.appendChild(option);
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching rooms:', error);
                            selectElement.innerHTML = '<option value="" disabled>Error loading rooms</option>';
                        });
                } else {
                    selectElement.innerHTML = '<option value="" disabled>Select dates first...</option>';
                }
            }

            startInput.addEventListener('change', updateRooms);
            endInput.addEventListener('change', updateRooms);
        }
            fetchRooms('res_checkin', 'res_checkout', 'res_room_ids');
            fetchRooms('check_in_date', 'check_out_date', 'checkin_room_ids');
        });
    </script>
    <!-- Scripts for bookings -->
    <script>
        // Pre-fill the Edit Booking modal with the clicked row's data
        document.querySelectorAll('[data-bs-target="#edit-booking-modal"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                document.getElementById('edit-booking-form').action = '/bookings/' + id;
                document.getElementById('edit_booking_fname').value = this.dataset.fname;
                document.getElementById('edit_booking_lname').value = this.dataset.lname;
                document.getElementById('edit_booking_phone').value = this.dataset.phone;
            });
        });

        function confirmBookingDelete(formId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                document.getElementById(formId).submit();
            }
        }
    </script>


    <!-- Load Chart.js locally from public/js folder -->
    <script src="{{ asset('js/chart.min.js') }}"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('occupancyChart');
        if (!ctx) return;

        const chartLoader = document.getElementById('chartLoader');
        
        // Use Dynamic Data from Laravel if available, else fallback to Dummy Data
        const chartData = window.dynamicChartData ? window.dynamicChartData : {
            today: { labels: ['Morning', 'Afternoon', 'Evening', 'Night'], data: [5, 12, 18, 15] },
            weekly: { labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], data: [45, 50, 48, 60, 85, 90, 75] },
            monthly: { labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], data: [200, 250, 220, 310] }
        };

        // 1. Initialize the chart with ZEROES so it starts completely flat at the bottom
        let occupancyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.today.labels, 
                datasets: [{
                    label: 'Rooms Occupied',
                    data: [0, 0, 0, 0], // <--- Forcing it to start flat
                    backgroundColor: '#4A7856',
                    borderRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        suggestedMax: 20 // Gives the initial animation a fixed roof so the grid doesn't jump
                    } 
                },
                animation: {
                    duration: 1200, // 1.2 seconds for the upward growth
                    easing: 'easeOutQuart'
                }
            }
        });

        // 2. A split second after loading, inject the real 'Today' data. 
        // This absolutely forces the bars to animate upwards.
        setTimeout(() => {
            occupancyChart.data.datasets[0].data = chartData.today.data;
            occupancyChart.update();
        }, 100);

        // Filter Click Logic
        document.querySelectorAll('.chart-filter').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');
                
                chartLoader.style.display = 'block';
                ctx.style.opacity = '0.3';

                setTimeout(() => {
                    const newData = chartData[filter];
                    if(newData) {
                        occupancyChart.data.labels = newData.labels;
                        occupancyChart.data.datasets[0].data = newData.data;
                        
                        // Adjust the roof dynamically based on the new data
                        const maxVal = Math.max(...newData.data);
                        occupancyChart.options.scales.y.suggestedMax = maxVal + (maxVal * 0.2); 

                        occupancyChart.update();
                    }

                    chartLoader.style.display = 'none';
                    ctx.style.opacity = '1';
                }, 800); 
            });
        });
    });
    </script>

    <!-- Sidebar Underline Animation -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Select all sidebar links
        const sidebarLinks = document.querySelectorAll('.sidebar-link');

        sidebarLinks.forEach(link => {
            const textSpan = link.querySelector('span');
            if (!textSpan) return;

            // Set the text span to position relative so the underline stays directly under the word
            textSpan.style.position = 'relative';
            textSpan.style.display = 'inline-block';
            textSpan.style.paddingBottom = '4px'; // Space between text and underline

            // Dynamically create the underline element using JavaScript
            const underline = document.createElement('div');
            underline.style.position = 'absolute';
            underline.style.bottom = '0';
            underline.style.left = '0'; // Start exactly at the left edge of the word
            underline.style.height = '2px';
            underline.style.backgroundColor = '#C5A059'; // Golden Light Brown
            underline.style.width = '0%'; // Start invisible (0 width)
            underline.style.transition = 'width 0.3s ease-out'; // The slide animation speed

            // Add the underline to the text span
            textSpan.appendChild(underline);

            // If this link is the currently active page, keep the underline fully extended
            if (link.classList.contains('active')) {
                underline.style.width = '100%';
            }

            // JS Event: Slide in from left on hover
            link.addEventListener('mouseenter', () => {
                underline.style.width = '100%';
            });

            // JS Event: Slide back out on mouse leave (unless it's the active page)
            link.addEventListener('mouseleave', () => {
                if (!link.classList.contains('active')) {
                    underline.style.width = '0%';
                }
            });
        });
    });
    </script>

    <!-- Chart Filter Button  -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select all chart filter links and the new chart filter button
            const chartFilters = document.querySelectorAll('.chart-filter');
            const chartFilterBtn = document.getElementById('chartFilterBtn');

            chartFilters.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent page jump

                    // Get the text of the selected filter (e.g., "Today" or "Weekly")
                    const filterText = this.textContent;

                    // Update the button label
                    chartFilterBtn.textContent = filterText;

                    // Note for later: Can also grab the data-filter value here 
                    // to trigger the local Chart.js updates
                    // const filterValue = this.getAttribute('data-filter');
                    // updateChartFunction(filterValue); 
                });
            });
        });
    </script>

    <!-- Room Status Filter Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select the dropdown options, the button, and all room cards
            const roomFilters = document.querySelectorAll('.room-filter');
            const filterDropdownBtn = document.getElementById('filterDropdownBtn');
            const roomCards = document.querySelectorAll('.room-card'); 

            roomFilters.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent page from jumping to top

                    // 1. Update the button label to match what was clicked
                    const filterText = this.textContent;
                    filterDropdownBtn.textContent = filterText;

                    // 2. Filter the room cards (if you have the data-status attributes set up)
                    const selectedStatus = this.getAttribute('data-filter');
                    
                    roomCards.forEach(card => {
                        const cardStatus = card.getAttribute('data-status');
                        
                        // Show if 'All Rooms' is selected, or if the status matches
                        if (selectedStatus === 'all' || cardStatus === selectedStatus) {
                            card.style.display = 'block'; 
                        } else {
                            card.style.display = 'none'; 
                        }
                    });
                });
            });
        });
    </script>
    <!-- Room Mode Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            [['res_room_mode', 'res_room_ids'], ['checkin_room_mode', 'checkin_room_ids']].forEach(function (pair) {
                var mode = document.getElementById(pair[0]);
                var rooms = document.getElementById(pair[1]);
                if (!mode || !rooms) return;
                function apply() {
                    rooms.multiple = (mode.value === 'multiple');
                    Array.from(rooms.options).forEach(function (o) { o.selected = false; });
                }
                mode.addEventListener('change', apply);
                apply();
            });
        });
    </script>
    <!-- Check-in/Check-out Time Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var inTime = "{{ config('hotel.check_in_time') }}";
            var outTime = "{{ config('hotel.check_out_time') }}";
            var date = document.getElementById('res_date');
            var nights = document.getElementById('res_nights');
            var inInput = document.getElementById('res_checkin');
            var outInput = document.getElementById('res_checkout');
            var summary = document.getElementById('res_summary');
            if (!date || !nights || !inInput || !outInput) return;

            function pad(n) { return String(n).padStart(2, '0'); }
            function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

            function update() {
                var n = Math.max(1, Math.min(30, parseInt(nights.value, 10) || 1));
                if (!date.value) { inInput.value = ''; outInput.value = ''; summary.textContent = ''; return; }
                var end = new Date(date.value + 'T00:00:00');
                end.setDate(end.getDate() + n);
                inInput.value = date.value + 'T' + inTime;
                outInput.value = ymd(end) + 'T' + outTime;
                summary.textContent = 'Check-in ' + date.value + ' ' + inTime + '  →  Check-out ' + ymd(end) + ' ' + outTime;
                outInput.dispatchEvent(new Event('change'));
            }
            date.addEventListener('change', update);
            nights.addEventListener('input', update);
            update();
        });
    </script>
    @stack('scripts')
<!--End of Scripts  -->