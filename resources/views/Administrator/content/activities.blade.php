<!-- Section 2: Activity Logs Content -->
<style>
    #activityLogsPagination .page-item.active .page-link {
        background-color: #F5F7DF !important;
        border-color: #F5F7DF !important;
        color: #443627 !important;
        font-weight: 700;
    }
    #activityLogsPagination .page-link {
        color: #F5F7DF;
        background-color: rgba(245, 247, 223, 0.15);
        border-color: rgba(245, 247, 223, 0.3);
    }
    #activityLogsPagination .page-link:hover {
        background-color: rgba(245, 247, 223, 0.3);
        color: #ffffff;
    }
    #activityLogsPagination .page-item.disabled .page-link {
        color: rgba(245, 247, 223, 0.4);
        background-color: transparent;
        border-color: rgba(245, 247, 223, 0.15);
        cursor: not-allowed;
    }
</style>

<!-- Activity Logs Box Container -->
<div class="card border rounded overflow-hidden shadow-sm" style="background-color: #F5F7DF;">

    <!-- Name Header with Bottom Border -->
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 text-white" style="background-color: #443627;">
        <div>
            <h5 class="fw-bold m-0 text-white">System Activity Logs</h5>
            <small class="text-white-50">Automatically recorded activities performed by Receptionist accounts and system events</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill px-3 py-2 fw-semibold border" style="background-color: rgba(245, 247, 223, 0.15); color: #F5F7DF; border-color: rgba(245, 247, 223, 0.3) !important;" id="activityLogsCountBadge">
                {{ isset($activityLogs) ? $activityLogs->total() : 0 }} records
            </span>
            <button type="button" class="btn btn-sm btn-outline-light" id="activityRefreshBtn" title="Refresh activities">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- 4 Interactions / Filters -->
    <div class="p-4">
        <form id="activityFilterForm" method="GET" action="{{ url('/admindashboard') }}" class="m-0">
            <input type="hidden" name="section" value="activities">

            <div class="row g-3 align-items-end">

                <!-- Filter 1: Who -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label fw-bold text-muted small mb-1">Who</label>
                    <select class="form-select" id="activityFilterWho" name="who">
                        <option value="all" {{ ($filterWho ?? 'all') == 'all' ? 'selected' : '' }}>Everyone</option>
                        <option value="receptionist" {{ ($filterWho ?? '') == 'receptionist' ? 'selected' : '' }}>All Receptionists</option>
                        <optgroup label="Individual Receptionists">
                            @foreach($staffs ?? [] as $s)
                                <option value="{{ $s->id }}" {{ ($filterWho ?? '') == (string) $s->id ? 'selected' : '' }}>
                                    Receptionist: {{ $s->first_name }} {{ $s->last_name }} ({{ $s->username }})
                                </option>
                            @endforeach
                        </optgroup>
                        <option value="system" {{ ($filterWho ?? '') == 'system' ? 'selected' : '' }}>System Generated (Overdues)</option>
                    </select>
                </div>

                <!-- Filter 2: Type -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label fw-bold text-muted small mb-1">Type</label>
                    <select class="form-select" id="activityFilterType" name="type">
                        <option value="all" {{ ($filterType ?? 'all') == 'all' ? 'selected' : '' }}>All activity</option>
                        <option value="payment" {{ ($filterType ?? '') == 'payment' ? 'selected' : '' }}>Payment</option>
                        <option value="cancellation" {{ ($filterType ?? '') == 'cancellation' ? 'selected' : '' }}>Cancellations</option>
                        <option value="booking" {{ ($filterType ?? '') == 'booking' ? 'selected' : '' }}>Bookings</option>
                        <option value="overdue" {{ ($filterType ?? '') == 'overdue' ? 'selected' : '' }}>Overdues</option>
                        <option value="login" {{ ($filterType ?? '') == 'login' ? 'selected' : '' }}>Login</option>
                    </select>
                </div>

                <!-- Filter 3: Date -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-muted small mb-0">Date</label>
                        <button type="button" class="btn btn-link p-0 text-decoration-none small text-muted {{ empty($filterDate) ? 'd-none' : '' }}" id="activityClearDateBtn" style="font-size: 0.75rem;">Clear</button>
                    </div>
                    <input type="date" class="form-control" id="activityFilterDate" name="date" value="{{ $filterDate ?? '' }}">
                </div>

                <!-- Filter 4: Search Bar -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-muted small mb-0">Search</label>
                        <button type="button" class="btn btn-link p-0 text-decoration-none small text-muted {{ empty($filterSearch) ? 'd-none' : '' }}" id="activityClearSearchBtn" style="font-size: 0.75rem;">Clear</button>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="activityFilterSearch" name="search" value="{{ $filterSearch ?? '' }}" placeholder="Room, Guest, Transaction">
                        <button class="btn btn-outline-secondary" type="submit" id="activitySearchBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Active Filter Indicators -->
    <div class="px-4 pb-2 d-flex flex-wrap align-items-center gap-2 {{ (!empty($filterWho) && $filterWho != 'all') || (!empty($filterType) && $filterType != 'all') || !empty($filterDate) || !empty($filterSearch) ? '' : 'd-none' }}" id="activityActiveFiltersWrap">
        <small class="fw-bold text-muted">Active Filters:</small>
        <span class="badge bg-light text-dark border" id="badgeFilterWho">Who: {{ $filterWho ?? 'Everyone' }}</span>
        <span class="badge bg-light text-dark border" id="badgeFilterType">Type: {{ $filterType ?? 'All' }}</span>
        <span class="badge bg-light text-dark border {{ empty($filterDate) ? 'd-none' : '' }}" id="badgeFilterDate">Date: {{ $filterDate ?? '' }}</span>
        <span class="badge bg-light text-dark border {{ empty($filterSearch) ? 'd-none' : '' }}" id="badgeFilterSearch">Search: "{{ $filterSearch ?? '' }}"</span>
        <a href="{{ url('/admindashboard?section=activities') }}" class="btn btn-sm btn-link text-decoration-none text-danger p-0 ms-2 small" id="activityResetAllFilters">Reset filters</a>
    </div>

    <!-- Space for the actual logs to populate below the filters -->
    <div class="p-4 pt-0">
        <div class="table-responsive border rounded" style="background-color: #F5F7DF;">
            <table class="table table-hover align-middle mb-0" id="activityLogsTable" style="background-color: #F5F7DF;">
                <thead class="border-bottom" style="background-color: #443627; color: #ffffff;">
                    <tr>
                        <th class="py-3 px-3 fw-bold text-white small text-uppercase" style="width: 14%; background-color: #443627; color: #ffffff;">Type</th>
                        <th class="py-3 px-3 fw-bold text-white small text-uppercase" style="width: 22%; background-color: #443627; color: #ffffff;">Responsible User</th>
                        <th class="py-3 px-3 fw-bold text-white small text-uppercase" style="width: 32%; background-color: #443627; color: #ffffff;">Activity Details</th>
                        <th class="py-3 px-3 fw-bold text-white small text-uppercase" style="width: 16%; background-color: #443627; color: #ffffff;">Financials / Method</th>
                        <th class="py-3 px-3 fw-bold text-white small text-uppercase text-end text-white" style="width: 16%; background-color: #443627; color: #ffffff;">Date & Time</th>
                    </tr>
                </thead>
                <tbody id="activityLogsTableBody">
                    @forelse($activityLogs ?? [] as $log)
                        @php
                            $badge = $log->type_badge;
                            $isSystem = is_null($log->staff_id);
                        @endphp
                        <tr class="activity-row" data-id="{{ $log->id }}" data-type="{{ $log->activity_type }}">
                            <!-- Column 1: Type -->
                            <td class="py-3 px-3">
                                <span class="badge {{ $badge['class'] }} px-2.5 py-1.5 rounded-pill fw-semibold" style="font-size: 0.78rem;">
                                    {{ $badge['label'] }}
                                </span>
                                <div class="text-muted small mt-1 text-capitalize" style="font-size: 0.75rem;">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </div>
                            </td>

                            <!-- Column 2: Responsible User (Receptionist or System Generated) -->
                            <td class="py-3 px-3">
                                @if($isSystem)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                                                <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1-.52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark">System Generated</span>
                                            <div class="text-muted small" style="font-size: 0.75rem;">Automated Check</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background-color: #6A4435; font-size: 0.85rem;">
                                            {{ strtoupper(substr($log->staff->first_name ?? 'R', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark">{{ $log->actor_name }}</span>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <span class="badge bg-light text-secondary border">Receptionist #{{ $log->staff_id }}</span>
                                                <span class="ms-1">{{ '@' . ($log->staff->username ?? 'staff') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <!-- Column 3: Activity & Subject Details -->
                            <td class="py-3 px-3">
                                @if($log->activity_type === 'login')
                                    <div class="fw-semibold text-dark">Receptionist Login Session</div>
                                    <div class="text-muted small">
                                        User <span class="fw-semibold text-dark">@ {{ $log->details['username'] ?? $log->actor_username }}</span> logged in
                                        @if(!empty($log->details['ip_address']))
                                            <span class="text-secondary">(IP: {{ $log->details['ip_address'] }})</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="fw-semibold text-dark">
                                        @if(!empty($log->guest_name))
                                            {{ $log->guest_name }}
                                        @else
                                            <span class="text-muted">No Guest Specified</span>
                                        @endif
                                        @if(!empty($log->room_number))
                                            &bull; <span class="text-secondary fw-normal">{{ $log->room_number }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.82rem;">
                                        {{ $log->description }}
                                    </div>
                                    @if(!empty($log->reference_number))
                                        <div class="mt-1">
                                            <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">
                                                Ref: {{ $log->reference_number }}
                                            </span>
                                            @if(!empty($log->booking_type))
                                                <span class="badge bg-light text-muted border text-capitalize" style="font-size: 0.72rem;">
                                                    {{ str_replace('_', ' ', $log->booking_type) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Column 4: Financials / Method -->
                            <td class="py-3 px-3">
                                @if($log->activity_type === 'login')
                                    <span class="text-muted small">&mdash;</span>
                                @elseif($log->amount !== null && (float) $log->amount > 0)
                                    <div class="fw-bold text-success">
                                        ₱{{ number_format((float) $log->amount, 2) }}
                                    </div>
                                    <div class="text-muted small text-capitalize" style="font-size: 0.75rem;">
                                        {{ $log->payment_method ? str_replace('_', ' ', $log->payment_method) : 'N/A' }}
                                    </div>
                                @elseif($log->activity_type === 'overdue')
                                    <div class="text-danger fw-semibold small">
                                        Overdue Room Stay
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        Pending Payment
                                    </div>
                                @else
                                    <span class="text-muted small">None / ₱0.00</span>
                                @endif
                            </td>

                            <!-- Column 5: Date & Time -->
                            <td class="py-3 px-3 text-end text-nowrap">
                                <div class="fw-bold text-dark small">{{ $log->formatted_date }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $log->formatted_time }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr id="activityLogsEmptyRow">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#adb5bd" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                                        <path d="M4 4h8v1H4zm0 2h8v1H4zm0 2h8v1H4zm0 2h5v1H4z"/>
                                    </svg>
                                </div>
                                <h6 class="fw-bold text-secondary mb-1">No activity logs found</h6>
                                <p class="small text-muted mb-0">No records match the current filter selection.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="card-footer border-top d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 p-3 rounded" id="activityLogsPagination" style="background-color: #443627; color: #F5F7DF;">
            <div class="small" id="activityLogsPaginationInfo" style="color: #F5F7DF;">
                @if(isset($activityLogs) && $activityLogs->total() > 0)
                    Showing {{ $activityLogs->firstItem() }} to {{ $activityLogs->lastItem() }} of {{ $activityLogs->total() }} entries
                @endif
            </div>
            <div id="activityLogsPaginationLinks">
                @if(isset($activityLogs) && $activityLogs->total() > 0)
                    {{ $activityLogs->links('Receptionist.vendor.pagination.bootstrap-5') }}
                @endif
            </div>
        </div>
    </div>

</div>

<!-- Real-time Interactive JavaScript for Activities Filtering -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var whoSelect = document.getElementById('activityFilterWho');
        var typeSelect = document.getElementById('activityFilterType');
        var dateInput = document.getElementById('activityFilterDate');
        var searchInput = document.getElementById('activityFilterSearch');
        var clearDateBtn = document.getElementById('activityClearDateBtn');
        var clearSearchBtn = document.getElementById('activityClearSearchBtn');
        var refreshBtn = document.getElementById('activityRefreshBtn');
        var filterForm = document.getElementById('activityFilterForm');
        var resetAllBtn = document.getElementById('activityResetAllFilters');
        var tableBody = document.getElementById('activityLogsTableBody');
        var countBadge = document.getElementById('activityLogsCountBadge');
        var paginationInfo = document.getElementById('activityLogsPaginationInfo');
        var paginationLinks = document.getElementById('activityLogsPaginationLinks');
        var paginationWrap = document.getElementById('activityLogsPagination');
        var activeFiltersWrap = document.getElementById('activityActiveFiltersWrap');
        var badgeWho = document.getElementById('badgeFilterWho');
        var badgeType = document.getElementById('badgeFilterType');
        var badgeDate = document.getElementById('badgeFilterDate');
        var badgeSearch = document.getElementById('badgeFilterSearch');

        var searchTimeout = null;
        var urlParams = new URLSearchParams(window.location.search);
        var initialPage = parseInt(urlParams.get('page'), 10);
        var currentPage = (!isNaN(initialPage) && initialPage > 0) ? initialPage : {{ isset($activityLogs) ? $activityLogs->currentPage() : 1 }};

        function fetchActivities(page, updateHistory) {
            if (typeof updateHistory === 'undefined') updateHistory = true;
            if (page) currentPage = parseInt(page, 10);

            var params = new URLSearchParams();
            params.set('who', whoSelect ? whoSelect.value : 'all');
            params.set('type', typeSelect ? typeSelect.value : 'all');
            if (dateInput && dateInput.value) params.set('date', dateInput.value);
            if (searchInput && searchInput.value.trim()) params.set('search', searchInput.value.trim());
            params.set('page', currentPage);

            // Update browser URL query string without reloading
            if (updateHistory) {
                var newUrl = window.location.pathname + '?section=activities&' + params.toString();
                window.history.replaceState({ path: newUrl }, '', newUrl);
            }

            // Toggle clear buttons
            if (clearDateBtn) {
                if (dateInput && dateInput.value) clearDateBtn.classList.remove('d-none');
                else clearDateBtn.classList.add('d-none');
            }
            if (clearSearchBtn) {
                if (searchInput && searchInput.value.trim()) clearSearchBtn.classList.remove('d-none');
                else clearSearchBtn.classList.add('d-none');
            }

            // Update active filters strip
            var hasFilters = (whoSelect && whoSelect.value !== 'all') ||
                             (typeSelect && typeSelect.value !== 'all') ||
                             (dateInput && dateInput.value) ||
                             (searchInput && searchInput.value.trim());

            if (activeFiltersWrap) {
                if (hasFilters) activeFiltersWrap.classList.remove('d-none');
                else activeFiltersWrap.classList.add('d-none');
            }
            if (badgeWho && whoSelect) {
                badgeWho.textContent = 'Who: ' + whoSelect.options[whoSelect.selectedIndex].text;
            }
            if (badgeType && typeSelect) {
                badgeType.textContent = 'Type: ' + typeSelect.options[typeSelect.selectedIndex].text;
            }
            if (badgeDate) {
                if (dateInput && dateInput.value) {
                    badgeDate.textContent = 'Date: ' + dateInput.value;
                    badgeDate.classList.remove('d-none');
                } else {
                    badgeDate.classList.add('d-none');
                }
            }
            if (badgeSearch) {
                if (searchInput && searchInput.value.trim()) {
                    badgeSearch.textContent = 'Search: "' + searchInput.value.trim() + '"';
                    badgeSearch.classList.remove('d-none');
                } else {
                    badgeSearch.classList.add('d-none');
                }
            }

            // Fetch data from API endpoint
            fetch('/api/admin/activities?' + params.toString())
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res && res.status === 'success') {
                        renderLogs(res.data);
                        if (countBadge) countBadge.textContent = res.total + ' records';
                        if (paginationInfo) {
                            if (res.total > 0) {
                                var from = res.from ? res.from : ((res.current_page - 1) * res.per_page + 1);
                                var to = res.to ? res.to : Math.min(res.current_page * res.per_page, res.total);
                                paginationInfo.textContent = 'Showing ' + from + ' to ' + to + ' of ' + res.total + ' entries';
                            } else {
                                paginationInfo.textContent = '';
                            }
                        }
                        renderPagination(res);
                    }
                })
                .catch(function (err) {
                    console.error('Error fetching activities:', err);
                });
        }

        function renderPagination(res) {
            if (!paginationLinks) return;

            var curr = parseInt(res.current_page, 10) || 1;
            var last = parseInt(res.last_page, 10) || 1;
            var total = parseInt(res.total, 10) || 0;

            if (total === 0) {
                paginationLinks.innerHTML = '';
                return;
            }

            var html = '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

            // Previous Button
            if (curr <= 1) {
                html += '<li class="page-item disabled" aria-disabled="true"><span class="page-link">Previous</span></li>';
            } else {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (curr - 1) + '" rel="prev">Previous</a></li>';
            }

            // Page Numbers
            var pages = [];
            if (last <= 7) {
                for (var i = 1; i <= last; i++) {
                    pages.push(i);
                }
            } else {
                pages.push(1);

                var start = Math.max(2, curr - 1);
                var end = Math.min(last - 1, curr + 1);

                if (curr <= 3) {
                    start = 2;
                    end = 4;
                } else if (curr >= last - 2) {
                    start = last - 3;
                    end = last - 1;
                }

                if (start > 2) {
                    pages.push('...');
                }

                for (var j = start; j <= end; j++) {
                    pages.push(j);
                }

                if (end < last - 1) {
                    pages.push('...');
                }

                pages.push(last);
            }

            pages.forEach(function (p) {
                if (p === '...') {
                    html += '<li class="page-item disabled" aria-disabled="true"><span class="page-link">&hellip;</span></li>';
                } else if (p === curr) {
                    html += '<li class="page-item active" aria-current="page"><span class="page-link">' + p + '</span></li>';
                } else {
                    html += '<li class="page-item"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                }
            });

            // Next Button
            if (curr >= last) {
                html += '<li class="page-item disabled" aria-disabled="true"><span class="page-link">Next</span></li>';
            } else {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (curr + 1) + '" rel="next">Next</a></li>';
            }

            html += '</ul></nav>';
            paginationLinks.innerHTML = html;
        }

        function renderLogs(logs) {
            if (!tableBody) return;

            if (!logs || logs.length === 0) {
                tableBody.innerHTML = '<tr id="activityLogsEmptyRow">' +
                    '<td colspan="5" class="text-center py-5 text-muted">' +
                        '<div class="mb-2">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#adb5bd" viewBox="0 0 16 16">' +
                                '<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>' +
                                '<path d="M4 4h8v1H4zm0 2h8v1H4zm0 2h8v1H4zm0 2h5v1H4z"/>' +
                            '</svg>' +
                        '</div>' +
                        '<h6 class="fw-bold text-secondary mb-1">No activity logs found</h6>' +
                        '<p class="small text-muted mb-0">No records match the current filter selection.</p>' +
                    '</td>' +
                '</tr>';
                return;
            }

            var html = '';
            logs.forEach(function (log) {
                var badgeClass = (log.type_badge && log.type_badge.class) ? log.type_badge.class : 'bg-dark text-white';
                var badgeLabel = (log.type_badge && log.type_badge.label) ? log.type_badge.label : log.activity_type;
                var actionLabel = (log.action || '').replace(/_/g, ' ');

                // User Column
                var userHtml = '';
                if (log.is_system) {
                    userHtml = '<div class="d-flex align-items-center gap-2">' +
                        '<div class="rounded-circle bg-warning bg-opacity-25 text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">' +
                                '<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>' +
                                '<path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div>' +
                            '<span class="fw-bold text-dark">System Generated</span>' +
                            '<div class="text-muted small" style="font-size: 0.75rem;">Automated Check</div>' +
                        '</div>' +
                    '</div>';
                } else {
                    var initial = (log.actor_name || 'R').charAt(0).toUpperCase();
                    userHtml = '<div class="d-flex align-items-center gap-2">' +
                        '<div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background-color: #6A4435; font-size: 0.85rem;">' +
                            initial +
                        '</div>' +
                        '<div>' +
                            '<span class="fw-bold text-dark">' + (log.actor_name || 'Receptionist') + '</span>' +
                            '<div class="text-muted small" style="font-size: 0.75rem;">' +
                                '<span class="badge bg-light text-secondary border">Receptionist #' + (log.staff_id || '') + '</span>' +
                                '<span class="ms-1">@' + (log.actor_username || 'staff') + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }

                // Details Column
                var detailsHtml = '';
                if (log.activity_type === 'login') {
                    detailsHtml = '<div class="fw-semibold text-dark">Receptionist Login Session</div>' +
                        '<div class="text-muted small">User <span class="fw-semibold text-dark">@' + (log.actor_username || '') + '</span> logged in</div>';
                } else {
                    detailsHtml = '<div class="fw-semibold text-dark">' +
                        (log.guest_name ? log.guest_name : '<span class="text-muted">No Guest Specified</span>') +
                        (log.room_number ? ' &bull; <span class="text-secondary fw-normal">' + log.room_number + '</span>' : '') +
                    '</div>' +
                    '<div class="text-muted small" style="font-size: 0.82rem;">' + (log.description || '') + '</div>';

                    if (log.reference_number || log.booking_type) {
                        detailsHtml += '<div class="mt-1">';
                        if (log.reference_number) {
                            detailsHtml += '<span class="badge bg-light text-muted border me-1" style="font-size: 0.72rem;">Ref: ' + log.reference_number + '</span>';
                        }
                        if (log.booking_type) {
                            detailsHtml += '<span class="badge bg-light text-muted border text-capitalize" style="font-size: 0.72rem;">' + log.booking_type.replace(/_/g, ' ') + '</span>';
                        }
                        detailsHtml += '</div>';
                    }
                }

                // Financials Column
                var finHtml = '';
                if (log.activity_type === 'login') {
                    finHtml = '<span class="text-muted small">&mdash;</span>';
                } else if (log.amount !== null && parseFloat(log.amount) > 0) {
                    finHtml = '<div class="fw-bold text-success">' + (log.amount_formatted || '₱' + parseFloat(log.amount).toFixed(2)) + '</div>' +
                        '<div class="text-muted small text-capitalize" style="font-size: 0.75rem;">' + (log.payment_method ? log.payment_method.replace(/_/g, ' ') : 'N/A') + '</div>';
                } else if (log.activity_type === 'overdue') {
                    finHtml = '<div class="text-danger fw-semibold small">Overdue Room Stay</div><div class="text-muted small" style="font-size: 0.75rem;">Pending Payment</div>';
                } else {
                    finHtml = '<span class="text-muted small">None / ₱0.00</span>';
                }

                html += '<tr class="activity-row" data-id="' + log.id + '" data-type="' + log.activity_type + '">' +
                    '<td class="py-3 px-3">' +
                        '<span class="badge ' + badgeClass + ' px-2.5 py-1.5 rounded-pill fw-semibold" style="font-size: 0.78rem;">' + badgeLabel + '</span>' +
                        '<div class="text-muted small mt-1 text-capitalize" style="font-size: 0.75rem;">' + actionLabel + '</div>' +
                    '</td>' +
                    '<td class="py-3 px-3">' + userHtml + '</td>' +
                    '<td class="py-3 px-3">' + detailsHtml + '</td>' +
                    '<td class="py-3 px-3">' + finHtml + '</td>' +
                    '<td class="py-3 px-3 text-end text-nowrap">' +
                        '<div class="fw-bold text-dark small">' + log.formatted_date + '</div>' +
                        '<div class="text-muted small" style="font-size: 0.75rem;">' + log.formatted_time + '</div>' +
                    '</td>' +
                '</tr>';
            });

            tableBody.innerHTML = html;
        }

        // Attach event listeners for filters - all automatically reset to page 1
        if (whoSelect) {
            whoSelect.addEventListener('change', function () {
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (clearDateBtn && dateInput) {
            clearDateBtn.addEventListener('click', function (e) {
                e.preventDefault();
                dateInput.value = '';
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    currentPage = 1;
                    fetchActivities(1);
                }, 300);
            });
        }

        if (clearSearchBtn && searchInput) {
            clearSearchBtn.addEventListener('click', function (e) {
                e.preventDefault();
                searchInput.value = '';
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (resetAllBtn) {
            resetAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (whoSelect) whoSelect.value = 'all';
                if (typeSelect) typeSelect.value = 'all';
                if (dateInput) dateInput.value = '';
                if (searchInput) searchInput.value = '';
                currentPage = 1;
                fetchActivities(1);
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                fetchActivities(currentPage);
            });
        }

        // Handle pagination link clicks dynamically (Previous, Next, and page numbers)
        document.addEventListener('click', function (e) {
            var target = e.target.closest('#activityLogsPagination .page-link');
            if (!target) return;

            var pageItem = target.closest('.page-item');
            if (pageItem && (pageItem.classList.contains('disabled') || pageItem.classList.contains('active'))) {
                e.preventDefault();
                return;
            }

            var targetPage = null;
            if (target.dataset && target.dataset.page) {
                targetPage = parseInt(target.dataset.page, 10);
            } else if (target.getAttribute('href')) {
                var href = target.getAttribute('href');
                var match = href.match(/[?&]page=(\d+)/);
                if (match) {
                    targetPage = parseInt(match[1], 10);
                }
            }

            if (targetPage && !isNaN(targetPage) && targetPage > 0 && targetPage !== currentPage) {
                e.preventDefault();
                fetchActivities(targetPage);

                var tableCard = document.getElementById('activityLogsTable');
                if (tableCard && typeof tableCard.scrollIntoView === 'function') {
                    var rect = tableCard.getBoundingClientRect();
                    if (rect.top < 0) {
                        tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            } else if (target.getAttribute('href') === '#' || target.dataset.page) {
                e.preventDefault();
            }
        });

        // Periodic background poll every 30 seconds to catch newly recorded activities
        setInterval(function () {
            fetchActivities(currentPage, false);
        }, 30000);

        // Sync on popstate (browser back/forward navigation)
        window.addEventListener('popstate', function () {
            var popParams = new URLSearchParams(window.location.search);
            var page = parseInt(popParams.get('page'), 10) || 1;
            var who = popParams.get('who') || 'all';
            var type = popParams.get('type') || 'all';
            var date = popParams.get('date') || '';
            var search = popParams.get('search') || '';

            if (whoSelect) whoSelect.value = who;
            if (typeSelect) typeSelect.value = type;
            if (dateInput) dateInput.value = date;
            if (searchInput) searchInput.value = search;

            fetchActivities(page, false);
        });
    });
</script>
