<link rel="stylesheet" href="{{ asset('CSS/Administrator/staff.css') }}">

<div class="card border rounded p-3 p-sm-4 h-100 shadow-sm">

    <!-- Top Header Area -->
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start align-items-sm-center mb-3 mb-sm-4">
        <h4 class="fw-bold m-0 fs-5 fs-sm-4 text-dark pt-1 pt-sm-0">Manage Accounts</h4>

        <!-- Action Buttons Container: Add Staff at top; Edit and Delete directly below on mobile -->
        <div class="d-flex flex-column align-items-end gap-0 ms-auto">
            <!-- Add Staff Button 1-->
            <button class="btn btn-dark fw-bold btn-sm py-2 px-2.5 px-sm-3" data-bs-toggle="modal"
                data-bs-target="#addStaffModal">+ Add Receptionist</button>

            <!-- Dedicated Mobile Edit & Delete Buttons positioned directly below Add Staff -->
            <div class="d-flex d-md-none align-items-center gap-2 mt-2">
                <button type="button" class="btn btn-outline-dark btn-sm py-1.5 px-2.5 fw-semibold"
                    id="staffMobileEditBtn" title="Edit staff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor"
                        viewBox="0 0 16 16" aria-hidden="true">
                        <path
                            d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
                    </svg>
                </button>
                <button type="button" class="btn btn-danger btn-sm py-1.5 px-2.5 fw-semibold" id="staffMobileDeleteBtn"
                    title="Delete staff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor"
                        viewBox="0 0 16 16" aria-hidden="true">
                        <path
                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                        <path
                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Selection Notice Banner -->
    <div id="staffMobileSelectionNotice"
        class="alert alert-warning alert-dismissible fade show py-2 px-3 small mb-3 d-none d-md-none shadow-sm"
        role="alert">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                class="bi bi-info-circle flex-shrink-0" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                <path
                    d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
            </svg>
            <span id="staffMobileSelectionNoticeText" class="fw-semibold">Please select a staff member by clicking its
                table row to proceed.</span>
        </div>
        <button type="button" class="btn-close btn-close-sm p-2" id="closeStaffMobileNoticeBtn"
            aria-label="Close"></button>
    </div>

    <!-- Accounts Box Container -->
    <div class="border rounded overflow-hidden flex-grow-1 bg-white staff-table-wrapper">
        <div class="table-responsive w-100">
            <table class="table table-hover mb-0 align-middle staff-table">
                <colgroup>
                    <col class="col-staff-employee">
                    <col class="col-staff-email">
                    <col class="col-staff-contact">
                    <col class="col-staff-status">
                    <col class="col-staff-lastactive">
                    <col class="col-staff-actions d-none d-md-table-cell">
                </colgroup>
                <thead class="table-light border-bottom">
                    <tr>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Employee</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Email</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Contact Number</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Status</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Last active</th>
                        <!-- Desktop Actions Header (hidden on mobile) -->
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-end d-none d-md-table-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffs as $staff)
                        <tr class="staff-row" data-id="{{ $staff->id }}"
                            data-name="{{ $staff->first_name }} {{ $staff->last_name }}">
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 fw-bold staff-cell-employee">
                                {{ $staff->first_name }} {{ $staff->last_name }}</td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 staff-cell-email">{{ $staff->email }}</td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 staff-cell-contact">{{ $staff->contact_number }}
                            </td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 staff-cell-status">
                                <span class="badge bg-success rounded-pill">Active</span>
                            </td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-muted text-nowrap staff-cell-lastactive">
                                @php
                                    $latestAct = $staff->latestActivity;
                                @endphp
                                @if($latestAct)
                                    <span title="{{ $latestAct->formatted_datetime }}">{{ $latestAct->formatted_date }} {{ $latestAct->formatted_time }}</span>
                                @else
                                    <span>Just now</span>
                                @endif
                            </td>
                            <!-- Desktop Actions Column (hidden on mobile) -->
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-end text-nowrap d-none d-md-table-cell">
                                <div class="d-inline-flex align-items-center justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-dark" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#editStaffModal{{ $staff->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
                                            viewBox="0 0 16 16" aria-hidden="true">
                                            <path
                                                d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
                                        </svg>
                                    </button>

                                    <form method="POST" action="/staff/{{ $staff->id }}/delete"
                                        id="staffDeleteForm{{ $staff->id }}" class="d-inline mb-0">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="section" value="accman">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this staff member?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path
                                                    d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                                <path
                                                    d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Include the Edit Modal for this specific staff member -->
                        @include('Administrator.modals.editaccstaff', ['staff' => $staff])

                    @empty
                        <tr>
                            <!-- Colspan for empty message (6 columns) -->
                            <td colspan="6" class="text-center py-5 text-muted">
                                <!-- Add Staff Button 1-->
                                <button class="btn btn-dark fw-bold btn-sm py-2 px-2.5 px-sm-3" data-bs-toggle="modal"
                                    data-bs-target="#addStaffModal">+ Add Receptionist</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Administrator Credentials Section (Immediately after Receptionist Credentials) -->
    <div class="card p-4 border rounded shadow-sm mt-4 bg-white" id="adminCredentialsSection">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-1" style="color: #443627;">Administrator Credentials</h5>
                <p class="text-muted small mb-0">Manage and update the Administrator username and login password.</p>
            </div>
            <span class="badge text-bg-light border text-secondary fw-semibold">Administrator Account</span>
        </div>

        @if (session('success') && (request('section') === 'accman' || !request('section')))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any() && (request('section') === 'accman' || old('section') === 'accman'))
            <div class="alert alert-danger mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.credentials.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="accman">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="admin_acc_username" class="form-label fw-semibold text-secondary small text-uppercase">Username <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control"
                           id="admin_acc_username"
                           name="username"
                           value="{{ old('username', $adminUser->username ?? auth()->user()->username ?? 'admin') }}"
                           required>
                    <small class="text-muted">Username used to log in to the Administrator dashboard.</small>
                </div>

                <div class="col-md-4">
                    <label for="admin_acc_password" class="form-label fw-semibold text-secondary small text-uppercase">New Password</label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="admin_acc_password"
                               name="password"
                               placeholder="Leave blank to keep current">
                        <button class="btn btn-outline-secondary toggle-admin-password" type="button" data-target="admin_acc_password" title="Toggle password visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 6 characters. Leave empty to preserve current password.</small>
                </div>

                <div class="col-md-4">
                    <label for="admin_acc_password_confirmation" class="form-label fw-semibold text-secondary small text-uppercase">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="admin_acc_password_confirmation"
                               name="password_confirmation"
                               placeholder="Re-enter new password">
                        <button class="btn btn-outline-secondary toggle-admin-password" type="button" data-target="admin_acc_password_confirmation" title="Toggle password visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                        </button>
                    </div>
                    <small class="text-muted">Must match the new password entered above.</small>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn text-white fw-semibold px-4 py-2" style="background-color: #443627; border-color: #443627;">
                    Save Administrator Credentials
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-admin-password').forEach(function(btn) {
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

<!-- Included ONLY ONCE at the very bottom -->
@include('Administrator.modals.addacc')