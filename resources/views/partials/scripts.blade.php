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

    <!-- Dashboard Interactive Features: Room Filters, Book Choices, and Amenities Preselection -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Room Status Filter Buttons (ALL, AVAILABLE, OCCUPIED, UNDER MAINTENANCE)
            const filterButtons = document.querySelectorAll('.filter-btn-group .btn-filter');
            const roomCards = document.querySelectorAll('#hotelRoomGrid .hotel-room-card');

            filterButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    filterButtons.forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');

                    const selectedFilter = this.getAttribute('data-filter');

                    roomCards.forEach(function(card) {
                        const cardStatus = card.getAttribute('data-status');
                        if (selectedFilter === 'all') {
                            card.style.display = 'flex';
                        } else if (cardStatus === selectedFilter) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // 2. Book Choice Modal Transitions
            const btnBookNow = document.getElementById('btnChoiceBookNow');
            const btnReserve = document.getElementById('btnChoiceReserve');
            const bookChoiceModalEl = document.getElementById('bookChoiceModal');
            const availModalEl = document.getElementById('avail-modal');
            const resModalEl = document.getElementById('res-modal');

            if (btnBookNow && bookChoiceModalEl && availModalEl) {
                btnBookNow.addEventListener('click', function() {
                    const choiceModal = bootstrap.Modal.getInstance(bookChoiceModalEl) || new bootstrap.Modal(bookChoiceModalEl);
                    choiceModal.hide();

                    bookChoiceModalEl.addEventListener('hidden.bs.modal', function handler() {
                        bookChoiceModalEl.removeEventListener('hidden.bs.modal', handler);
                        const availModal = bootstrap.Modal.getInstance(availModalEl) || new bootstrap.Modal(availModalEl);
                        availModal.show();
                    });
                });
            }

            if (btnReserve && bookChoiceModalEl && resModalEl) {
                btnReserve.addEventListener('click', function() {
                    const choiceModal = bootstrap.Modal.getInstance(bookChoiceModalEl) || new bootstrap.Modal(bookChoiceModalEl);
                    choiceModal.hide();

                    bookChoiceModalEl.addEventListener('hidden.bs.modal', function handler() {
                        bookChoiceModalEl.removeEventListener('hidden.bs.modal', handler);
                        const resModal = bootstrap.Modal.getInstance(resModalEl) || new bootstrap.Modal(resModalEl);
                        resModal.show();
                    });
                });
            }

            // 3. Add Amenities Preselection & Dynamic Details
            const amenityButtons = document.querySelectorAll('.btn-trigger-amenity');
            const amenityRoomSelect = document.getElementById('amenityRoomSelect');
            const placeholderRoomNumber = document.getElementById('placeholderRoomNumber');
            const placeholderGuestName = document.getElementById('placeholderGuestName');
            const amenitiesModalEl = document.getElementById('amenitiesModal');

            function updateGuestDetails(roomNumber) {
                if (placeholderRoomNumber) {
                    placeholderRoomNumber.textContent = roomNumber ? `Room ${roomNumber}` : 'Room --';
                }
                if (placeholderGuestName) {
                    placeholderGuestName.textContent = roomNumber ? `Guest in Room ${roomNumber}` : 'Guest Name';
                }
            }

            if (amenityRoomSelect) {
                amenityRoomSelect.addEventListener('change', function() {
                    const selectedOpt = this.options[this.selectedIndex];
                    const rNum = selectedOpt ? selectedOpt.getAttribute('data-room-number') : '';
                    updateGuestDetails(rNum);
                });
            }

            amenityButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const roomId = this.getAttribute('data-room-id');
                    const roomNumber = this.getAttribute('data-room-number');

                    if (amenityRoomSelect) {
                        amenityRoomSelect.value = roomId;
                        updateGuestDetails(roomNumber);
                    }

                    if (amenitiesModalEl) {
                        const modal = bootstrap.Modal.getInstance(amenitiesModalEl) || new bootstrap.Modal(amenitiesModalEl);
                        modal.show();
                    }
                });
            });

            const btnSubmitAmenities = document.getElementById('btnSubmitAmenities');
            if (btnSubmitAmenities) {
                btnSubmitAmenities.addEventListener('click', function() {
                    const amenityItemSelect = document.getElementById('amenityItemSelect');
                    if (!amenityRoomSelect || !amenityRoomSelect.value) {
                        alert('Please select an occupied room.');
                        return;
                    }
                    if (!amenityItemSelect || !amenityItemSelect.value) {
                        alert('Please select an amenity item.');
                        return;
                    }
                    const selectedOpt = amenityRoomSelect.options[amenityRoomSelect.selectedIndex];
                    const rNum = selectedOpt ? selectedOpt.getAttribute('data-room-number') : '';
                    alert('Amenities requested successfully for Room ' + rNum + ' (UI demo).');
                    if (amenitiesModalEl) {
                        const modal = bootstrap.Modal.getInstance(amenitiesModalEl);
                        if (modal) modal.hide();
                    }
                });
            }
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