<!-- Add Amenity Modal -->
<div class="modal fade" id="addAmenityModal" tabindex="-1" aria-labelledby="addAmenityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addAmenityModalLabel">Add Amenity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/amenities/store">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Amenity name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Amenity price</label>
                        <input type="number" name="price" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Amenity charged per</label>
                        <select name="charged" class="form-select" required>
                            <option value="person">person</option>
                            <option value="item">item</option>
                            <option value="hours">hours</option>
                            <option value="night">night</option>
                            <!-- hours & night must be removed in this option, it will be become description -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">Save Amenity</button>
                </div>
            </form>
        </div>
    </div>
</div>