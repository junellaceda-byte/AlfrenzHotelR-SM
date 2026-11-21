<!-- Edit Account Modal -->
<div class="modal fade" id="editStaffModal{{ $staff->id }}" tabindex="-1" aria-labelledby="editStaffModalLabel{{ $staff->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="editStaffModalLabel{{ $staff->id }}">Edit Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="/staff/{{ $staff->id }}/update">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="accman">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ $staff->first_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ $staff->last_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ $staff->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" value="{{ $staff->contact_number }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ $staff->username }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Password</label>
                        <input type="text" name="password" class="form-control" value="{{ $staff->password }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">Update Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>