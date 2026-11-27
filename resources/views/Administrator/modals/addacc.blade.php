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
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">First Name <span class="text-danger">*</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Last Name <span class="text-danger">*</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Email <span class="text-danger">*</label>
                        <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Contact Number <span class="text-danger">*</span></label>
                        {{-- Original: <input type="text" name="contact_number" class="form-control" placeholder="09123456789" required> --}}
                        <div class="input-group @error('contact_number') is-invalid @enderror">
                            <select id="addStaffCountryCode" class="form-select flex-shrink-0" style="max-width: 110px;" aria-label="Country code">
                                <option value="+63" selected>🇵🇭 +63</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+44">🇬🇧 +44</option>
                                <option value="+81">🇯🇵 +81</option>
                                <option value="+86">🇨🇳 +86</option>
                                <option value="+91">🇮🇳 +91</option>
                                <option value="+82">🇰🇷 +82</option>
                                <option value="+65">🇸🇬 +65</option>
                                <option value="+60">🇲🇾 +60</option>
                                <option value="+62">🇮🇩 +62</option>
                                <option value="+66">🇹🇭 +66</option>
                                <option value="+84">🇻🇳 +84</option>
                                <option value="+61">🇦🇺 +61</option>
                                <option value="+49">🇩🇪 +49</option>
                                <option value="+33">🇫🇷 +33</option>
                                <option value="+39">🇮🇹 +39</option>
                                <option value="+34">🇪🇸 +34</option>
                                <option value="+7">🇷🇺 +7</option>
                                <option value="+55">🇧🇷 +55</option>
                                <option value="+52">🇲🇽 +52</option>
                                <option value="+27">🇿🇦 +27</option>
                                <option value="+20">🇪🇬 +20</option>
                                <option value="+966">🇸🇦 +966</option>
                                <option value="+971">🇦🇪 +971</option>
                                <option value="+92">🇵🇰 +92</option>
                                <option value="+880">🇧🇩 +880</option>
                            </select>
                            <input
                                type="tel"
                                id="addStaffLocalNumber"
                                class="form-control @error('contact_number') is-invalid @enderror"
                                placeholder="9171234567"
                                pattern="[0-9]{7,14}"
                                minlength="7"
                                maxlength="14"
                                inputmode="numeric"
                                required
                                aria-label="Local phone number"
                            >
                            {{-- Hidden field that stores the combined +countrycode+localnumber value --}}
                            <input type="hidden" name="contact_number" id="addStaffContactNumber">
                        </div>
                        @error('contact_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Enter digits only (e.g., 9171234567). Do not include leading zeros after selecting a country code.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Username <span class="text-danger">*</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Password <span class="text-danger">*</label>
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

<script>
(function () {
    'use strict';

    const addStaffModal = document.getElementById('addStaffModal');
    if (!addStaffModal) { return; }

    const form        = addStaffModal.querySelector('form');
    const codeSelect  = document.getElementById('addStaffCountryCode');
    const localInput  = document.getElementById('addStaffLocalNumber');
    const hiddenInput = document.getElementById('addStaffContactNumber');

    // Strip any leading zeros from local number (e.g. 09171234567 → 9171234567)
    function normalizeLocal(val) {
        return val.replace(/^0+/, '').replace(/\D/g, '');
    }

    function syncHidden() {
        const local = normalizeLocal(localInput.value);
        hiddenInput.value = local ? (codeSelect.value + local) : '';
    }

    localInput.addEventListener('input', function () {
        // Allow digits only
        this.value = this.value.replace(/\D/g, '');
        syncHidden();
    });

    codeSelect.addEventListener('change', syncHidden);

    form.addEventListener('submit', function (e) {
        const local = normalizeLocal(localInput.value);
        if (!local || local.length < 7 || local.length > 14) {
            e.preventDefault();
            localInput.setCustomValidity('Please enter a valid local number (7–14 digits, digits only).');
            localInput.reportValidity();
            return;
        }
        localInput.setCustomValidity('');
        syncHidden();
    });

    // Clear validation message on input
    localInput.addEventListener('input', function () {
        this.setCustomValidity('');
    });

    // Reset form when modal closes
    addStaffModal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        hiddenInput.value = '';
        codeSelect.value  = '+63';
        localInput.value  = '';
        localInput.setCustomValidity('');
    });
}());
</script>
