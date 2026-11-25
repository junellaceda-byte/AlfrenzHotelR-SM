<!-- Add Amenity Modal -->
<div class="modal fade" id="addAmenityModal" tabindex="-1" aria-labelledby="addAmenityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="addAmenityModalLabel">Add Amenity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/amenities/store" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4" style="background-color: #F5F7DF;">
                    <!-- Image Upload with Cube Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Amenity Image</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="amenity-cube-preview border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden">
                                <img id="addAmenityPreview" src="{{ asset('images/amenities/default-amenity.svg') }}" alt="Amenity Preview" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="image" id="addAmenityImage" class="form-control" accept="image/*">
                                <div class="form-text text-muted small">Select an image for the amenity cube (PNG, JPG, SVG, WebP up to 2MB). Optional.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Amenity name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Extra Bed" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Amenity price</label>
                        <input type="number" name="price" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Description</label>
                        <input type="text" name="charged" class="form-control" placeholder="e.g. per item, per hour, per person" required>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background-color: #443627;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold" style="background-color: #F5F7DF; color: #443627;">Save Amenity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addImgInput = document.getElementById('addAmenityImage');
    const addImgPreview = document.getElementById('addAmenityPreview');
    const defaultImgSrc = "{{ asset('images/amenities/default-amenity.svg') }}";

    if (addImgInput && addImgPreview) {
        addImgInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    addImgPreview.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                addImgPreview.src = defaultImgSrc;
            }
        });
    }
});
</script>
