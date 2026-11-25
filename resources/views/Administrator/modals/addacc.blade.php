<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start border-0 shadow overflow-hidden">
            <div class="modal-header border-0 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="addStaffModalLabel">Add Staff Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="/staff/store">
                @csrf
                <input type="hidden" name="section" value="accman">
                <div class="modal-body p-4" style="background-color: #F5F7DF;">
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" placeholder="09123456789" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background-color: #443627;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold" style="background-color: #F5F7DF; color: #443627;">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>
