<!-- Topbar -->
@php
    $displayAdminUsername = $adminUsername ?? ($adminUser->username ?? (session('admin_id') ? \App\Models\User::find(session('admin_id'))?->username : null) ?? \App\Models\User::first()?->username ?? session('user_id') ?? 'Administrator');
@endphp
<header class="bg-white border-bottom shadow-sm px-3 px-sm-4 py-3 d-flex justify-content-between align-items-center" style="background-color: #AEAC78 !important; border-bottom: 1px solid rgba(68, 54, 39, 0.15) !important;">
    <div class="d-flex align-items-center gap-2">
        <!-- Hamburger Menu Button for mobile -->
        <button class="btn border rounded shadow-sm d-md-none d-flex align-items-center justify-content-center p-2" 
                type="button" 
                data-bs-toggle="offcanvas" 
                data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu"
                aria-label="Toggle navigation"
                id="openSidebar"
                style="width: 38px; height: 38px; background-color: #443627 !important; border-color: #443627 !important; color: #F5F7DF !important;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#F5F7DF" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>
        </button>

        <!-- Welcoming Greeting -->
        <h4 class="mb-0 fw-bold text-dark text-truncate" style="font-size: clamp(1rem, 1.3vw, 1.45rem); letter-spacing: -0.01em; color: #443627 !important;">
            Good day, {{ $displayAdminUsername }}!
        </h4>
    </div>

    <!-- Header Action Buttons -->
    <div class="d-flex align-items-center gap-2 gap-sm-3">
        <!-- Date and Time (live, Manila time) -->
        <div class="d-none d-sm-flex border px-3 py-2 rounded shadow-sm fw-bold small" style="background-color: #443627; border: 1px solid #443627; color: #F5F7DF;">
            <span id="current-date" style="color: #F5F7DF;" data-format="{{ $dateFormat ?? 'M d, Y' }}">{{ \App\Services\SystemFormatService::formatDate(now()) }}</span>
            <span id="liveDate" class="d-none">{{ \App\Services\SystemFormatService::formatDate(now()) }}</span>
        </div>
        <div class="border px-2 px-sm-3 py-2 rounded shadow-sm fw-bold small" style="background-color: #443627; border: 1px solid #443627; color: #F5F7DF;">
            <span id="current-time" style="color: #F5F7DF;" data-format="{{ $timeFormat ?? 'h:i A' }}">{{ \App\Services\SystemFormatService::formatTime(now()) }}</span>
            <span id="liveTime" class="d-none">{{ \App\Services\SystemFormatService::formatTime(now()) }}</span>
        </div>
    </div>
</header>

<!-- Script to create a live ticking clock (Manila time) with dynamic format -->
<script>
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
            var liveDateEl = document.getElementById('liveDate');
            var liveTimeEl = document.getElementById('liveTime');

            var dateFormat = (dateEl && dateEl.getAttribute('data-format')) || '{{ $dateFormat ?? "M d, Y" }}';
            var timeFormat = (timeEl && timeEl.getAttribute('data-format')) || '{{ $timeFormat ?? "h:i A" }}';

            function updateDateTime() {
                var now = new Date();
                var dateStr = formatPhpDate(now, dateFormat);
                var timeStr = formatPhpDate(now, timeFormat);

                if (dateEl) dateEl.textContent = dateStr;
                if (timeEl) timeEl.textContent = timeStr;
                if (liveDateEl) liveDateEl.textContent = dateStr;
                if (liveTimeEl) liveTimeEl.textContent = timeStr;
            }

            function syncSettings() {
                fetch('{{ route("api.system.settings") }}')
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.date_format) dateFormat = data.date_format;
                        if (data && data.time_format) timeFormat = data.time_format;
                        if (dateEl) dateEl.setAttribute('data-format', dateFormat);
                        if (timeEl) timeEl.setAttribute('data-format', timeFormat);
                        updateDateTime();
                    })
                    .catch(function(e) {});
            }

            updateDateTime();
            setInterval(updateDateTime, 1000);
            setInterval(syncSettings, 30000);
        });
    })();
</script>