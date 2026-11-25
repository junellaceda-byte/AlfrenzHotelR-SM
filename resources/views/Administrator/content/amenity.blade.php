<link rel="stylesheet" href="{{ asset('CSS/Administrator/amenity.css') }}">

<div class="card border rounded p-3 p-sm-4 h-100 shadow-sm d-flex flex-column" style="background-color: #F5F7DF;">
    
    <!-- Top Header Area -->
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start align-items-sm-center mb-3 mb-sm-4">
        <h4 class="fw-bold m-0 fs-5 fs-sm-4 text-dark pt-1 pt-sm-0">Available Amenities</h4>

        <!-- Action Buttons Container: Add Amenity at top; Edit and Delete directly below on mobile -->
        <div class="d-flex flex-column align-items-end gap-0 ms-auto">
            <!-- Add Amenity Button -->
            <button class="btn btn-dark fw-bold btn-sm py-2 px-2.5 px-sm-3" data-bs-toggle="modal" data-bs-target="#addAmenityModal">+ Add Amenity</button>
            
            <!-- Dedicated Mobile Edit & Delete Buttons positioned directly below Add Amenity (no ellipsis menu) -->
            <div class="d-flex d-md-none align-items-center gap-2 mt-2">
                <button type="button" class="btn btn-outline-dark btn-sm py-1.5 px-2.5 fw-semibold" id="mobileEditBtn" title="Edit amenity">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                </button>
                <button type="button" class="btn btn-danger btn-sm py-1.5 px-2.5 fw-semibold" id="mobileDeleteBtn" title="Delete amenity">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Mobile Selection Notice Banner -->
    <div id="mobileSelectionNotice" class="alert alert-warning alert-dismissible fade show py-2 px-3 small mb-3 d-none d-md-none shadow-sm" role="alert">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle flex-shrink-0" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
            </svg>
            <span id="mobileSelectionNoticeText" class="fw-semibold">Please select an amenity by clicking its table row to proceed.</span>
        </div>
        <button type="button" class="btn-close btn-close-sm p-2" id="closeMobileNoticeBtn" aria-label="Close"></button>
    </div>

    <!-- The Box Container for the Amenities -->
    <div class="border rounded overflow-hidden flex-grow-1 amenities-table-wrapper" style="background-color: #F5F7DF;">
        <div class="table-responsive w-100" style="background-color: #F5F7DF;">
            <table class="table table-hover mb-0 align-middle amenities-table">
                <colgroup>
                    <col class="col-amenity">
                    <col class="col-price">
                    <col class="col-charged">
                    <col class="col-offered">
                    <col class="col-actions">
                </colgroup>
                <thead class="border-bottom" style="background-color: #443627; color: #ffffff;">
                    <tr>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start text-white" style="background-color: #443627; color: #ffffff;">Amenity</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start text-white" style="background-color: #443627; color: #ffffff;">Price</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start text-white" style="background-color: #443627; color: #ffffff;">Description</th>
                        <th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-center text-white" style="background-color: #443627; color: #ffffff;">Status</th>
                        <!-- Desktop Actions Header (hidden on mobile, visible on desktop) -->
                        <th style="background-color: #443627; color: #ffffff;" class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-end text-white d-none d-md-table-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($amenities as $amenity)
                        <tr class="amenity-row" data-id="{{ $amenity->id }}" data-name="{{ $amenity->name }}" style="background-color: #F5F7DF;">
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start" style="background-color: #F5F7DF;">
                                <div class="d-inline-flex align-items-center" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $amenity->name }}">
                                    <img src="{{ $amenity->image_url }}" alt="{{ $amenity->name }}" class="amenity-cube-thumb" loading="lazy">
                                </div>
                            </td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start text-nowrap" style="background-color: #F5F7DF;">
                                <span class="amenity-price fw-semibold" style="color: #6A4435;">₱{{ number_format($amenity->price, 2) }}</span>
                            </td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start text-nowrap" style="background-color: #F5F7DF;">
                                <span class="amenity-charged text-secondary">{{ $amenity->charged }}</span>
                            </td>
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-center text-nowrap" style="background-color: #F5F7DF;">
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="form-check form-switch mb-0 d-inline-block">
                                        <input class="form-check-input amenity-toggle-switch" type="checkbox" role="switch" data-id="{{ $amenity->id }}" {{ $amenity->offered ? 'checked' : '' }} disabled tabindex="-1" aria-disabled="true" style="cursor: default;" title="{{ $amenity->offered ? 'Offered' : 'Not Offered' }}">
                                    </div>
                                </div>
                            </td>
                            <!-- Desktop Actions Column (hidden on mobile, visible on desktop) -->
                            <td class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-end text-nowrap d-none d-md-table-cell" style="background-color: #F5F7DF;">
                                <div class="d-inline-flex align-items-center justify-content-end gap-1 gap-sm-2">
                                    <button type="button" class="btn btn-sm btn-outline-dark" title="Edit" data-bs-toggle="modal" data-bs-target="#editAmenityModal{{ $amenity->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                                    </button>
                                    
                                    <form method="POST" action="/amenities/{{ $amenity->id }}/delete" id="deleteForm{{ $amenity->id }}" class="d-inline mb-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this amenity?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Include the Edit Modal for this specific amenity -->
                        @include('Administrator.modals.editamenity', ['amenity' => $amenity])
                        
                    @empty
                        <tr style="background-color: #F5F7DF;">
                            <!-- Colspan for empty message -->
                            <td colspan="5" class="text-center py-5 text-muted" style="background-color: #F5F7DF;">
                                There is no Amenity here Add Now
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@include('Administrator.modals.addamenity')

<script>
window.previewEditAmenityImage = function(event, id) {
    const input = event.target;
    const preview = document.getElementById('editAmenityPreview' + id);
    if (input.files && input.files[0] && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
};

(function() {
    // Initialize tooltips for amenity cube images
    try {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            if (window.bootstrap && bootstrap.Tooltip) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            }
        });
    } catch (e) {}

    function broadcastAmenitiesSync() {
        if (typeof BroadcastChannel !== 'undefined') {
            try {
                const bc = new BroadcastChannel('hotel_amenities_sync');
                bc.postMessage({ action: 'amenities_updated', timestamp: Date.now() });
                bc.close();
            } catch (e) {}
        }
        try {
            localStorage.setItem('hotel_amenities_updated_at', Date.now());
        } catch (e) {}
    }

    // Broadcast if an admin action just redirected back with success
    @if(session('success'))
        broadcastAmenitiesSync();
    @endif

    // Independent Mobile Actions Logic (Edit and Delete)
    let activeMobileMode = null; // null | 'edit' | 'delete'

    const mobileEditBtn = document.getElementById('mobileEditBtn');
    const mobileDeleteBtn = document.getElementById('mobileDeleteBtn');
    const noticeBox = document.getElementById('mobileSelectionNotice');
    const noticeText = document.getElementById('mobileSelectionNoticeText');
    const closeNoticeBtn = document.getElementById('closeMobileNoticeBtn');
    const amenitiesTable = document.querySelector('.amenities-table');

    function setMobileMode(mode) {
        if (activeMobileMode === mode) {
            clearMobileMode();
            return;
        }

        activeMobileMode = mode;

        if (amenitiesTable) {
            amenitiesTable.classList.remove('mode-edit', 'mode-delete');
            if (mode) {
                amenitiesTable.classList.add('mode-' + mode);
            }
        }

        // Update button visual states independently
        if (mobileEditBtn) {
            if (mode === 'edit') {
                mobileEditBtn.classList.add('btn-dark', 'active');
                mobileEditBtn.classList.remove('btn-outline-dark');
            } else {
                mobileEditBtn.classList.remove('btn-dark', 'active');
                mobileEditBtn.classList.add('btn-outline-dark');
            }
        }

        if (mobileDeleteBtn) {
            if (mode === 'delete') {
                mobileDeleteBtn.classList.add('active', 'border-2', 'border-dark');
            } else {
                mobileDeleteBtn.classList.remove('active', 'border-2', 'border-dark');
            }
        }

        // Update notice prompt banner
        if (mode === 'edit') {
            if (noticeBox) {
                noticeBox.className = 'alert alert-info alert-dismissible fade show py-2 px-3 small mb-3 d-md-none shadow-sm';
            }
            if (noticeText) {
                noticeText.textContent = 'Please select an amenity by clicking its table row to edit.';
            }
            if (noticeBox) {
                noticeBox.classList.remove('d-none');
                noticeBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        } else if (mode === 'delete') {
            if (noticeBox) {
                noticeBox.className = 'alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3 d-md-none shadow-sm';
            }
            if (noticeText) {
                noticeText.textContent = 'Please select an amenity by clicking its table row to delete.';
            }
            if (noticeBox) {
                noticeBox.classList.remove('d-none');
                noticeBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        } else {
            if (noticeBox) {
                noticeBox.classList.add('d-none');
            }
        }
    }

    function clearMobileMode() {
        activeMobileMode = null;
        if (amenitiesTable) {
            amenitiesTable.classList.remove('mode-edit', 'mode-delete');
        }
        if (mobileEditBtn) {
            mobileEditBtn.classList.remove('btn-dark', 'active');
            mobileEditBtn.classList.add('btn-outline-dark');
        }
        if (mobileDeleteBtn) {
            mobileDeleteBtn.classList.remove('active', 'border-2', 'border-dark');
        }
        if (noticeBox) {
            noticeBox.classList.add('d-none');
        }
    }

    if (closeNoticeBtn) {
        closeNoticeBtn.addEventListener('click', clearMobileMode);
    }

    function executeEdit(id) {
        const modalEl = document.getElementById('editAmenityModal' + id);
        if (modalEl) {
            if (window.bootstrap && bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                $(modalEl).modal('show');
            }
        }
    }

    function executeDelete(id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"?')) {
            const form = document.getElementById('deleteForm' + id);
            if (form) {
                form.submit();
            }
        }
    }

    // Row click handler on mobile: requires clicking a table row
    document.querySelectorAll('.amenity-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('form')) {
                return;
            }

            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            // Strictly execute only the corresponding action that was activated
            if (activeMobileMode === 'edit') {
                clearMobileMode();
                executeEdit(id);
            } else if (activeMobileMode === 'delete') {
                clearMobileMode();
                executeDelete(id, name);
            }
        });
    });

    // Mobile Edit Button click: strictly activates Edit mode
    if (mobileEditBtn) {
        mobileEditBtn.addEventListener('click', function() {
            setMobileMode('edit');
        });
    }

    // Mobile Delete Button click: strictly activates Delete mode
    if (mobileDeleteBtn) {
        mobileDeleteBtn.addEventListener('click', function() {
            setMobileMode('delete');
        });
    }
})();
</script>