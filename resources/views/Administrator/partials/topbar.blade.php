<!-- Topbar -->
<header class="bg-white border-bottom shadow-sm px-3 px-sm-4 py-3 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <!-- Hamburger Menu Button for mobile -->
        <button class="btn bg-white border rounded shadow-sm d-md-none d-flex align-items-center justify-content-center p-2 text-secondary" 
                type="button" 
                data-bs-toggle="offcanvas" 
                data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu"
                aria-label="Toggle navigation"
                id="openSidebar"
                style="width: 38px; height: 38px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>
        </button>

        <!-- Welcoming Greeting -->
        <h4 class="mb-0 fw-bold text-dark text-truncate" style="font-size: clamp(1rem, 1.3vw, 1.45rem); letter-spacing: -0.01em;">
            Good day, Name!
        </h4>
    </div>

    <!-- Header Action Buttons -->
    <div class="d-flex align-items-center gap-2 gap-sm-3">
        <!-- Date and Time (live, Manila time) -->
        <div class="d-none d-sm-flex border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary small">
            <span id="current-date">{{ now()->setTimezone('Asia/Manila')->format('M d, Y') }}</span>
            <span id="liveDate" class="d-none">{{ now()->setTimezone('Asia/Manila')->format('m/d/Y') }}</span>
        </div>
        <div class="border px-2 px-sm-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary small">
            <span id="current-time">{{ now()->setTimezone('Asia/Manila')->format('h:i A') }}</span>
            <span id="liveTime" class="d-none">{{ now()->setTimezone('Asia/Manila')->format('h:i A') }}</span>
        </div>

        <!-- Admin Avatar -->
        <div class="border rounded-circle bg-white shadow-sm fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; color: #6A4435; font-size: 0.95rem;" title="Administrator">
            A
        </div>
    </div>
</header>

<!-- Script to create a live ticking clock (Manila time) -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function updateDateTime() {
            var now = new Date();
            var dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric', timeZone: 'Asia/Manila' });
            var timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila' });

            var dateEl = document.getElementById('current-date');
            var timeEl = document.getElementById('current-time');
            if (dateEl) dateEl.textContent = dateStr;
            if (timeEl) timeEl.textContent = timeStr;

            var liveDateEl = document.getElementById('liveDate');
            var liveTimeEl = document.getElementById('liveTime');
            if (liveDateEl) liveDateEl.textContent = dateStr;
            if (liveTimeEl) liveTimeEl.textContent = timeStr;
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);
    });
</script>