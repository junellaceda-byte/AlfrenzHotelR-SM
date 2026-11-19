<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addStaffModalLabel">Add Staff Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="/staff/store">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Gmail</label>
                        <input type="email" name="gmail" class="form-control" placeholder="example@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" placeholder="09123456789" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>