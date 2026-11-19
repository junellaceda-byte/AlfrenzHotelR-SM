<!-- Set Monthly Target Modal -->
<div class="modal fade" id="setTargetModal" tabindex="-1" aria-labelledby="setTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold" id="setTargetModalLabel">Set Monthly Target</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="setTargetForm" action="" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <label for="monthlyTargetInput" class="form-label fw-semibold" style="color: #443627;">
                        Target Revenue Amount (₱)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold" style="background-color: #f8f5f2; color: #443627;">₱</span>
                        <input type="number"
                            class="form-control"
                            id="monthlyTargetInput"
                            name="monthly_target"
                            min="1"
                            step="any"
                            placeholder="e.g. 150000"
                            required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background-color: #443627;">
                        Save Target
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>