@extends('Receptionist.layouts.dashboard')

@section('title', 'System Settings')

@section('receptionist-content')
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
    .preview-box {
        background-color: #f8f6f4;
        border-left: 4px solid #443627;
    }
</style>

<div class="container-fluid p-0">
    <div class="card p-4 border rounded shadow-sm bg-white">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: #443627;">System Settings</h5>
            <span class="badge text-bg-light border text-secondary fw-semibold">Receptionist Portal</span>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Synchronization Status Banner -->
        <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background-color: #fdfbf7; border-color: #e2d9cf !important;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px; background-color: #443627;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                    </svg>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <strong class="text-dark">Synchronized System Timestamp</strong>
                        <span class="badge bg-success bg-opacity-75 text-white small">Synchronized with Administrator</span>
                    </div>
                    <span class="small text-muted">All booking records, payment records, Activity Logs, and dashboard displays operate under unified system-wide timestamp settings synchronized with Administrator settings.</span>
                </div>
            </div>
            <div class="text-end d-none d-md-block">
                <span class="badge bg-light text-secondary border">Asia/Manila (GMT+8)</span>
            </div>
        </div>

        <!-- Section 1: Active Receptionist Profile Details -->
        <h6 class="fw-bold section-heading mb-3">Receptionist Account Profile</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Staff Member</label>
                <div class="p-2.5 px-3 rounded bg-light border fw-semibold text-dark">
                    {{ $staff ? trim($staff->first_name . ' ' . $staff->last_name) : (session('staff_name') ?? 'Receptionist Staff') }}
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Username</label>
                <div class="p-2.5 px-3 rounded bg-light border fw-semibold text-dark">
                    {{ '@' . ($staff->username ?? session('staff_username') ?? 'staff') }}
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Email / Gmail</label>
                <div class="p-2.5 px-3 rounded bg-light border text-dark">
                    {{ $staff->email ?? $staff->gmail ?? 'N/A' }}
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Contact Number</label>
                <div class="p-2.5 px-3 rounded bg-light border text-dark">
                    {{ $staff->contact_number ?? 'N/A' }}
                </div>
            </div>
        </div>

        <div class="p-3 bg-light rounded border mb-4">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                </svg>
                <span>Receptionist account credentials and permissions are managed centrally by the Administrator under <strong>Account Management</strong>. Administrator-level credentials and system configurations are accessible exclusively by the Administrator.</span>
            </div>
        </div>

        <!-- Section 2: Timestamp Display Format (Synchronized System Settings) -->
        <h6 class="fw-bold section-heading mb-3">Timestamp Display Format</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Time Format Mode</label>
                <div class="p-2.5 px-3 rounded bg-light border">
                    <span class="badge bg-secondary text-white me-2">
                        {{ \App\Services\SystemFormatService::is24Hour() ? '24-Hour' : '12-Hour (AM/PM)' }}
                    </span>
                    <span class="fw-semibold text-dark">
                        {{ \App\Services\SystemFormatService::is24Hour() ? '24-Hour Standard Time' : '12-Hour Clock (with AM/PM)' }}
                    </span>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Active Time Format</label>
                <div class="p-2.5 px-3 rounded bg-light border fw-semibold text-dark">
                    <code>{{ $timeFormat ?? \App\Services\SystemFormatService::timeFormat() }}</code>
                    <span class="text-muted ms-2 small">({{ \App\Services\SystemFormatService::formatTime(now()) }})</span>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Active Date Format</label>
                <div class="p-2.5 px-3 rounded bg-light border fw-semibold text-dark">
                    <code>{{ $dateFormat ?? \App\Services\SystemFormatService::dateFormat() }}</code>
                    <span class="text-muted ms-2 small">({{ \App\Services\SystemFormatService::formatDate(now()) }})</span>
                </div>
            </div>
        </div>

        <!-- Live Manila Timestamp Preview Box -->
        <div class="preview-box p-3 rounded mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.75rem;">Current Live Manila Timestamp</small>
                    <span id="receptionist_live_timestamp" class="fw-bold fs-5" style="color: #443627;">
                        {{ \App\Services\SystemFormatService::formatDateTime(now()) }}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-success" role="status" aria-hidden="true"></span>
                    <span class="small text-muted">Synchronized Live Clock (Asia/Manila)</span>
                </div>
            </div>
        </div>

        <!-- Applicable Coverage Info -->
        <div class="p-3 bg-light rounded border">
            <div class="row g-2 text-muted small">
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#198754" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.57 2.57 5.902-5.924z"/>
                    </svg>
                    <span>Applied to: Booking History & Reservations Check-in / Check-out</span>
                </div>
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#198754" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.57 2.57 5.902-5.924z"/>
                    </svg>
                    <span>Applied to: Billing Invoices & Confirmed Payment Records</span>
                </div>
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#198754" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.57 2.57 5.902-5.924z"/>
                    </svg>
                    <span>Applied to: Due Room Alerts & In-House Guest Statuses</span>
                </div>
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#198754" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.57 2.57 5.902-5.924z"/>
                    </svg>
                    <span>Applied to: Topbar Real-Time Live Clock Display</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        function formatPhpDate(date, format) {
            var monthsShort = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            var daysShort = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            var daysFull = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

            var Y = date.getFullYear();
            var y = String(Y).slice(-2);
            var n = date.getMonth() + 1;
            var m = (n < 10 ? '0' : '') + n;
            var M = monthsShort[date.getMonth()];
            var F = monthsFull[date.getMonth()];
            var j = date.getDate();
            var d = (j < 10 ? '0' : '') + j;
            var D = daysShort[date.getDay()];
            var l = daysFull[date.getDay()];

            var G = date.getHours();
            var H = (G < 10 ? '0' : '') + G;
            var g = G % 12 || 12;
            var h = (g < 10 ? '0' : '') + g;
            var i = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
            var s = (date.getSeconds() < 10 ? '0' : '') + date.getSeconds();
            var A = G >= 12 ? 'PM' : 'AM';
            var a = A.toLowerCase();

            var map = {
                'Y': Y, 'y': y, 'm': m, 'n': n, 'M': M, 'F': F, 'd': d, 'j': j, 'D': D, 'l': l,
                'H': H, 'h': h, 'G': G, 'g': g, 'i': i, 's': s, 'A': A, 'a': a
            };

            var res = '';
            for (var k = 0; k < format.length; k++) {
                var ch = format[k];
                res += (map.hasOwnProperty(ch) ? map[ch] : ch);
            }
            return res;
        }

        document.addEventListener('DOMContentLoaded', function () {
            var liveEl = document.getElementById('receptionist_live_timestamp');
            var dateFormat = '{{ $dateFormat ?? "M d, Y" }}';
            var timeFormat = '{{ $timeFormat ?? "h:i A" }}';

            function updateLive() {
                var now = new Date();
                var dStr = formatPhpDate(now, dateFormat);
                var tStr = formatPhpDate(now, timeFormat);
                if (liveEl) {
                    liveEl.textContent = dStr + ' ' + tStr;
                }
            }

            function syncSettings() {
                fetch('{{ route("api.system.settings") }}')
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.date_format) dateFormat = data.date_format;
                        if (data && data.time_format) timeFormat = data.time_format;
                        updateLive();
                    })
                    .catch(function() {});
            }

            updateLive();
            setInterval(updateLive, 1000);
            setInterval(syncSettings, 30000);
        });
    })();
</script>
@endsection
