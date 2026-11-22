<style>
    .btn-vintage {
        background-color: #443627;
        border-color: #443627;
        color: #ffffff;
    }
    .btn-vintage:hover,
    .btn-vintage:focus {
        background-color: #5c4a36;
        border-color: #5c4a36;
        color: #ffffff;
    }
    .section-heading {
        color: #443627;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
    }
    .form-control:focus,
    .form-select:focus {
        border-color: #5c4a36;
        box-shadow: 0 0 0 0.2rem rgba(68, 54, 39, 0.2);
    }
    .preview-box {
        background-color: #f8f6f4;
        border-left: 4px solid #443627;
    }
</style>

<div class="card p-4 border rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0" style="color: #443627;">System Settings</h5>
        <span class="badge text-bg-light border">Administrator</span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Information Notice: Administrator Credentials Moved to Account Management -->
    <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background-color: #fdfbf7; border-color: #e2d9cf !important;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; background-color: #443627;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                </svg>
            </div>
            <div>
                <strong class="d-block text-dark">Administrator Credentials Management</strong>
                <span class="small text-muted">Administrator username and password credentials are now managed in <strong>Account Management</strong>, positioned immediately after Receptionist Credentials.</span>
            </div>
        </div>
        <a href="{{ url('/admindashboard?section=accman#adminCredentialsSection') }}" class="btn btn-sm btn-outline-dark fw-semibold text-nowrap px-3">
            Go to Account Management &rarr;
        </a>
    </div>

    <!-- Timestamp Format Customization Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <h6 class="fw-bold section-heading mb-2">Timestamp Display Format</h6>
            <p class="text-muted small mb-3">
                Configure how dates and times are displayed throughout the entire system. Changes are saved to the database and consistently applied across both Administrator and Receptionist dashboards, booking records, payment records, Activity Logs, and notifications.
            </p>

            <!-- Format Mode Selector: 12-Hour vs 24-Hour -->
            <div class="mb-4 p-3 rounded border bg-light">
                <label class="form-label fw-bold text-dark small text-uppercase mb-2">Time Display Mode</label>
                <div class="d-flex gap-4 flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="time_mode_radio" id="mode_12hour" value="12" {{ (!$is24Hour) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold text-dark" for="mode_12hour">
                            12-Hour Format (AM/PM) <span class="text-muted fw-normal">(e.g. 02:30 PM)</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="time_mode_radio" id="mode_24hour" value="24" {{ ($is24Hour) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold text-dark" for="mode_24hour">
                            24-Hour Format <span class="text-muted fw-normal">(e.g. 14:30)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="date_format" class="form-label fw-semibold">Date Format</label>
                    <select class="form-select" id="date_format" name="date_format">
                        <option value="M d, Y" data-preview="Nov 20, 2026" {{ ($dateFormat ?? 'M d, Y') === 'M d, Y' ? 'selected' : '' }}>
                            Nov 20, 2026 (M d, Y)
                        </option>
                        <option value="F d, Y" data-preview="November 20, 2026" {{ ($dateFormat ?? '') === 'F d, Y' ? 'selected' : '' }}>
                            November 20, 2026 (F d, Y)
                        </option>
                        <option value="Y-m-d" data-preview="2026-11-20" {{ ($dateFormat ?? '') === 'Y-m-d' ? 'selected' : '' }}>
                            2026-11-20 (Y-m-d)
                        </option>
                        <option value="m/d/Y" data-preview="11/20/2026" {{ ($dateFormat ?? '') === 'm/d/Y' ? 'selected' : '' }}>
                            11/20/2026 (m/d/Y)
                        </option>
                        <option value="d/m/Y" data-preview="20/11/2026" {{ ($dateFormat ?? '') === 'd/m/Y' ? 'selected' : '' }}>
                            20/11/2026 (d/m/Y)
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="time_format" class="form-label fw-semibold">Time Format Option</label>
                    <select class="form-select" id="time_format" name="time_format">
                        <!-- 12-Hour Group -->
                        <optgroup label="12-Hour Format (AM/PM)" id="optgroup_12h">
                            <option value="h:i A" data-preview="11:25 AM" {{ ($timeFormat ?? 'h:i A') === 'h:i A' ? 'selected' : '' }}>
                                12-Hour Standard (11:25 AM)
                            </option>
                            <option value="h:i:s A" data-preview="11:25:00 AM" {{ ($timeFormat ?? '') === 'h:i:s A' ? 'selected' : '' }}>
                                12-Hour with Seconds (11:25:00 AM)
                            </option>
                        </optgroup>
                        <!-- 24-Hour Group -->
                        <optgroup label="24-Hour Format" id="optgroup_24h">
                            <option value="H:i" data-preview="11:25" {{ ($timeFormat ?? '') === 'H:i' ? 'selected' : '' }}>
                                24-Hour Standard (11:25)
                            </option>
                            <option value="H:i:s" data-preview="11:25:00" {{ ($timeFormat ?? '') === 'H:i:s' ? 'selected' : '' }}>
                                24-Hour with Seconds (11:25:00)
                            </option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="preview-box p-3 rounded mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.75rem;">Live Manila Timestamp Preview</small>
                        <span id="timestamp_preview" class="fw-bold fs-5" style="color: #443627;">Nov 20, 2026 11:25 AM</span>
                    </div>
                    <span class="badge bg-light text-secondary border">Asia/Manila (GMT+8)</span>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-vintage px-4 fw-semibold">Save Settings</button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateSelect = document.getElementById('date_format');
        const timeSelect = document.getElementById('time_format');
        const previewText = document.getElementById('timestamp_preview');
        const radio12 = document.getElementById('mode_12hour');
        const radio24 = document.getElementById('mode_24hour');

        function updatePreview() {
            const dateSample = dateSelect.options[dateSelect.selectedIndex].getAttribute('data-preview');
            const timeSample = timeSelect.options[timeSelect.selectedIndex].getAttribute('data-preview');
            previewText.textContent = (dateSample || '') + ' ' + (timeSample || '');
        }

        function syncRadioWithSelect() {
            const val = timeSelect.value;
            if (val.indexOf('H') !== -1) {
                if (radio24) radio24.checked = true;
            } else {
                if (radio12) radio12.checked = true;
            }
        }

        if (radio12) {
            radio12.addEventListener('change', function () {
                if (this.checked && timeSelect.value.indexOf('H') !== -1) {
                    timeSelect.value = 'h:i A';
                    updatePreview();
                }
            });
        }

        if (radio24) {
            radio24.addEventListener('change', function () {
                if (this.checked && timeSelect.value.indexOf('H') === -1) {
                    timeSelect.value = 'H:i';
                    updatePreview();
                }
            });
        }

        dateSelect.addEventListener('change', updatePreview);
        timeSelect.addEventListener('change', function () {
            syncRadioWithSelect();
            updatePreview();
        });

        syncRadioWithSelect();
        updatePreview();
    });
</script>