<!-- Scripts -->
    <script src="{{ asset('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('bootstrap-5.3.8-dist/css/bootstrap.bundle.min.css') }}"></script>
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

    @stack('scripts')
    <!--End of Scripts  -->