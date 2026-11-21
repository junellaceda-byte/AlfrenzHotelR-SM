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

            // 3. Add Amenities Preselection, Dynamic Multi-Selection & List Details
            const amenityButtons = document.querySelectorAll('.btn-trigger-amenity');
            const amenityRoomSelect = document.getElementById('amenityRoomSelect');
            const placeholderRoomNumber = document.getElementById('placeholderRoomNumber');
            const placeholderGuestName = document.getElementById('placeholderGuestName');
            const amenitiesModalEl = document.getElementById('amenitiesModal');
            const formRoomId = document.getElementById('amenitiesFormRoomId');
            const formBookingId = document.getElementById('amenitiesFormBookingId');
            const amenitiesFormEl = document.getElementById('amenitiesForm');
            const amenityItemSelect = document.getElementById('amenityItemSelect');
            const amenityQuantity = document.getElementById('amenityQuantity');
            const amenitySpecialReq = document.getElementById('amenitySpecialReq');
            const btnAddAmenityToList = document.getElementById('btnAddAmenityToList');
            const selectedAmenitiesList = document.getElementById('selectedAmenitiesList');
            const emptyAmenitiesNotice = document.getElementById('emptyAmenitiesNotice');
            const selectedAmenitiesBadge = document.getElementById('selectedAmenitiesBadge');
            const selectedAmenitiesTotal = document.getElementById('selectedAmenitiesTotal');
            const amenitiesJsonInput = document.getElementById('amenitiesJsonInput');
            const amenitiesHiddenInputsContainer = document.getElementById('amenitiesHiddenInputsContainer');

            let selectedModalAmenities = [];

            function escapeHtmlLocal(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatMoneyLocal(amount) {
                return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function renderSelectedAmenitiesList() {
                if (!selectedAmenitiesList) return;

                if (selectedModalAmenities.length === 0) {
                    if (emptyAmenitiesNotice) emptyAmenitiesNotice.style.display = 'block';
                    if (selectedAmenitiesBadge) selectedAmenitiesBadge.textContent = '0 items';
                    if (selectedAmenitiesTotal) selectedAmenitiesTotal.textContent = '₱0.00';
                    selectedAmenitiesList.innerHTML = '';
                    if (amenitiesJsonInput) amenitiesJsonInput.value = '';
                    if (amenitiesHiddenInputsContainer) amenitiesHiddenInputsContainer.innerHTML = '';
                    return;
                }

                if (emptyAmenitiesNotice) emptyAmenitiesNotice.style.display = 'none';
                if (selectedAmenitiesBadge) {
                    selectedAmenitiesBadge.textContent = selectedModalAmenities.length + (selectedModalAmenities.length === 1 ? ' item' : ' items');
                }

                let total = 0;
                let html = '';
                let hiddenHtml = '';

                selectedModalAmenities.forEach((item, index) => {
                    const subtotal = item.quantity * item.price;
                    total += subtotal;

                    hiddenHtml += '<input type="hidden" name="amenities[' + index + '][amenity_id]" value="' + escapeHtmlLocal(item.amenityId) + '">';
                    hiddenHtml += '<input type="hidden" name="amenities[' + index + '][quantity]" value="' + item.quantity + '">';
                    hiddenHtml += '<input type="hidden" name="amenities[' + index + '][notes]" value="' + escapeHtmlLocal(item.notes || '') + '">';

                    html += '<div class="list-group-item bg-white rounded border p-2 mb-1 d-flex align-items-center justify-content-between" data-index="' + index + '">' +
                        '<div class="me-2 text-truncate" style="max-width: 175px;">' +
                            '<div class="fw-bold text-dark small text-truncate" title="' + escapeHtmlLocal(item.name) + '">' + escapeHtmlLocal(item.name) + '</div>' +
                            '<div class="text-muted" style="font-size: 0.72rem;">' +
                                '₱' + formatMoneyLocal(item.price) + ' / ' + escapeHtmlLocal(item.charged) +
                                (item.notes ? ' <span class="badge bg-light text-secondary border ms-1">' + escapeHtmlLocal(item.notes) + '</span>' : '') +
                            '</div>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-1">' +
                            '<div class="input-group input-group-sm" style="width: 75px;">' +
                                '<button class="btn btn-outline-secondary btn-item-minus px-1 py-0" type="button" data-index="' + index + '" title="Decrease quantity">-</button>' +
                                '<input type="number" class="form-control form-control-sm text-center px-0 py-0 input-item-quantity" min="1" max="20" value="' + item.quantity + '" data-index="' + index + '">' +
                                '<button class="btn btn-outline-secondary btn-item-plus px-1 py-0" type="button" data-index="' + index + '" title="Increase quantity">+</button>' +
                            '</div>' +
                            '<div class="text-end fw-bold small text-dark ms-1" style="min-width: 65px;">' +
                                '₱' + formatMoneyLocal(subtotal) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 btn-item-remove" data-index="' + index + '" title="Remove item">' +
                                '<span style="font-size: 1.1rem; line-height: 1; font-weight: bold;">&times;</span>' +
                            '</button>' +
                        '</div>' +
                    '</div>';
                });

                selectedAmenitiesList.innerHTML = html;
                if (selectedAmenitiesTotal) selectedAmenitiesTotal.textContent = '₱' + formatMoneyLocal(total);
                if (amenitiesJsonInput) {
                    amenitiesJsonInput.value = JSON.stringify(selectedModalAmenities.map(function(i) {
                        return {
                            amenity_id: i.amenityId,
                            quantity: i.quantity,
                            notes: i.notes
                        };
                    }));
                }
                if (amenitiesHiddenInputsContainer) {
                    amenitiesHiddenInputsContainer.innerHTML = hiddenHtml;
                }
            }

            function addAmenityFromInputs() {
                if (!amenityItemSelect || !amenityItemSelect.value) return false;
                const opt = amenityItemSelect.options[amenityItemSelect.selectedIndex];
                if (!opt || !opt.value) return false;

                const id = opt.value;
                const name = opt.getAttribute('data-name') || opt.text.split('(')[0].trim();
                const price = parseFloat(opt.getAttribute('data-price')) || 0;
                const charged = opt.getAttribute('data-charged') || 'item';
                const qty = Math.max(1, parseInt(amenityQuantity ? amenityQuantity.value : 1, 10) || 1);
                const notes = amenitySpecialReq ? amenitySpecialReq.value.trim() : '';

                const existingIndex = selectedModalAmenities.findIndex(function(a) {
                    return String(a.amenityId) === String(id);
                });

                if (existingIndex > -1) {
                    selectedModalAmenities[existingIndex].quantity += qty;
                    if (notes) {
                        selectedModalAmenities[existingIndex].notes = selectedModalAmenities[existingIndex].notes
                            ? (selectedModalAmenities[existingIndex].notes + ', ' + notes)
                            : notes;
                    }
                } else {
                    selectedModalAmenities.push({
                        amenityId: id,
                        name: name,
                        price: price,
                        charged: charged,
                        quantity: qty,
                        notes: notes
                    });
                }

                renderSelectedAmenitiesList();

                // Reset inputs for next selection so user can choose multiple amenities easily
                amenityItemSelect.value = '';
                if (amenityQuantity) amenityQuantity.value = 1;
                if (amenitySpecialReq) amenitySpecialReq.value = '';

                const scrollArea = document.getElementById('selectedAmenitiesScrollArea');
                if (scrollArea) {
                    scrollArea.scrollTop = scrollArea.scrollHeight;
                }
                return true;
            }

            if (amenityItemSelect) {
                amenityItemSelect.addEventListener('change', function() {
                    addAmenityFromInputs();
                });
            }

            if (btnAddAmenityToList) {
                btnAddAmenityToList.addEventListener('click', function() {
                    if (!amenityItemSelect || !amenityItemSelect.value) {
                        alert('Please choose an amenity from the dropdown first.');
                        return;
                    }
                    addAmenityFromInputs();
                });
            }

            if (selectedAmenitiesList) {
                selectedAmenitiesList.addEventListener('click', function(e) {
                    const btnRemove = e.target.closest('.btn-item-remove');
                    if (btnRemove) {
                        const index = parseInt(btnRemove.getAttribute('data-index'), 10);
                        if (!isNaN(index) && selectedModalAmenities[index]) {
                            selectedModalAmenities.splice(index, 1);
                            renderSelectedAmenitiesList();
                        }
                        return;
                    }

                    const btnMinus = e.target.closest('.btn-item-minus');
                    if (btnMinus) {
                        const index = parseInt(btnMinus.getAttribute('data-index'), 10);
                        if (!isNaN(index) && selectedModalAmenities[index]) {
                            if (selectedModalAmenities[index].quantity > 1) {
                                selectedModalAmenities[index].quantity--;
                                renderSelectedAmenitiesList();
                            } else {
                                selectedModalAmenities.splice(index, 1);
                                renderSelectedAmenitiesList();
                            }
                        }
                        return;
                    }

                    const btnPlus = e.target.closest('.btn-item-plus');
                    if (btnPlus) {
                        const index = parseInt(btnPlus.getAttribute('data-index'), 10);
                        if (!isNaN(index) && selectedModalAmenities[index]) {
                            if (selectedModalAmenities[index].quantity < 20) {
                                selectedModalAmenities[index].quantity++;
                                renderSelectedAmenitiesList();
                            }
                        }
                        return;
                    }
                });

                selectedAmenitiesList.addEventListener('change', function(e) {
                    const inputQty = e.target.closest('.input-item-quantity');
                    if (inputQty) {
                        const index = parseInt(inputQty.getAttribute('data-index'), 10);
                        if (!isNaN(index) && selectedModalAmenities[index]) {
                            let val = parseInt(inputQty.value, 10);
                            if (isNaN(val) || val < 1) val = 1;
                            if (val > 20) val = 20;
                            selectedModalAmenities[index].quantity = val;
                            renderSelectedAmenitiesList();
                        }
                    }
                });
            }

            function resetAmenitiesModalState() {
                selectedModalAmenities = [];
                renderSelectedAmenitiesList();
                if (amenityItemSelect) amenityItemSelect.value = '';
                if (amenityQuantity) amenityQuantity.value = 1;
                if (amenitySpecialReq) amenitySpecialReq.value = '';
            }

            if (amenitiesModalEl) {
                amenitiesModalEl.addEventListener('hidden.bs.modal', function() {
                    resetAmenitiesModalState();
                });
            }

            function updateGuestDetails(roomNumber, guestName, roomId, bookingId) {
                resetAmenitiesModalState();
                if (placeholderRoomNumber) {
                    placeholderRoomNumber.textContent = roomNumber ? `Room ${roomNumber}` : 'Room --';
                }
                if (placeholderGuestName) {
                    placeholderGuestName.textContent = guestName || (roomNumber ? `Guest in Room ${roomNumber}` : 'Guest Name');
                }
                if (formRoomId && roomId) {
                    formRoomId.value = roomId;
                }
                if (formBookingId) {
                    formBookingId.value = bookingId || '';
                }
            }

            if (amenityRoomSelect) {
                amenityRoomSelect.addEventListener('change', function() {
                    const selectedOpt = this.options[this.selectedIndex];
                    const rNum = selectedOpt ? selectedOpt.getAttribute('data-room-number') : '';
                    const gName = selectedOpt ? selectedOpt.getAttribute('data-guest-name') : '';
                    const rId = selectedOpt ? selectedOpt.value : '';
                    const bId = selectedOpt ? selectedOpt.getAttribute('data-booking-id') : '';
                    updateGuestDetails(rNum, gName, rId, bId);
                });
            }

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-trigger-amenity');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                const roomId = btn.getAttribute('data-room-id');
                const roomNumber = btn.getAttribute('data-room-number');
                const guestName = btn.getAttribute('data-guest-name');
                const bookingId = btn.getAttribute('data-booking-id');

                if (amenityRoomSelect) {
                    amenityRoomSelect.value = roomId;
                    const opt = amenityRoomSelect.querySelector(`option[value="${roomId}"]`);
                    const gName = guestName || (opt ? opt.getAttribute('data-guest-name') : '');
                    const bId = bookingId || (opt ? opt.getAttribute('data-booking-id') : '');
                    updateGuestDetails(roomNumber, gName, roomId, bId);
                }

                if (amenitiesModalEl) {
                    const modal = bootstrap.Modal.getInstance(amenitiesModalEl) || new bootstrap.Modal(amenitiesModalEl);
                    modal.show();
                }
            });

            if (amenitiesFormEl) {
                amenitiesFormEl.addEventListener('submit', function(e) {
                    // If an amenity is selected in the dropdown but hasn't been added yet, add it
                    if (amenityItemSelect && amenityItemSelect.value) {
                        addAmenityFromInputs();
                    }

                    if (!amenityRoomSelect || !amenityRoomSelect.value) {
                        e.preventDefault();
                        alert('Please select an occupied room.');
                        return;
                    }

                    if (selectedModalAmenities.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one amenity to add.');
                        return;
                    }

                    if (formRoomId) {
                        formRoomId.value = amenityRoomSelect.value;
                    }
                    const selectedOpt = amenityRoomSelect.options[amenityRoomSelect.selectedIndex];
                    if (formBookingId && selectedOpt) {
                        formBookingId.value = selectedOpt.getAttribute('data-booking-id') || '';
                    }

                    // Fallback populate single-item fields for backwards compatibility
                    if (selectedModalAmenities.length > 0) {
                        if (amenityItemSelect) {
                            amenityItemSelect.value = selectedModalAmenities[0].amenityId;
                        }
                        if (amenityQuantity) {
                            amenityQuantity.value = selectedModalAmenities[0].quantity;
                        }
                        if (amenitySpecialReq) {
                            amenitySpecialReq.value = selectedModalAmenities[0].notes || '';
                        }
                    }
                });
            }
        });
    </script>
    <!-- Direct Room Selection Script for Quick Modals -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            ['res_room_ids', 'checkin_room_ids'].forEach(function (id) {
                var selectEl = document.getElementById(id);
                if (!selectEl) return;
                selectEl.addEventListener('mousedown', function (e) {
                    if (e.target && e.target.tagName === 'OPTION' && !e.target.disabled) {
                        e.preventDefault();
                        e.target.selected = !e.target.selected;
                        selectEl.dispatchEvent(new Event('change'));
                    }
                });
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
        const discountWrap  = document.getElementById('bn_discount_wrap');
        const depositWrap   = document.getElementById('bn_deposit_wrap');
        const depositInput  = document.getElementById('bn_deposit');
        const payCash       = document.getElementById('bn_pay_cash');
        const payEwallet    = document.getElementById('bn_pay_ewallet');
        const ewalletFields = document.getElementById('bn_ewallet_fields');
        const ewalletType   = document.getElementById('bn_ewallet_type');
        const refNumberInput= document.getElementById('bn_reference_number');
        const payMethodLabel= document.getElementById('bn_payment_method_label');
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
                if (payMethodLabel) payMethodLabel.textContent = 'Payment Method';
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
                if (payMethodLabel) payMethodLabel.textContent = 'Payment Method (for Advance Deposit)';
            }
            onPaymentMethodChange();
            computeCheckOut();
            updatePrice();
        }
        modeNow.addEventListener('change', onModeChange);
        modeReserve.addEventListener('change', onModeChange);

        /* ─── Payment method toggle (Cash vs E-Wallet) ─── */
        function onPaymentMethodChange() {
            var isEwallet = payEwallet && payEwallet.checked;
            if (isEwallet) {
                if (ewalletFields) ewalletFields.style.display = '';
                var dep = parseFloat(depositInput ? depositInput.value : 0) || 0;
                if (getMode() === 'reserve' && dep <= 0) {
                    if (refNumberInput) refNumberInput.required = false;
                } else {
                    if (refNumberInput) refNumberInput.required = true;
                }
            } else {
                if (ewalletFields) ewalletFields.style.display = 'none';
                if (refNumberInput) {
                    refNumberInput.required = false;
                }
            }
        }
        if (payCash) payCash.addEventListener('change', onPaymentMethodChange);
        if (payEwallet) payEwallet.addEventListener('change', onPaymentMethodChange);
        if (depositInput) {
            depositInput.addEventListener('input', function () {
                onPaymentMethodChange();
            });
        }

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

        /* ─── Room button clicks: direct selection & toggle ─── */
        roomButtons.addEventListener('click', function (e) {
            var btn = e.target.closest('.bn-room-btn');
            if (!btn) return;
            var id = btn.getAttribute('data-room-id');
            if (selectedRoomIds.has(id)) {
                selectedRoomIds.delete(id);
            } else {
                selectedRoomIds.add(id);
            }
            // Update button visuals
            roomButtons.querySelectorAll('.bn-room-btn').forEach(function (b) {
                b.classList.toggle('active', selectedRoomIds.has(b.getAttribute('data-room-id')));
            });
            syncSelectFromSet();
            updatePrice();
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
        // Live clock in the top bar (Manila time) with dynamic format
        (function () {
            function formatPhpDate(date, format) {
                if (!format) return '';
                var options = {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric',
                    hour12: false
                };
                var parts = new Intl.DateTimeFormat('en-US', options).formatToParts(date);
                var p = {};
                parts.forEach(function(part) { p[part.type] = part.value; });

                var Y = p.year || '';
                var y = Y ? Y.slice(-2) : '';
                var m = p.month ? p.month.padStart(2, '0') : '';
                var n = p.month || '';
                var d = p.day ? p.day.padStart(2, '0') : '';
                var j = p.day || '';
                var H = p.hour === '24' ? '00' : (p.hour ? p.hour.padStart(2, '0') : '00');
                var hourInt = parseInt(H, 10);
                var hour12 = hourInt % 12 || 12;
                var h = String(hour12).padStart(2, '0');
                var g = String(hour12);
                var G = String(hourInt);
                var i = p.minute ? p.minute.padStart(2, '0') : '00';
                var s = p.second ? p.second.padStart(2, '0') : '00';
                var A = hourInt >= 12 ? 'PM' : 'AM';
                var a = hourInt >= 12 ? 'pm' : 'am';

                var monthNamesShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var monthNamesFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                var M = monthNamesShort[parseInt(n, 10) - 1] || '';
                var F = monthNamesFull[parseInt(n, 10) - 1] || '';

                var map = {
                    'Y': Y, 'y': y, 'm': m, 'n': n, 'd': d, 'j': j,
                    'H': H, 'h': h, 'G': G, 'g': g, 'i': i, 's': s,
                    'A': A, 'a': a, 'M': M, 'F': F
                };

                var res = '';
                for (var k = 0; k < format.length; k++) {
                    var ch = format[k];
                    res += (map.hasOwnProperty(ch) ? map[ch] : ch);
                }
                return res;
            }

            document.addEventListener('DOMContentLoaded', function () {
                var dateEl = document.getElementById('current-date');
                var timeEl = document.getElementById('current-time');
                if (!dateEl || !timeEl) return;

                var dateFormat = dateEl.getAttribute('data-format') || '{{ $dateFormat ?? "M d, Y" }}';
                var timeFormat = timeEl.getAttribute('data-format') || '{{ $timeFormat ?? "h:i A" }}';

                function tick() {
                    var now = new Date();
                    dateEl.textContent = formatPhpDate(now, dateFormat);
                    timeEl.textContent = formatPhpDate(now, timeFormat);
                }

                function syncSettings() {
                    fetch('{{ route("api.system.settings") }}')
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data && data.date_format) dateFormat = data.date_format;
                            if (data && data.time_format) timeFormat = data.time_format;
                            dateEl.setAttribute('data-format', dateFormat);
                            timeEl.setAttribute('data-format', timeFormat);
                            tick();
                        })
                        .catch(function(e) {});
                }

                tick();
                setInterval(tick, 1000);
                setInterval(syncSettings, 30000);
            });
        })();
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

    {{-- Real-time polling: notifications + KPI cards + room grid (receptionist overview only) --}}
    <script>
    (function () {
        'use strict';

        var POLL_URL = '/api/dashboard/notifications';
        var POLL_INTERVAL = 60000; // 60 seconds

        // Only run on the receptionist overview page (guard: KPI cards must be present)
        if (!document.getElementById('kpi-available')) return;

        /**
         * Build the HTML for a single notification row (matches the Blade template style).
         * @param {Object} guest
         * @returns {string}
         */
        function notificationRowHtml(guest) {
            var timeText = guest.is_overdue
                ? '<span class="text-danger fw-semibold">was due at ' + guest.checkout_time + ' (overdue)</span>'
                : guest.checkout_time + ' is their due time to check out (' + guest.minutes + ' min left)';

            return '<div class="d-flex align-items-start gap-2 py-2 border-bottom small">' +
                       '<span class="fw-bold">&rarr;</span>' +
                       '<div>' +
                           '<span class="fw-semibold">RM ' + guest.room_number + '</span> - ' + guest.name + ' - ' +
                           timeText +
                       '</div>' +
                   '</div>';
        }

        /**
         * Update the notification panel (#notificationList) and the See More modal (#allDueRoomsModal).
         * @param {Array} guests
         */
        function updateNotifications(guests) {
            var panel  = document.getElementById('notificationList');
            var modal  = document.getElementById('allDueRoomsModal');
            var badge  = document.getElementById('notificationCountBadge');

            if (!panel) return;

            var count = (guests && guests.length) ? guests.length : 0;

            // Update count badge
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            }

            // Panel shows max 2
            if (!guests || guests.length === 0) {
                panel.innerHTML = '<p class="text-muted small mb-0 py-2 fst-italic" style="font-style: italic;">No rooms due within the next 30 minutes.</p>';
            } else {
                panel.innerHTML = guests.slice(0, 2).map(notificationRowHtml).join('');
            }

            // Modal body shows all
            if (modal) {
                var body = modal.querySelector('.modal-body');
                if (body) {
                    if (!guests || guests.length === 0) {
                        body.innerHTML =
                            '<div class="text-center py-4 text-secondary">' +
                                '<p class="mb-0 fw-semibold fst-italic" style="font-style: italic;">No rooms due within the next 30 minutes.</p>' +
                                '<small class="text-muted d-block mt-1">Guests nearing their check-out time appear here automatically.</small>' +
                            '</div>';
                    } else {
                        body.innerHTML = guests.map(notificationRowHtml).join('');
                    }
                }
            }
        }

        /**
         * Update KPI number cards.
         * @param {Object} kpi  { available, occupied, reserved, maintenance, total }
         */
        function updateKpi(kpi) {
            var fields = {
                'kpi-available':   kpi.available,
                'kpi-occupied':    kpi.occupied,
                'kpi-reserved':    kpi.reserved,
                'kpi-maintenance': kpi.maintenance,
            };
            Object.keys(fields).forEach(function (id) {
                var el = document.getElementById(id);
                if (el) { el.textContent = fields[id]; }
            });
            // Sync total rooms label if present (e.g. "Total: X Rooms" in room management sections)
            if (kpi.total !== undefined) {
                var totalLabel = document.getElementById('total-rooms-label');
                if (totalLabel) { totalLabel.textContent = kpi.total; }
            }
        }

        /**
         * Server time offset synchronization for accurate live ticking
         */
        var serverTimeStr = '{{ now()->setTimezone("Asia/Manila")->toIso8601String() }}';
        var serverTimeOffset = new Date(serverTimeStr).getTime() - Date.now();

        function pad2(n) {
            return (n < 10 ? '0' : '') + n;
        }

        function formatOverdueTime(diffMs) {
            if (diffMs <= 0) return '00:00:00';
            var totalSec = Math.floor(diffMs / 1000);
            var days = Math.floor(totalSec / 86400);
            var hours = Math.floor((totalSec % 86400) / 3600);
            var minutes = Math.floor((totalSec % 3600) / 60);
            var seconds = totalSec % 60;

            if (days > 0) {
                return days + 'd ' + pad2(hours) + ':' + pad2(minutes) + ':' + pad2(seconds);
            }
            return pad2(hours) + ':' + pad2(minutes) + ':' + pad2(seconds);
        }

        /**
         * Live ticker that updates Overdue Time Counter on occupied room cards
         * every second only when the scheduled check-out date and time have passed.
         */
        function tickOverdueCounters() {
            var currentNow = Date.now() + serverTimeOffset;

            document.querySelectorAll('#hotelRoomGrid .hotel-room-card').forEach(function (card) {
                var isOccupied = card.getAttribute('data-status') === 'occupied';
                var counterEl = card.querySelector('.room-overdue-counter');
                var checkoutStr = counterEl ? counterEl.getAttribute('data-checkout') : card.getAttribute('data-checkout');

                if (!isOccupied || !checkoutStr) {
                    if (counterEl) { counterEl.classList.add('d-none'); }
                    return;
                }

                var checkoutTime = new Date(checkoutStr).getTime();
                if (isNaN(checkoutTime)) {
                    if (counterEl) { counterEl.classList.add('d-none'); }
                    return;
                }

                var diffMs = currentNow - checkoutTime;
                var hoverAction = card.querySelector('.card-hover-action');
                if (diffMs > 0) {
                    // Check-out date and time have passed -> display overdue counter directly below IN/OUT times
                    if (!counterEl) {
                        var timesContainer = card.querySelector('.room-times-text');
                        if (timesContainer) {
                            counterEl = document.createElement('div');
                            counterEl.className = 'room-overdue-counter text-danger fw-bold mt-1';
                            counterEl.setAttribute('data-checkout', checkoutStr);
                            counterEl.innerHTML = '<span class="overdue-label">Overdue:</span> <span class="overdue-timer">00:00:00</span>';
                            timesContainer.appendChild(counterEl);
                        }
                    }

                    if (counterEl) {
                        var timerEl = counterEl.querySelector('.overdue-timer');
                        if (timerEl) {
                            timerEl.textContent = formatOverdueTime(diffMs);
                        }
                        counterEl.classList.remove('d-none');
                    }

                    card.classList.add('is-overdue');
                    // Show OVERDUE overlay on the room image
                    var wrapper = card.querySelector('.room-image-wrapper');
                    if (wrapper && !wrapper.querySelector('.overlay-overdue')) {
                        var occupiedOverlay = wrapper.querySelector('.overlay-occupied');
                        if (occupiedOverlay) { occupiedOverlay.remove(); }
                        var od = document.createElement('div');
                        od.className = 'position-absolute top-0 start-0 w-100 h-100 overlay-overdue';
                        od.textContent = 'OVERDUE';
                        wrapper.appendChild(od);
                    }

                    // Hide Add Amenities hover action on overdue occupied room
                    if (hoverAction) {
                        hoverAction.style.display = 'none';
                    }
                } else {
                    // Scheduled check-out date and time have not passed yet
                    card.classList.remove('is-overdue');
                    if (counterEl) {
                        counterEl.classList.add('d-none');
                    }

                    // Remove OVERDUE overlay if it exists; restore occupied overlay if absent
                    var wrapper = card.querySelector('.room-image-wrapper');
                    if (wrapper) {
                        var odOverlay = wrapper.querySelector('.overlay-overdue');
                        if (odOverlay) {
                            odOverlay.remove();
                            if (!wrapper.querySelector('.overlay-occupied')) {
                                var occ = document.createElement('div');
                                occ.className = 'position-absolute top-0 start-0 w-100 h-100 overlay-occupied';
                                occ.textContent = 'OCCUPIED';
                                wrapper.appendChild(occ);
                            }
                        }
                    }

                    if (hoverAction) {
                        hoverAction.style.display = '';
                    }
                }
            });
        }

        /**
         * Update the status of each room card in the grid.
         * room_statuses is a plain object { "1": "available", "3": "occupied", ... }
         * room_checkouts is an optional object { "3": "2026-11-12T13:57:00+08:00", ... }
         * @param {Object} roomStatuses
         * @param {Object} [roomCheckouts]
         * @param {Object} [roomBlockingPeriods]
         * @param {Object} [roomTimes]
         */
        function updateRoomGrid(roomStatuses, roomCheckouts, roomBlockingPeriods, roomTimes) {
            if (!roomStatuses) return;

            var overlayMap = {
                occupied:          'overlay-occupied',
                reserved:          'overlay-reserved',
                under_maintenance: 'overlay-maintenance',
                maintenance:       'overlay-maintenance',
            };

            var labelMap = {
                occupied:          '<span class="room-status-badge text-danger">Occupied</span>',
                available:         '<span class="room-status-badge text-success">Available</span>',
                reserved:          '<span class="room-status-badge text-warning">Reserved</span>',
                under_maintenance: '<span class="room-status-badge text-secondary">Under Maintenance</span>',
                maintenance:       '<span class="room-status-badge text-secondary">Under Maintenance</span>',
            };

            document.querySelectorAll('[data-room-id]').forEach(function (card) {
                var roomId = card.getAttribute('data-room-id');
                if (!roomStatuses.hasOwnProperty(roomId)) return;

                var newStatus = roomStatuses[roomId];
                var dataStatus = newStatus === 'maintenance' ? 'under_maintenance' : newStatus;
                var oldStatus  = card.getAttribute('data-status');

                // Update checkout time if provided
                if (roomCheckouts && roomCheckouts.hasOwnProperty(roomId)) {
                    card.setAttribute('data-checkout', roomCheckouts[roomId]);
                    var cEl = card.querySelector('.room-overdue-counter');
                    if (cEl) {
                        cEl.setAttribute('data-checkout', roomCheckouts[roomId]);
                    }
                }

                // If not occupied, ensure overdue counter is hidden
                if (newStatus !== 'occupied') {
                    var overdueEl = card.querySelector('.room-overdue-counter');
                    if (overdueEl) {
                        overdueEl.classList.add('d-none');
                    }
                }

                // No change — skip DOM work
                if (dataStatus === oldStatus) return;

                // 1. Update data-status and is-occupied class
                card.setAttribute('data-status', dataStatus);
                card.classList.toggle('is-occupied', newStatus === 'occupied');

                // 2. Update the overlay inside room-image-wrapper
                var wrapper = card.querySelector('.room-image-wrapper');
                if (wrapper) {
                    // Remove any existing overlay div
                    ['overlay-occupied', 'overlay-reserved', 'overlay-maintenance', 'overlay-overdue'].forEach(function (cls) {
                        var existing = wrapper.querySelector('.' + cls);
                        if (existing) { existing.remove(); }
                    });

                    // Remove hover action button (only for occupied)
                    var hoverBtn = wrapper.querySelector('.card-hover-action');
                    if (hoverBtn) { hoverBtn.remove(); }

                    if (overlayMap[newStatus]) {
                        var overlay = document.createElement('div');
                        // Overdue check: if newly occupied and already past checkout, show overdue overlay instead
                        var overlayClass = overlayMap[newStatus];
                        if (newStatus === 'occupied') {
                            var coStrCheck = (roomCheckouts && roomCheckouts.hasOwnProperty(roomId))
                                ? roomCheckouts[roomId]
                                : card.getAttribute('data-checkout');
                            if (coStrCheck) {
                                var coTimeCheck = new Date(coStrCheck).getTime();
                                if (!isNaN(coTimeCheck) && (Date.now() + serverTimeOffset > coTimeCheck)) {
                                    overlayClass = 'overlay-overdue';
                                }
                            }
                        }
                        overlay.className = 'position-absolute top-0 start-0 w-100 h-100 ' + overlayClass;
                        if (overlayClass === 'overlay-overdue') {
                            overlay.textContent = 'OVERDUE';
                        } else if (overlayClass === 'overlay-occupied') {
                            overlay.textContent = 'OCCUPIED';
                        } else if (overlayClass === 'overlay-reserved') {
                            overlay.textContent = 'RESERVED';
                        } else if (overlayClass === 'overlay-maintenance') {
                            overlay.textContent = 'UNDER MAINTENANCE';
                        }
                        wrapper.appendChild(overlay);
                    }

                    if (newStatus === 'occupied') {
                        var checkoutStr = (roomCheckouts && roomCheckouts.hasOwnProperty(roomId))
                            ? roomCheckouts[roomId]
                            : card.getAttribute('data-checkout');
                        var isOverdue = false;
                        if (checkoutStr) {
                            var coTime = new Date(checkoutStr).getTime();
                            if (!isNaN(coTime) && (Date.now() + serverTimeOffset > coTime)) {
                                isOverdue = true;
                            }
                        }

                        card.classList.toggle('is-overdue', isOverdue);

                        if (!isOverdue) {
                            var heading = card.querySelector('.room-number-heading');
                            var roomNumberText = roomId;
                            if (heading) {
                                var match = heading.textContent.match(/\d+/);
                                if (match) { roomNumberText = match[0]; }
                            }
                            var roomNumberStr = String(roomNumberText).padStart(2, '0');
                            var hoverDiv = document.createElement('div');
                            hoverDiv.className = 'card-hover-action';
                            hoverDiv.innerHTML = '<button type="button" class="btn btn-card-amenities text-uppercase btn-trigger-amenity" data-room-id="' + roomId + '" data-room-number="' + roomNumberStr + '">ADD AMENITIES</button>';
                            wrapper.appendChild(hoverDiv);
                        }
                    }
                }

                // 3. Update the status badge text
                var statusContainer = card.querySelector('.room-card-body .room-status-badge');
                if (statusContainer) {
                    statusContainer.parentElement.innerHTML = labelMap[newStatus] ||
                        '<span class="room-status-badge text-secondary">' +
                            newStatus.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); }) +
                        '</span>';
                }

                // 3b. Update blocking period text if under maintenance, in/out if occupied, remove if reserved
                var timesEl = card.querySelector('.room-times-text');
                if (newStatus === 'under_maintenance' || newStatus === 'maintenance') {
                    var periodText = (roomBlockingPeriods && roomBlockingPeriods[roomId]) ? roomBlockingPeriods[roomId] : '';
                    if (periodText) {
                        if (timesEl) {
                            timesEl.className = 'room-times-text text-end room-blocking-period';
                            timesEl.textContent = periodText;
                        } else {
                            var footerEl = card.querySelector('.room-card-body .d-flex.mt-auto');
                            if (footerEl) {
                                var newTimesEl = document.createElement('div');
                                newTimesEl.className = 'room-times-text text-end room-blocking-period';
                                newTimesEl.textContent = periodText;
                                footerEl.appendChild(newTimesEl);
                            }
                        }
                    }
                } else if (newStatus === 'occupied') {
                    if (timesEl && timesEl.classList.contains('room-blocking-period')) {
                        timesEl.remove();
                        timesEl = null;
                    }
                    if (roomTimes && roomTimes[roomId]) {
                        var inTime = roomTimes[roomId].in;
                        var outTime = roomTimes[roomId].out;
                        var timesHtml = '<div><span class="text-muted">IN:</span> ' + inTime + '</div><div><span class="text-muted">OUT:</span> ' + outTime + '</div>';
                        if (timesEl) {
                            timesEl.className = 'room-times-text text-end';
                            timesEl.innerHTML = timesHtml;
                        } else {
                            var footerEl = card.querySelector('.room-card-body .d-flex.mt-auto');
                            if (footerEl) {
                                var newTimesEl = document.createElement('div');
                                newTimesEl.className = 'room-times-text text-end';
                                newTimesEl.innerHTML = timesHtml;
                                footerEl.appendChild(newTimesEl);
                            }
                        }
                    }
                } else if (newStatus === 'reserved') {
                    // Reserved: show Arrival date/time from data attribute, remove any old times element first
                    var arrivalText = card.getAttribute('data-reservation-arrival') || '';
                    if (timesEl) {
                        timesEl.remove();
                        timesEl = null;
                    }
                    if (arrivalText) {
                        var footerEl = card.querySelector('.room-card-body .d-flex.mt-auto');
                        if (footerEl) {
                            var arrivalEl = document.createElement('div');
                            arrivalEl.className = 'room-times-text text-end room-reservation-arrival';
                            arrivalEl.innerHTML = '<div><span class="text-muted">Arrival:</span></div><div>' + arrivalText + '</div>';
                            footerEl.appendChild(arrivalEl);
                        }
                    }
                } else if (timesEl && timesEl.classList.contains('room-blocking-period')) {
                    timesEl.remove();
                }

                // 4. Re-apply the active filter so the card shows/hides correctly
                var activeFilter = document.querySelector('.btn-filter.active');
                if (activeFilter) {
                    var filter = activeFilter.getAttribute('data-filter');
                    if (filter && filter !== 'all') {
                        card.style.display = (dataStatus === filter) ? 'flex' : 'none';
                    } else {
                        card.style.display = 'flex';
                    }
                }
            });
        }

        /**
         * Fetch the notifications endpoint and apply updates.
         */
        function poll() {
            fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) { throw new Error('HTTP ' + response.status); }
                    return response.json();
                })
                .then(function (data) {
                    if (data.server_time) {
                        var st = new Date(data.server_time).getTime();
                        if (!isNaN(st)) {
                            serverTimeOffset = st - Date.now();
                        }
                    }
                    updateNotifications(data.due_guests);
                    updateKpi(data.kpi);
                    // Sync reservation arrival attributes before updating grid so reserved cards get the latest text
                    if (data.room_reservation_arrivals) {
                        document.querySelectorAll('[data-room-id]').forEach(function (card) {
                            var roomId = card.getAttribute('data-room-id');
                            if (data.room_reservation_arrivals.hasOwnProperty(roomId)) {
                                card.setAttribute('data-reservation-arrival', data.room_reservation_arrivals[roomId]);
                            } else {
                                card.removeAttribute('data-reservation-arrival');
                            }
                        });
                    }
                    updateRoomGrid(data.room_statuses, data.room_checkouts, data.room_blocking_periods, data.room_times);
                    tickOverdueCounters();
                })
                .catch(function (err) {
                    console.warn('[Notifications Poll] Failed:', err);
                });
        }

        // Run overdue ticker every second for live updates
        tickOverdueCounters();
        setInterval(tickOverdueCounters, 1000);

        // Run immediately on page load, then every 60 s
        poll();
        setInterval(poll, POLL_INTERVAL);
    })();
    </script>

    {{-- Real-time Amenities Synchronization: Keeps CHOOSE AMENITY dropdown and Available Amenities lists synced without page refresh --}}
    <script>
    (function () {
        'use strict';

        var AMENITIES_API_URL = '/api/amenities';
        var lastAmenitiesSignature = null;

        function formatMoney(amount) {
            return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function syncAmenities(amenities) {
            if (!Array.isArray(amenities)) return;

            // Generate a signature to avoid unnecessary DOM operations if nothing changed
            var signature = JSON.stringify(amenities.map(function (a) {
                return [a.id, a.name, a.price, a.charged, a.offered];
            }));

            if (signature === lastAmenitiesSignature) {
                return;
            }
            lastAmenitiesSignature = signature;

            // 1. Update the CHOOSE AMENITY dropdown in the Add Amenities modal
            var selectEl = document.getElementById('amenityItemSelect');
            if (selectEl) {
                var currentVal = selectEl.value;
                var html = '<option value="" disabled' + (!currentVal ? ' selected' : '') + '>-- Choose an amenity --</option>';

                if (amenities.length === 0) {
                    html += '<option value="" disabled>No amenities available</option>';
                } else {
                    var hasCurrent = false;
                    amenities.forEach(function (amenity) {
                        var isSelected = String(amenity.id) === String(currentVal);
                        if (isSelected) hasCurrent = true;
                        var price = Number(amenity.price);
                        var charged = amenity.charged || 'item';
                        html += '<option value="' + amenity.id + '"' +
                            ' data-price="' + price + '"' +
                            ' data-name="' + escapeHtml(amenity.name) + '"' +
                            ' data-charged="' + escapeHtml(charged) + '"' +
                            (isSelected ? ' selected' : '') + '>' +
                            escapeHtml(amenity.name) + ' (₱' + formatMoney(price) + ' per ' + escapeHtml(charged) + ')' +
                            '</option>';
                    });

                    if (currentVal && !hasCurrent) {
                        selectEl.value = '';
                    }
                }
                selectEl.innerHTML = html;
            }

            // 2. Update the Quick Add Amenities list in the billing modal (if present)
            var quickAddList = document.getElementById('quickAddAmenitiesList');
            if (quickAddList) {
                var invoiceId = quickAddList.getAttribute('data-invoice-id') || '';
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrfInput = document.querySelector('input[name="_token"]');
                var csrfToken = csrfMeta ? csrfMeta.content : (csrfInput ? csrfInput.value : '');

                if (amenities.length === 0) {
                    quickAddList.innerHTML = '<li class="list-group-item px-2 py-2 border-0 bg-transparent text-muted small">No amenities available.</li>';
                } else {
                    var listHtml = '';
                    amenities.forEach(function (item) {
                        var price = Number(item.price);
                        listHtml += '<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-2 border-0 bg-transparent" data-amenity-id="' + item.id + '">' +
                            '<span class="small">' + escapeHtml(item.name) + '</span>' +
                            '<div class="d-flex align-items-center">' +
                                '<span class="text-muted small me-3">₱ ' + formatMoney(price) + '</span>' +
                                '<div class="d-flex gap-1">' +
                                    '<form action="/billing/' + invoiceId + '/remove-amenity" method="POST" class="m-0">' +
                                        '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                        '<input type="hidden" name="item_name" value="' + escapeHtml(item.name) + '">' +
                                        '<button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;" title="Remove 1">-</button>' +
                                    '</form>' +
                                    '<form action="/billing/' + invoiceId + '/add-amenity" method="POST" class="m-0">' +
                                        '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                        '<input type="hidden" name="item_name" value="' + escapeHtml(item.name) + '">' +
                                        '<input type="hidden" name="price" value="' + price + '">' +
                                        '<button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;" title="Add 1">+</button>' +
                                    '</form>' +
                                '</div>' +
                            '</div>' +
                        '</li>';
                    });
                    quickAddList.innerHTML = listHtml;
                }
            }

            // 3. Update the Add Amenities to booking modal list (if present)
            var bookingAddList = document.getElementById('bookingAddAmenitiesList');
            if (bookingAddList) {
                var currentQtys = {};
                bookingAddList.querySelectorAll('input[type="number"][name^="qty["]').forEach(function (input) {
                    var match = input.name.match(/qty\[(\d+)\]/);
                    if (match) {
                        currentQtys[match[1]] = input.value;
                    }
                });

                if (amenities.length === 0) {
                    bookingAddList.innerHTML = '<div class="py-3 text-center text-muted small">No amenities currently offered.</div>';
                } else {
                    var bHtml = '';
                    amenities.forEach(function (item) {
                        var price = Number(item.price);
                        var charged = item.charged || 'item';
                        var existingQty = currentQtys[item.id] || '0';
                        bHtml += '<div class="d-flex justify-content-between align-items-center py-2 border-bottom" data-amenity-id="' + item.id + '">' +
                            '<div>' +
                                '<div class="fw-semibold">' + escapeHtml(item.name) + '</div>' +
                                '<div class="small text-muted">₱' + formatMoney(price) + ' per ' + escapeHtml(charged) + '</div>' +
                            '</div>' +
                            '<input type="number" name="qty[' + item.id + ']" value="' + existingQty + '" min="0" max="20" class="form-control form-control-sm text-center" style="width: 5rem;">' +
                        '</div>';
                    });
                    bookingAddList.innerHTML = bHtml;
                }
            }
        }

        function fetchLatestAmenities() {
            fetch(AMENITIES_API_URL, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (data && Array.isArray(data.amenities)) {
                    syncAmenities(data.amenities);
                }
            })
            .catch(function () {});
        }

        // Cross-tab real-time sync via BroadcastChannel
        if (typeof BroadcastChannel !== 'undefined') {
            try {
                var bc = new BroadcastChannel('hotel_amenities_sync');
                bc.onmessage = function (ev) {
                    fetchLatestAmenities();
                };
            } catch (e) {}
        }

        // Cross-tab fallback via storage event
        window.addEventListener('storage', function (e) {
            if (e.key === 'hotel_amenities_updated_at') {
                fetchLatestAmenities();
            }
        });

        // Re-fetch immediately when opening any amenities modal
        var amenitiesModal = document.getElementById('amenitiesModal');
        if (amenitiesModal) {
            amenitiesModal.addEventListener('show.bs.modal', function () {
                fetchLatestAmenities();
            });
        }
        var addAmenityModal = document.getElementById('addAmenityModal');
        if (addAmenityModal) {
            addAmenityModal.addEventListener('show.bs.modal', function () {
                fetchLatestAmenities();
            });
        }

        // Re-fetch on tab focus
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetchLatestAmenities();
            }
        });

        // Periodic background poll every 2.5 seconds
        setInterval(fetchLatestAmenities, 2500);

        // Initial fetch on load
        fetchLatestAmenities();
    })();
    </script>

    @stack('scripts')

    {{-- Billing page: Reference Number toggle + Payment Details modal --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ── Reference Number field in Billing Process modal ──
        var payCash    = document.getElementById('pay_cash');
        var payEwallet = document.getElementById('pay_ewallet');
        var refWrap    = document.getElementById('billing_ewallet_ref_wrap');
        var refInput   = document.getElementById('billing_reference_number');

        function toggleBillingRef() {
            if (!refWrap) return;
            var isEwallet = payEwallet && payEwallet.checked;
            refWrap.classList.toggle('d-none', !isEwallet);
            if (refInput) refInput.required = isEwallet;
        }

        if (payCash)    payCash.addEventListener('change',    toggleBillingRef);
        if (payEwallet) payEwallet.addEventListener('change', toggleBillingRef);
        toggleBillingRef();

        // ── View Payment Details button ──
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-view-payment');
            if (!btn) return;

            var modal = document.getElementById('paymentDetailsModal');
            if (!modal) return;

            // Populate header info
            document.getElementById('pd_guest_name').textContent = btn.dataset.guest || '—';
            document.getElementById('pd_rooms').textContent      = btn.dataset.rooms || '—';
            document.getElementById('pd_paid_at').textContent    = btn.dataset.paidAt || '—';
            document.getElementById('pd_method').textContent     = btn.dataset.method || '—';
            document.getElementById('pd_grand_total').textContent = '₱ ' + (btn.dataset.grandTotal || '0.00');

            // E-Wallet reference number
            var refRow = document.getElementById('pd_ref_wrap');
            var refEl  = document.getElementById('pd_reference_number');
            if (btn.dataset.isEwallet === '1' && btn.dataset.referenceNumber) {
                refEl.textContent = btn.dataset.referenceNumber;
                refRow.classList.remove('d-none');
            } else {
                refRow.classList.add('d-none');
            }

            // Populate charges table
            var tbody = document.getElementById('pd_charges_body');
            tbody.innerHTML = '';
            var charges = [];
            try { charges = JSON.parse(btn.dataset.charges || '[]'); } catch (ex) {}

            if (charges.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted small">No itemized details available.</td></tr>';
            } else {
                charges.forEach(function (charge) {
                    var tr = document.createElement('tr');
                    var amtNum = Number(charge.amount);
                    var amtFormatted = (amtNum < 0 ? '- \u20b1 ' : '\u20b1 ') + Math.abs(amtNum).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    tr.innerHTML = '<td class="small">' + (charge.description || '\u2014') + '</td>' +
                                   '<td class="text-end small">' + amtFormatted + '</td>';
                    tbody.appendChild(tr);
                });
            }

            var bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
            bsModal.show();
        });

    });
    </script>
<!--End of Scripts  -->