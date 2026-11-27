<!-- Edit Account Modal -->
<div class="modal fade" id="editStaffModal{{ $staff->id }}" tabindex="-1" aria-labelledby="editStaffModalLabel{{ $staff->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start border-0 shadow overflow-hidden">
            <div class="modal-header border-0 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="editStaffModalLabel{{ $staff->id }}">Edit Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="/staff/{{ $staff->id }}/update">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="accman">
                
                <div class="modal-body p-4" style="background-color: #F5F7DF;">
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="{{ $staff->first_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="{{ $staff->last_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ $staff->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Contact Number <span class="text-danger">*</span></label>
                        {{-- Original: <input type="text" name="contact_number" class="form-control" value="{{ $staff->contact_number }}" required> --}}
                        @php
                            // Parse stored contact_number into country code + local number
                            $storedContact = $staff->contact_number ?? '';
                            $editCountryCode = '+63';
                            $editLocalNumber = $storedContact;
                            // Match a leading + followed by 1–4 digits (country code)
                            if (preg_match('/^(\+\d{1,4})(\d+)$/', $storedContact, $m)) {
                                $editCountryCode = $m[1];
                                $editLocalNumber  = $m[2];
                            }
                            $knownCodes = ['+1','+7','+20','+27','+33','+34','+39','+44','+49','+52','+55','+60','+61','+62','+63','+65','+66','+81','+82','+84','+86','+91','+92','+880','+966','+971'];
                            if (!in_array($editCountryCode, $knownCodes)) {
                                $editLocalNumber = $storedContact;
                                $editCountryCode = '+63';
                            }
                        @endphp
                        <div class="input-group @error('contact_number') is-invalid @enderror">
                            <select id="editStaffCountryCode{{ $staff->id }}" class="form-select flex-shrink-0" style="max-width: 110px;" aria-label="Country code">
                                @foreach(['+63'=>'🇵🇭 +63','+1'=>'🇺🇸 +1','+44'=>'🇬🇧 +44','+81'=>'🇯🇵 +81','+86'=>'🇨🇳 +86','+91'=>'🇮🇳 +91','+82'=>'🇰🇷 +82','+65'=>'🇸🇬 +65','+60'=>'🇲🇾 +60','+62'=>'🇮🇩 +62','+66'=>'🇹🇭 +66','+84'=>'🇻🇳 +84','+61'=>'🇦🇺 +61','+49'=>'🇩🇪 +49','+33'=>'🇫🇷 +33','+39'=>'🇮🇹 +39','+34'=>'🇪🇸 +34','+7'=>'🇷🇺 +7','+55'=>'🇧🇷 +55','+52'=>'🇲🇽 +52','+27'=>'🇿🇦 +27','+20'=>'🇪🇬 +20','+966'=>'🇸🇦 +966','+971'=>'🇦🇪 +971','+92'=>'🇵🇰 +92','+880'=>'🇧🇩 +880'] as $code => $label)
                                    <option value="{{ $code }}" {{ $editCountryCode === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input
                                type="tel"
                                id="editStaffLocalNumber{{ $staff->id }}"
                                class="form-control @error('contact_number') is-invalid @enderror"
                                placeholder="9171234567"
                                pattern="[0-9]{7,14}"
                                minlength="7"
                                maxlength="14"
                                inputmode="numeric"
                                value="{{ $editLocalNumber }}"
                                required
                                aria-label="Local phone number"
                            >
                            {{-- Hidden field that stores the combined +countrycode+localnumber value --}}
                            <input type="hidden" name="contact_number" id="editStaffContactNumber{{ $staff->id }}" value="{{ $storedContact }}">
                        </div>
                        @error('contact_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Enter digits only (e.g., 9171234567). Do not include leading zeros after selecting a country code.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" value="{{ $staff->username }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control" value="{{ $staff->password }}" required>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background-color: #443627;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold" style="background-color: #F5F7DF; color: #443627;">Update Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const staffId     = '{{ $staff->id }}';
    const editModal   = document.getElementById('editStaffModal' + staffId);
    if (!editModal) { return; }

    const form        = editModal.querySelector('form');
    const codeSelect  = document.getElementById('editStaffCountryCode' + staffId);
    const localInput  = document.getElementById('editStaffLocalNumber' + staffId);
    const hiddenInput = document.getElementById('editStaffContactNumber' + staffId);

    function normalizeLocal(val) {
        return val.replace(/^0+/, '').replace(/\D/g, '');
    }

    function syncHidden() {
        const local = normalizeLocal(localInput.value);
        hiddenInput.value = local ? (codeSelect.value + local) : '';
    }

    localInput.addEventListener('input', function () {
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

    localInput.addEventListener('input', function () {
        this.setCustomValidity('');
    });
}());
</script>
