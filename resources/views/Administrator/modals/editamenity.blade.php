<!-- Edit Amenity Modal -->
<div class="modal fade" id="editAmenityModal{{ $amenity->id }}" tabindex="-1" aria-labelledby="editAmenityModalLabel{{ $amenity->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="editAmenityModalLabel{{ $amenity->id }}">Edit Amenity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="/amenities/{{ $amenity->id }}/update">
                @csrf
                <!-- Laravel method spoofing for PUT request -->
                @method('PUT')
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Amenity name</label>
                        <input type="text" name="name" class="form-control" value="{{ $amenity->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Amenity price</label>
                        <input type="number" name="price" step="0.01" class="form-control" value="{{ $amenity->price }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Amenity charged per</label>
                        <select name="charged" class="form-select" required>
                            <option value="person" {{ $amenity->charged == 'person' ? 'selected' : '' }}>person</option>
                            <option value="item" {{ $amenity->charged == 'item' ? 'selected' : '' }}>item</option>
                            <option value="hours" {{ $amenity->charged == 'hours' ? 'selected' : '' }}>hours</option>
                            <option value="night" {{ $amenity->charged == 'night' ? 'selected' : '' }}>night</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Offered Status</label>
                        <select name="offered" class="form-select" required>
                            <option value="1" {{ $amenity->offered ? 'selected' : '' }}>On (Being Offered)</option>
                            <option value="0" {{ !$amenity->offered ? 'selected' : '' }}>Off (Not Offered)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">Update Amenity</button>
                </div>
            </form>
        </div>
    </div>
</div>