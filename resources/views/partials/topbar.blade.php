<!-- Topbar -->
<header class="bg-white border-bottom shadow-sm px-4 py-3 d-flex justify-content-between align-items-center">
    <div>
        <!-- Welcoming Greeting -->
        <h4 class="mb-0 fw-bold text-dark" style="font-size: clamp(1.1rem, 1.3vw, 1.45rem); letter-spacing: -0.01em;">
            <!-- Good day, {{ Auth::check() ? Auth::user()->name : 'Name' }}! -->
             Good day, Name!
        </h4>
    </div>
    
    <!-- Header Action Buttons -->
    <div class="d-flex align-items-center gap-2 gap-sm-3">
        <!-- Book Button: Opens Book Choice Modal -->
        <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.03em;" data-bs-toggle="modal" data-bs-target="#bookChoiceModal">
            Book
        </button>

        <!-- Add Amenities Button: Opens Amenities Modal -->
        <button type="button" class="btn btn-dark fw-semibold px-3 py-2 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.03em; background-color: #6A4435; border-color: #6A4435;" data-bs-toggle="modal" data-bs-target="#amenitiesModal">
            Add Amenities
        </button>
    </div>
</header>
<!-- End of Topbar -->