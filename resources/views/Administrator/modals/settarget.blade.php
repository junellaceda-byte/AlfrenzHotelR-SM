<!-- Set Monthly Target Modal -->
<div class="modal fade" id="setTargetModal" tabindex="-1" aria-labelledby="setTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold" id="setTargetModalLabel">Set Monthly Target</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="setTargetForm" action="{{ route('api.admin.monthly-target') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div id="setTargetAlert" class="alert alert-danger py-2 px-3 small d-none mb-3"></div>

                    @php
                        $now = now()->setTimezone('Asia/Manila');
                        $modalYear = (int) $now->format('Y');
                        $modalMonth = (int) $now->format('n');
                        $modalMonths = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                    @endphp

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="monthlyTargetMonthSelect" class="form-label fw-semibold small" style="color: #443627;">Target Month</label>
                            <select id="monthlyTargetMonthSelect" name="month" class="form-select" required>
                                @foreach($modalMonths as $mNum => $mName)
                                    <option value="{{ $mNum }}" {{ $mNum === $modalMonth ? 'selected' : '' }}>{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label for="monthlyTargetYearSelect" class="form-label fw-semibold small" style="color: #443627;">Target Year</label>
                            <select id="monthlyTargetYearSelect" name="year" class="form-select" required>
                                @for($y = $modalYear + 1; $y >= $modalYear - 3; $y--)
                                    <option value="{{ $y }}" {{ $y === $modalYear ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <label for="monthlyTargetInput" class="form-label fw-semibold" style="color: #443627;">
                        Target Revenue Amount (₱)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold" style="background-color: #f8f5f2; color: #443627;">₱</span>
                        <input type="number"
                            class="form-control"
                            id="monthlyTargetInput"
                            name="target_amount"
                            min="1"
                            step="any"
                            placeholder="e.g. 150000"
                            required>
                    </div>
                    <small id="setTargetCurrentHint" class="text-muted mt-2 d-block" style="font-size: 0.8rem;">
                        This sets the monthly revenue goal for the selected month to track real-time progress on the target gauge.
                    </small>
                </div>
                <div class="modal-footer border-0 px-4 py-3" style="background-color: #443627;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveTargetSubmitBtn" class="btn fw-semibold" style="background-color: #F5F7DF; color: #443627;">
                        Save Target
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
