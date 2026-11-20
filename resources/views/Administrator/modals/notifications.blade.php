<!-- Modal: Admin Notifications Modal (Due Checkouts & Cancellation Revenue Losses) -->
<div class="modal fade" id="adminNotificationsModal" tabindex="-1" aria-labelledby="adminNotificationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <!-- Modal Header (matches Receptionist modal style) -->
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-dark" id="adminNotificationsModalLabel">
                        Notifications &amp; Revenue Losses
                    </h5>
                    <small class="text-muted" style="font-size: 0.78rem;">
                        Real-time overdue guest checkouts and revenue lost from cancellations
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4" id="adminNotificationsModalBody">
                <div class="text-center py-4 text-secondary">
                    <p class="mb-0 fw-semibold fst-italic" style="font-style: italic;">Loading notifications...</p>
                    <small class="text-muted d-block mt-1">Fetching real-time records from Receptionist Billing and Reservations...</small>
                </div>
            </div>

            <!-- Modal Footer (matches Receptionist modal style) -->
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

