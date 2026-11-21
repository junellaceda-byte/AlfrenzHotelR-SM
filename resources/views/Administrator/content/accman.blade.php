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
                                Just now</td>
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

</div>

<!-- Included ONLY ONCE at the very bottom -->
@include('Administrator.modals.addacc')