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

    <!-- Replace action with your settings update route -->
    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Administrator Credentials Section -->
        <h6 class="fw-bold section-heading mb-3">Administrator Credentials</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="username" class="form-label fw-semibold">Username</label>
                <input type="text"
                       class="form-control"
                       id="username"
                       name="username"
                       value="{{ old('username', auth()->user()->username ?? $adminUser->username ?? 'admin') }}"
                       required>
            </div>

            <div class="col-md-4">
                <label for="password" class="form-label fw-semibold">New Password</label>
                <div class="input-group">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Leave blank to keep current">
                    <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password" title="Toggle password visibility">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                    </button>
                </div>
            </div>

            <div class="col-md-4">
                <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                <div class="input-group">
                    <input type="password"
                           class="form-control"
                           id="password_confirmation"
                           name="password_confirmation"
                           placeholder="Re-enter new password">
                    <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password_confirmation" title="Toggle password visibility">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Timestamp Format Customization Section -->
        <h6 class="fw-bold section-heading mb-3">Timestamp Display Format</h6>
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
                <label for="time_format" class="form-label fw-semibold">Time Format</label>
                <select class="form-select" id="time_format" name="time_format">
                    <option value="h:i A" data-preview="11:25 AM" {{ ($timeFormat ?? 'h:i A') === 'h:i A' ? 'selected' : '' }}>
                        12-Hour (11:25 AM)
                    </option>
                    <option value="h:i:s A" data-preview="11:25:00 AM" {{ ($timeFormat ?? '') === 'h:i:s A' ? 'selected' : '' }}>
                        12-Hour with Seconds (11:25:00 AM)
                    </option>
                    <option value="H:i" data-preview="11:25" {{ ($timeFormat ?? '') === 'H:i' ? 'selected' : '' }}>
                        24-Hour (11:25)
                    </option>
                    <option value="H:i:s" data-preview="11:25:00" {{ ($timeFormat ?? '') === 'H:i:s' ? 'selected' : '' }}>
                        24-Hour with Seconds (11:25:00)
                    </option>
                </select>
            </div>
        </div>

        <div class="preview-box p-3 rounded mb-4">
            <small class="text-muted d-block">Live Timestamp Preview</small>
            <span id="timestamp_preview" class="fw-bold" style="color: #443627;">Nov 20, 2026 11:25 AM</span>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-vintage px-4">Save Changes</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateSelect = document.getElementById('date_format');
        const timeSelect = document.getElementById('time_format');
        const previewText = document.getElementById('timestamp_preview');

        function updatePreview() {
            const dateSample = dateSelect.options[dateSelect.selectedIndex].getAttribute('data-preview');
            const timeSample = timeSelect.options[timeSelect.selectedIndex].getAttribute('data-preview');
            previewText.textContent = (dateSample || '') + ' ' + (timeSample || '');
        }

        dateSelect.addEventListener('change', updatePreview);
        timeSelect.addEventListener('change', updatePreview);
        updatePreview();

        // Password visibility toggle
        document.querySelectorAll('.toggle-password-visibility').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z"/><path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z"/></svg>';
                } else {
                    input.type = 'password';
                    this.innerHTML = '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>';
                }
            });
        });
    });
</script>