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
            // Expose globally so the Book Now modal script can call it
            window.fetchRooms = fetchRooms;

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

            // 2. (Old Book Choice Modal transitions removed – replaced by #bookNowModal controller below)

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
    
    <!-- Book Now Modal: Reactive Controller -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        /* ─── DOM refs ─── */
        const form          = document.getElementById('bookNowForm');
        const modalEl       = document.getElementById('bookNowModal');
        if (!form || !modalEl) return;

        /* ─── Config from data attributes on the form (set in topbar.blade.php) ─── */
        const PRICES            = JSON.parse(form.dataset.prices);
        const DISCOUNTS         = JSON.parse(form.dataset.discounts);
        const ROUTE_BOOKING     = form.dataset.routeBooking;
        const ROUTE_RESERVATION = form.dataset.routeReservation;
        const ROUTE_AVAILABLE   = form.dataset.routeAvailable;

        const actionType    = document.getElementById('bn_action_type');
        const modeNow       = document.getElementById('bn_mode_now');
        const modeReserve   = document.getElementById('bn_mode_reserve');
        const stayShort     = document.getElementById('bn_stay_short');
        const stayOvernight = document.getElementById('bn_stay_overnight');
        const shortWrap     = document.getElementById('bn_short_hours_wrap');
        const overnightWrap = document.getElementById('bn_overnight_wrap');
        const checkIn       = document.getElementById('bn_check_in');
        const checkOut      = document.getElementById('bn_check_out');
        const roomSelect    = document.getElementById('bn_room_ids');
        const roomButtons   = document.getElementById('bn_room_buttons');
        const roomMode      = document.getElementById('bn_room_mode');
        const discountWrap  = document.getElementById('bn_discount_wrap');
        const depositWrap   = document.getElementById('bn_deposit_wrap');
        const depositInput  = document.getElementById('bn_deposit');
        const bedPriceBadge = document.getElementById('bn_bed_price_badge');
        const extraBedsInput= document.getElementById('bn_extra_beds');
        const submitBtn     = document.getElementById('bn_submit_btn');
        const checkInLabel  = document.getElementById('bn_checkin_label');
        const checkInHint   = document.getElementById('bn_checkin_hint');
        const checkOutWrap  = document.getElementById('bn_checkout_wrap');

        // Price preview elements
        const pvSubtotal  = document.getElementById('bn_preview_subtotal');
        const pvDiscount  = document.getElementById('bn_preview_discount');
        const pvDeposit   = document.getElementById('bn_preview_deposit');
        const pvBalance   = document.getElementById('bn_preview_balance');
        const pvTotal     = document.getElementById('bn_preview_total');
        const rowDiscount = document.getElementById('bn_row_discount');
        const rowDeposit  = document.getElementById('bn_row_deposit');
        const rowBalance  = document.getElementById('bn_row_balance');

        /* ─── State ─── */
        let selectedRoomIds = new Set();

        /* ─── Helpers ─── */
        function pad(n) { return String(n).padStart(2, '0'); }
        function dtLocal(d) {
            return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
        function peso(n) { return '₱' + Number(n).toFixed(2); }
        function getMode() { return modeReserve.checked ? 'reserve' : 'now'; }
        function getStayType() { return stayOvernight.checked ? 'overnight' : 'short_stay'; }
        function getShortHours() {
            var el = document.querySelector('input[name="short_hours"]:checked');
            return el ? parseInt(el.value, 10) : 3;
        }
        function getBreakfast() {
            var el = document.querySelector('input[name="with_breakfast"]:checked');
            return el ? parseInt(el.value, 10) : 0;
        }
        function getDiscountType() {
            var el = document.querySelector('input[name="discount_type"]:checked');
            return el ? el.value : '';
        }

        /* ─── Compute check-out from check-in + duration, then load the free rooms ─── */
        function computeCheckOut() {
            if (!checkIn.value) { checkOut.value = ''; loadRooms(); return; }
            var d = new Date(checkIn.value);
            if (isNaN(d.getTime())) return;
            var hours = getStayType() === 'short_stay' ? getShortHours() : 24;
            d.setHours(d.getHours() + hours);
            checkOut.value = dtLocal(d);
            if (getMode() === 'reserve') {
                checkInHint.textContent = 'Today or a future date. The stay ends ' +
                    d.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) + '.';
            }
            loadRooms();
        }

        /* ─── Ask the server which rooms are free for this stay ─── */
        var loadTimer = null;
        function loadRooms() {
            clearTimeout(loadTimer);
            loadTimer = setTimeout(function () {
                if (!checkIn.value || !checkOut.value) {
                    roomSelect.innerHTML = '';
                    roomButtons.innerHTML = '<span class="text-muted small fst-italic">Set the time to load available rooms…</span>';
                    return;
                }
                roomButtons.innerHTML = '<span class="text-muted small fst-italic">Loading rooms…</span>';
                fetch(ROUTE_AVAILABLE + '?start_date=' + encodeURIComponent(checkIn.value) + '&end_date=' + encodeURIComponent(checkOut.value))
                    .then(function (res) { if (!res.ok) throw new Error('bad response'); return res.json(); })
                    .then(function (rooms) {
                        roomSelect.innerHTML = '';
                        rooms.forEach(function (room) {
                            var o = document.createElement('option');
                            o.value = room.id;
                            o.textContent = 'Room ' + (room.room_number || room.id);
                            roomSelect.appendChild(o);
                        });
                        // Drop selections that are no longer free, then redraw the buttons
                        var free = new Set(rooms.map(function (r) { return String(r.id); }));
                        selectedRoomIds.forEach(function (id) { if (!free.has(id)) selectedRoomIds.delete(id); });
                        renderRoomButtons();
                        updatePrice();
                    })
                    .catch(function () {
                        roomButtons.innerHTML = '<span class="text-danger small">Could not load the rooms. Please try again.</span>';
                    });
            }, 80);
        }

        /* ─── Render room buttons from hidden select options ─── */
        function renderRoomButtons() {
            roomButtons.innerHTML = '';
            var options = roomSelect.options;
            if (options.length === 0) {
                roomButtons.innerHTML = '<span class="text-muted small fst-italic">No rooms available for the selected dates.</span>';
                return;
            }
            // Check for disabled-only options (loading / error messages)
            var hasReal = false;
            for (var i = 0; i < options.length; i++) {
                if (!options[i].disabled) { hasReal = true; break; }
            }
            if (!hasReal) {
                roomButtons.innerHTML = '<span class="text-muted small fst-italic">' + (options[0] ? options[0].textContent : 'Loading rooms…') + '</span>';
                return;
            }
            for (var i = 0; i < options.length; i++) {
                if (options[i].disabled) continue;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bn-room-btn' + (selectedRoomIds.has(options[i].value) ? ' active' : '');
                btn.setAttribute('data-room-id', options[i].value);
                // Format label as "Room 01"
                var numMatch = options[i].textContent.match(/\d+/);
                var num = numMatch ? String(numMatch[0]).padStart(2, '0') : options[i].value;
                btn.textContent = 'Room ' + num;
                roomButtons.appendChild(btn);
            }
            syncSelectFromSet();
        }

        /* ─── Sync hidden <select> selected state from our Set ─── */
        function syncSelectFromSet() {
            for (var i = 0; i < roomSelect.options.length; i++) {
                roomSelect.options[i].selected = selectedRoomIds.has(roomSelect.options[i].value);
            }
        }

        /* ─── Price preview ─── */
        function updatePrice() {
            var mode = getMode();
            var stayType = getStayType();
            var count = selectedRoomIds.size;
            var roomPrice = 0;
            var extraBedsCost = 0;

            if (stayType === 'short_stay') {
                var hours = getShortHours();
                roomPrice = PRICES.short_stay[hours] || 0;
            } else {
                var bk = getBreakfast();
                roomPrice = bk ? PRICES.overnight.with_breakfast : PRICES.overnight.no_breakfast;
                var beds = parseInt(extraBedsInput.value, 10) || 0;
                var bedRate = bk ? PRICES.extra_bed.with_breakfast : PRICES.extra_bed.no_breakfast;
                extraBedsCost = beds * bedRate;
            }

            var subtotal = (roomPrice * count) + extraBedsCost;
            var discountAmt = 0;
            var total = subtotal;

            if (mode === 'now') {
                var dt = getDiscountType();
                var pct = (dt && DISCOUNTS[dt]) ? DISCOUNTS[dt] : 0;
                discountAmt = Math.round(subtotal * pct * 100) / 100;
                total = Math.round((subtotal - discountAmt) * 100) / 100;
                // Show discount row, hide deposit/balance rows
                rowDiscount.style.display = '';
                rowDeposit.style.display = 'none';
                rowBalance.style.display = 'none';
                pvDiscount.textContent = '- ' + peso(discountAmt);
            } else {
                // Reserve mode: no discount, but show deposit/balance if deposit entered
                rowDiscount.style.display = 'none';
                var dep = parseFloat(depositInput.value) || 0;
                if (dep > 0) {
                    var balance = Math.max(0, Math.round((total - dep) * 100) / 100);
                    rowDeposit.style.display = '';
                    rowBalance.style.display = '';
                    pvDeposit.textContent = peso(dep);
                    pvBalance.textContent = peso(balance);
                } else {
                    rowDeposit.style.display = 'none';
                    rowBalance.style.display = 'none';
                }
            }

            pvSubtotal.textContent = peso(subtotal);
            pvTotal.textContent = peso(total);
        }

        // Mode toggle: Book Now vs Reserve
        function onModeChange() {
            var mode = getMode();
            var now = new Date();
            if (mode === 'now') {
                form.action = ROUTE_BOOKING;
                actionType.value = 'check_in';
                discountWrap.style.display = '';
                depositWrap.style.display = 'none';
                depositInput.value = '';
                checkInLabel.textContent = 'Check-in Date';
                checkInHint.textContent = 'Today only. You can change the time.';
                checkOutWrap.style.display = '';
                // Book Now is for today only: from now until the end of today
                checkIn.min = dtLocal(now);
                checkIn.max = dtLocal(now).slice(0, 10) + 'T23:59';
                if (!checkIn.value || checkIn.value < checkIn.min || checkIn.value > checkIn.max) checkIn.value = dtLocal(now);
                submitBtn.textContent = 'Confirm Booking';
            } else {
                form.action = ROUTE_RESERVATION;
                actionType.value = 'reserved';
                discountWrap.style.display = 'none';
                document.getElementById('bn_disc_none').checked = true;
                depositWrap.style.display = '';
                checkInLabel.textContent = 'Arrival Date & Time';
                checkOutWrap.style.display = 'none';
                // Reserve: today or a future date, never in the past
                checkIn.min = dtLocal(now);
                checkIn.removeAttribute('max');
                if (!checkIn.value || checkIn.value < checkIn.min) checkIn.value = dtLocal(now);
                submitBtn.textContent = 'Confirm Reservation';
            }
            computeCheckOut();
            updatePrice();
        }
        modeNow.addEventListener('change', onModeChange);
        modeReserve.addEventListener('change', onModeChange);

        /* ─── Stay type toggle ─── */
        function onStayTypeChange() {
            if (getStayType() === 'short_stay') {
                shortWrap.style.display = '';
                overnightWrap.style.display = 'none';
            } else {
                shortWrap.style.display = 'none';
                overnightWrap.style.display = '';
            }
            computeCheckOut();
            updatePrice();
        }
        stayShort.addEventListener('change', onStayTypeChange);
        stayOvernight.addEventListener('change', onStayTypeChange);

        /* ─── Short hours change ─── */
        document.querySelectorAll('input[name="short_hours"]').forEach(function (r) {
            r.addEventListener('change', function () {
                computeCheckOut();
                updatePrice();
            });
        });

        /* ─── Breakfast change ─── */
        document.querySelectorAll('input[name="with_breakfast"]').forEach(function (r) {
            r.addEventListener('change', function () {
                var bk = getBreakfast();
                var bedRate = bk ? PRICES.extra_bed.with_breakfast : PRICES.extra_bed.no_breakfast;
                bedPriceBadge.textContent = peso(bedRate) + ' / bed';
                updatePrice();
            });
        });

        /* ─── Extra beds change ─── */
        extraBedsInput.addEventListener('input', updatePrice);

        /* ─── Check-in change ─── */
        checkIn.addEventListener('change', function () {
            computeCheckOut();
        });

        /* ─── Discount radios ─── */
        document.querySelectorAll('input[name="discount_type"]').forEach(function (r) {
            r.addEventListener('change', updatePrice);
        });

        /* ─── Deposit input ─── */
        depositInput.addEventListener('input', updatePrice);

        /* ─── Room button clicks ─── */
        roomButtons.addEventListener('click', function (e) {
            var btn = e.target.closest('.bn-room-btn');
            if (!btn) return;
            var id = btn.getAttribute('data-room-id');
            if (roomMode.value === 'one') {
                selectedRoomIds.clear();
                selectedRoomIds.add(id);
            } else {
                if (selectedRoomIds.has(id)) {
                    selectedRoomIds.delete(id);
                } else {
                    selectedRoomIds.add(id);
                }
            }
            // Update button visuals
            roomButtons.querySelectorAll('.bn-room-btn').forEach(function (b) {
                b.classList.toggle('active', selectedRoomIds.has(b.getAttribute('data-room-id')));
            });
            syncSelectFromSet();
            updatePrice();
        });

        /* ─── Room mode dropdown ─── */
        roomMode.addEventListener('change', function () {
            if (roomMode.value === 'one' && selectedRoomIds.size > 1) {
                var first = selectedRoomIds.values().next().value;
                selectedRoomIds.clear();
                selectedRoomIds.add(first);
                roomButtons.querySelectorAll('.bn-room-btn').forEach(function (b) {
                    b.classList.toggle('active', selectedRoomIds.has(b.getAttribute('data-room-id')));
                });
                syncSelectFromSet();
                updatePrice();
            }
        });

        /* ─── Observe hidden <select> for option changes (fetchRooms populates it) ─── */
        var observer = new MutationObserver(function () {
            // Prune any selected IDs that no longer exist in the new options
            var availIds = new Set();
            for (var i = 0; i < roomSelect.options.length; i++) {
                if (!roomSelect.options[i].disabled) availIds.add(roomSelect.options[i].value);
            }
            selectedRoomIds.forEach(function (id) {
                if (!availIds.has(id)) selectedRoomIds.delete(id);
            });
            // Enforce "one room" constraint
            if (roomMode.value === 'one' && selectedRoomIds.size > 1) {
                var first = selectedRoomIds.values().next().value;
                selectedRoomIds.clear();
                selectedRoomIds.add(first);
            }
            renderRoomButtons();
            updatePrice();
        });
        observer.observe(roomSelect, { childList: true, subtree: true });

        /* ─── Modal shown: initialise check-in to now ─── */
        modalEl.addEventListener('shown.bs.modal', function () {
            if (!checkIn.value) {
                checkIn.value = dtLocal(new Date());
            }
            onStayTypeChange();
            onModeChange();
        });

        /* ─── Modal hidden: reset form ─── */
        modalEl.addEventListener('hidden.bs.modal', function () {
            form.reset();
            selectedRoomIds.clear();
            roomButtons.innerHTML = '<span class="text-muted small fst-italic">Set check-in time to load available rooms…</span>';
            roomSelect.innerHTML = '';
            checkIn.value = '';
            checkOut.value = '';
            depositInput.value = '';
            // Reset visibility to defaults
            shortWrap.style.display = '';
            overnightWrap.style.display = 'none';
            discountWrap.style.display = '';
            depositWrap.style.display = 'none';
            rowDiscount.style.display = '';
            rowDeposit.style.display = 'none';
            rowBalance.style.display = 'none';
            pvSubtotal.textContent = '₱0.00';
            pvDiscount.textContent = '- ₱0.00';
            pvTotal.textContent = '₱0.00';
            submitBtn.textContent = 'Confirm Booking';
            bedPriceBadge.textContent = '₱500 / bed';
        });

        /* ─── Submit validation ─── */
        form.addEventListener('submit', function (e) {
            if (selectedRoomIds.size === 0) {
                e.preventDefault();
                alert('Please select at least one available room.');
            }
        });
    });
    </script>
    <!-- Live Clock -->
    <script>
        // Live clock in the top bar (Manila time)
        document.addEventListener('DOMContentLoaded', function () {
            var dateEl = document.getElementById('current-date');
            var timeEl = document.getElementById('current-time');
            if (!dateEl || !timeEl) return;

            function tick() {
                var now = new Date();
                dateEl.textContent = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric', timeZone: 'Asia/Manila' });
                timeEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila' });
            }
            tick();
            setInterval(tick, 1000);
        });
    </script>
    
    <!-- Block Room Form Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('blockRoomModal');
            var form = document.getElementById('blockRoomForm');
            var start = document.getElementById('bl_start');
            var end = document.getElementById('bl_end');
            var reason = document.getElementById('bl_reason');
            var buttons = document.getElementById('bl_room_buttons');
            var inputs = document.getElementById('bl_room_inputs');
            var hint = document.getElementById('bl_room_hint');
            var message = document.getElementById('bl_message');
            var submitBtn = document.getElementById('bl_submit');
            if (!modalEl || !form || !start || !end || !buttons || !inputs || !submitBtn) return;

            var url = form.dataset.url;
            var selected = new Set();   // ids of the rooms the receptionist clicked
            var freeRooms = [];         // rooms free for the chosen dates
            var defaultHint = 'Choose the dates first.';

            function pad(n) { return String(n).padStart(2, '0'); }

            // Shows or hides the red message above the Current blocks list
            function showMessage(text) {
                message.textContent = text;
                message.classList.toggle('d-none', !text);
            }

            // Tells the receptionist what is selected
            function updateHint() {
                if (!freeRooms.length) { return; }
                hint.textContent = selected.size
                    ? selected.size + ' selected: ' + Array.from(selected).sort(function (a, b) { return a - b; }).map(pad).join(', ')
                    : freeRooms.length + ' room(s) free. Click to select.';
            }

            // Keeps the hidden form fields in step with the clicked rooms
            function syncInputs() {
                inputs.innerHTML = '';
                selected.forEach(function (id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'room_ids[]';
                    input.value = id;
                    inputs.appendChild(input);
                });
            }

            // One button per free room: click to select, click again to deselect
            function render() {
                buttons.innerHTML = '';
                freeRooms.forEach(function (room) {
                    var id = String(room.id);
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'bn-room-btn' + (selected.has(id) ? ' active' : '');
                    btn.textContent = 'Room ' + pad(room.room_number || room.id);
                    btn.addEventListener('click', function () {
                        if (selected.has(id)) { selected.delete(id); } else { selected.add(id); }
                        syncInputs();
                        showMessage('');
                        updateHint();
                        render();
                    });
                    buttons.appendChild(btn);
                });
            }

            // Asks the server which rooms are free from the start to the end
            function loadRooms() {
                selected.clear();
                syncInputs();
                freeRooms = [];
                buttons.innerHTML = '';
                showMessage('');
                if (!start.value || !end.value || end.value <= start.value) {
                    hint.textContent = start.value && end.value ? 'The end must be later than the start.' : defaultHint;
                    return;
                }
                hint.textContent = 'Loading rooms...';
                fetch(url + '?start_date=' + encodeURIComponent(start.value) + '&end_date=' + encodeURIComponent(end.value))
                    .then(function (res) { if (!res.ok) throw new Error('bad response'); return res.json(); })
                    .then(function (rooms) {
                        freeRooms = rooms;
                        render();
                        if (rooms.length) { updateHint(); } else { hint.textContent = 'No rooms are free for these dates.'; }
                    })
                    .catch(function () { hint.textContent = 'Could not load the rooms. Please try again.'; });
            }
            start.addEventListener('change', loadRooms);
            end.addEventListener('change', loadRooms);

            // Block room: check everything first and say what is missing, then send the form
            submitBtn.addEventListener('click', function () {
                if (!start.value || !end.value) { showMessage('Choose the From and Until date and time.'); return; }
                if (end.value <= start.value) { showMessage('The end must be later than the start.'); return; }
                if (selected.size === 0) { showMessage('Click at least one room to block.'); return; }
                if (!reason.value.trim()) { showMessage('Type the reason (for example: Aircon repair).'); return; }
                showMessage('');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                form.submit();
            });

            // Remove: sends its own small form, so it never mixes with the Block room fields
            form.querySelectorAll('.btn-remove-block').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!window.confirm('Remove this block? The room will be available again.')) { return; }
                    var remove = document.createElement('form');
                    remove.method = 'POST';
                    remove.action = btn.dataset.action;
                    var token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = form.querySelector('input[name="_token"]').value;
                    remove.appendChild(token);
                    document.body.appendChild(remove);
                    remove.submit();
                });
            });

            // Pressing Enter inside the window must not send anything by accident
            form.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') { e.preventDefault(); }
            });

            // Start fresh every time the window is closed
            modalEl.addEventListener('hidden.bs.modal', function () {
                form.reset();
                selected.clear();
                syncInputs();
                freeRooms = [];
                buttons.innerHTML = '';
                hint.textContent = defaultHint;
                showMessage('');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Block room';
            });
        });
    </script>

    @stack('scripts')
<!--End of Scripts  -->