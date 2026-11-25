<!-- Modal: Export Booking History to Excel -->
<div class="modal fade" id="exportBookingModal" tabindex="-1" aria-labelledby="exportBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded p-2 text-white d-flex align-items-center justify-content-center" style="background-color: #198754; width: 34px; height: 34px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark m-0 fs-6" id="exportBookingModalLabel">Export Booking History</h5>
                        <small class="text-muted">Generate and download booking records within selected date range</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="exportBookingForm" method="GET" action="{{ route('booking.export') }}">
                <div class="modal-body p-4">
                    <!-- Preset Hidden Field -->
                    <input type="hidden" name="preset" id="bookingExportPreset" value="thisMonth">
                    @if(request('filter'))
                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                    @endif
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif

                    <!-- 1. Date Range Presets -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.04em;">Date Range Presets</label>
                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Date Range Presets" id="bookingDatePresets">
                            <button type="button" class="btn btn-sm btn-outline-secondary booking-preset-btn flex-fill" data-preset="last7">Last 7 Days</button>
                            <button type="button" class="btn btn-sm booking-preset-btn flex-fill active" data-preset="thisMonth" style="background-color: #6A4435; border-color: #6A4435; color: #ffffff;">This Month</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary booking-preset-btn flex-fill" data-preset="lastMonth">Last Month</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary booking-preset-btn flex-fill" data-preset="custom">Custom</button>
                        </div>
                    </div>

                    <!-- 2. Calendar Date Filters (From and To, M/D/Y) -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 0.04em;">Calendar Date Filters</label>
                            <span class="badge bg-light text-secondary border fw-normal">Format: M/D/Y</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label for="bookingExportFrom" class="form-label small fw-semibold text-dark mb-1">From <span class="text-muted fw-normal">(M/D/Y)</span></label>
                                <input type="date" class="form-control" id="bookingExportFrom" name="from" placeholder="MM/DD/YYYY" required>
                                <div class="form-text text-muted small" style="font-size: 0.725rem;">Format: M/D/Y</div>
                            </div>
                            <div class="col-6">
                                <label for="bookingExportTo" class="form-label small fw-semibold text-dark mb-1">To <span class="text-muted fw-normal">(M/D/Y)</span></label>
                                <input type="date" class="form-control" id="bookingExportTo" name="to" placeholder="MM/DD/YYYY" required>
                                <div class="form-text text-muted small" style="font-size: 0.725rem;">Format: M/D/Y</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Export Button at bottom -->
                <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between align-items-center" style="background-color: #443627;">
                    <button type="button" class="btn btn-link text-decoration-none p-0 fw-semibold" style="color: #F5F7DF;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 fw-semibold px-3" id="confirmExportBookingBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                        </svg>
                        Export to Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fromInput = document.getElementById('bookingExportFrom');
    const toInput = document.getElementById('bookingExportTo');
    const presetBtns = document.querySelectorAll('.booking-preset-btn');
    const presetHidden = document.getElementById('bookingExportPreset');

    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function setActivePresetBtn(btn) {
        presetBtns.forEach(function (b) {
            b.classList.remove('active');
            b.classList.add('btn-outline-secondary');
            b.style.backgroundColor = '';
            b.style.borderColor = '';
            b.style.color = '';
        });
        if (btn) {
            btn.classList.add('active');
            btn.classList.remove('btn-outline-secondary');
            btn.style.backgroundColor = '#6A4435';
            btn.style.borderColor = '#6A4435';
            btn.style.color = '#ffffff';
        }
    }

    function applyPreset(presetType) {
        const now = new Date();
        let fromDate, toDate;

        if (presetType === 'last7') {
            toDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            fromDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6);
        } else if (presetType === 'thisMonth') {
            fromDate = new Date(now.getFullYear(), now.getMonth(), 1);
            toDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        } else if (presetType === 'lastMonth') {
            fromDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            toDate = new Date(now.getFullYear(), now.getMonth(), 0);
        } else if (presetType === 'custom') {
            if (presetHidden) presetHidden.value = 'custom';
            if (fromInput) fromInput.focus();
            return;
        }

        if (fromDate && toDate && fromInput && toInput) {
            fromInput.value = formatDate(fromDate);
            toInput.value = formatDate(toDate);
        }
        if (presetHidden) {
            presetHidden.value = presetType;
        }
    }

    presetBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const preset = this.getAttribute('data-preset');
            setActivePresetBtn(this);
            applyPreset(preset);
        });
    });

    [fromInput, toInput].forEach(function (input) {
        if (!input) return;
        input.addEventListener('change', function () {
            const customBtn = document.querySelector('.booking-preset-btn[data-preset="custom"]');
            setActivePresetBtn(customBtn);
            if (presetHidden) presetHidden.value = 'custom';
        });
    });

    // Populate initial default (This Month) when modal is opened or on load
    const exportModal = document.getElementById('exportBookingModal');
    if (exportModal) {
        exportModal.addEventListener('show.bs.modal', function () {
            if (fromInput && toInput && (!fromInput.value || !toInput.value)) {
                const thisMonthBtn = document.querySelector('.booking-preset-btn[data-preset="thisMonth"]');
                setActivePresetBtn(thisMonthBtn);
                applyPreset('thisMonth');
            }
        });
    }

    // Pre-populate values immediately
    if (fromInput && toInput && !fromInput.value) {
        applyPreset('thisMonth');
    }
});
</script>
