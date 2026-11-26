<!-- Modal: Activity Log Details Modal -->
<div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-labelledby="activityDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="modal-title fw-bold text-white mb-0" id="activityDetailsModalLabel">
                            Activity Log Details
                        </h5>
                        <span class="badge rounded-pill border" id="modalActivityIdBadge" style="background-color: rgba(245, 247, 223, 0.15); color: #F5F7DF; border-color: rgba(245, 247, 223, 0.3) !important; font-size: 0.72rem;">
                            #—
                        </span>
                    </div>
                    <small class="text-white-50" style="font-size: 0.78rem;">
                        Complete record information for the selected activity
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 p-md-4" style="background-color: #F5F7DF;">
                <!-- Summary Strip: Type & Date/Time -->
                <div class="p-3 rounded border bg-white mb-3 d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-2">
                    <div>
                        <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Activity Type &amp; Action</small>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge rounded-pill px-2.5 py-1 fw-semibold" id="modalActivityTypeBadge">—</span>
                            <span class="fw-semibold text-dark text-capitalize small" id="modalActivityAction">—</span>
                        </div>
                    </div>
                    <div class="text-sm-end">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Date &amp; Time</small>
                        <div class="fw-bold text-dark small" id="modalActivityDate">—</div>
                        <div class="text-muted small" id="modalActivityTime" style="font-size: 0.78rem;">—</div>
                    </div>
                </div>

                <!-- Responsible User Section -->
                <div class="p-3 rounded border bg-white mb-3">
                    <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.7rem;">Responsible User</small>
                    <div class="d-flex align-items-center gap-3">
                        <div id="modalActivityAvatar" class="activity-user-avatar text-white fw-bold" style="background-color: #6A4435; width: 38px !important; height: 38px !important; min-width: 38px !important; min-height: 38px !important; max-width: 38px !important; max-height: 38px !important;">
                            R
                        </div>
                        <div class="min-width-0">
                            <div class="fw-bold text-dark" id="modalActivityActorName">—</div>
                            <div class="text-muted small d-flex align-items-center flex-wrap gap-2 mt-1">
                                <span class="badge bg-light text-secondary border d-none" id="modalActivityStaffBadge">Receptionist</span>
                                <span id="modalActivityActorUsername">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Details & Financials Grid -->
                <div class="row g-3 mb-3">
                    <!-- Activity Details -->
                    <div class="col-12 col-md-7">
                        <div class="p-3 rounded border bg-white h-100">
                            <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.7rem;">Activity Details</small>
                            <div class="mb-2">
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">Subject / Guest</span>
                                <span class="fw-semibold text-dark" id="modalActivityGuestRoom">—</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">Description</span>
                                <span class="text-dark small" id="modalActivityDescription">—</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2" id="modalActivityMetaBadges"></div>
                        </div>
                    </div>

                    <!-- Financials / Method -->
                    <div class="col-12 col-md-5">
                        <div class="p-3 rounded border bg-white h-100">
                            <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.7rem;">Financials / Method</small>
                            <div class="mb-2">
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">Amount / Status</span>
                                <span class="fw-bold fs-6" id="modalActivityAmount">—</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">Payment Method</span>
                                <span class="text-dark small text-capitalize fw-semibold" id="modalActivityPaymentMethod">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Record Information (Extra Details JSON / Subject Info) -->
                <div class="p-3 rounded border bg-white d-none" id="modalActivityExtraSection">
                    <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.7rem;">Additional Information</small>
                    <div class="row g-2" id="modalActivityExtraGrid"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-0 pt-2 pb-2" style="background-color: #443627;">
                <button type="button" class="btn btn-link text-decoration-none p-0 fw-semibold" style="color: #F5F7DF;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
