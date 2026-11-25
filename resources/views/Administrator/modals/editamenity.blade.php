<!-- Edit Amenity Modal -->
<div class="modal fade" id="editAmenityModal{{ $amenity->id }}" tabindex="-1" aria-labelledby="editAmenityModalLabel{{ $amenity->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start border-0 shadow overflow-hidden">
            <div class="modal-header border-0 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="editAmenityModalLabel{{ $amenity->id }}">Edit Amenity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="/amenities/{{ $amenity->id }}/update" enctype="multipart/form-data">
                @csrf
                <!-- Laravel method spoofing for PUT request -->
                @method('PUT')
                
                <div class="modal-body p-4" style="background-color: #F5F7DF;">
                    <!-- Current & Replacement Amenity Image Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Amenity Image</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="amenity-cube-preview border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden">
                                <img id="editAmenityPreview{{ $amenity->id }}" src="{{ $amenity->image_url }}" alt="{{ $amenity->name }}" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="image" id="editAmenityImage{{ $amenity->id }}" class="form-control" accept="image/*" onchange="previewEditAmenityImage(event, '{{ $amenity->id }}')">
                                <div class="form-text text-muted small">Upload replacement cube image. Leave blank to keep current image.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Amenity name</label>
                        <input type="text" name="name" class="form-control" value="{{ $amenity->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Amenity price</label>
                        <input type="number" name="price" step="0.01" class="form-control" value="{{ $amenity->price }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Description</label>
                        <input type="text" name="charged" class="form-control" value="{{ $amenity->charged }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Status</label>
                        <select name="offered" class="form-select" required>
                            <option value="1" {{ $amenity->offered ? 'selected' : '' }}>On (Being Offered)</option>
                            <option value="0" {{ !$amenity->offered ? 'selected' : '' }}>Off (Not Offered)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background-color: #443627;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold" style="background-color: #F5F7DF; color: #443627;">Update Amenity</button>
                </div>
            </form>
        </div>
    </div>
</div>
