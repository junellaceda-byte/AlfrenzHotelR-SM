<!-- Topbar -->
                <header class="border-bottom p-3 shadow-sm d-flex justify-content-between align-items-center" style="background-color: #da7b14;">
                    <div>
                        <!-- Welcoming Message -->
                        <div class="border border-secondary rounded px-3 py-2 text-secondary fw-semibold bg-light d-inline-block">
                            Good day, Name!
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-4 pe-3">
                        
                        <!-- Bell Icon Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="text-secondary position-relative d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{{ asset('icons/bell.svg') }}" width="20" height="20" alt="Notifications">
                                
                                <!-- Dynamic Badge (Only displays if there are due guests) -->
                                @if(isset($dueGuests) && $dueGuests->count() > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                        {{ $dueGuests->count() }}
                                        <span class="visually-hidden">due guests</span>
                                    </span>
                                @endif
                            </a>
                            
                            <!-- Dropdown Menu List -->
                            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 280px; max-height: 300px; overflow-y: auto;">
                                <li>
                                    <h6 class="dropdown-header text-uppercase fw-bold text-dark border-bottom pb-2 mb-0">
                                        Due Guests
                                    </h6>
                                </li>
                                
                                <!-- Iterate through data -->
                                @if(isset($dueGuests) && $dueGuests->count() > 0)
                                    @foreach($dueGuests as $guest)
                                        <li>
                                            <a class="dropdown-item py-2 border-bottom" href="#">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-semibold text-secondary">{{ $guest->name }}</span>
                                                    <span class="badge bg-warning text-dark">Room {{ $guest->room_number }}</span>
                                                </div>
                                                <small class="text-danger d-block mt-1">Checkout time reached</small>
                                            </a>
                                        </li>
                                    @endforeach
                                @else
                                    <li>
                                        <span class="dropdown-item text-muted small text-center py-3">
                                            No guests are currently due.
                                        </span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <!-- End Bell Icon Dropdown -->

                        <!-- Logout Icon Button -->
                        <a href="#" class="text-secondary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <!-- Ensure you have a logout.svg file in your icons folder -->
                            <img src="{{ asset('icons/logout.svg') }}" width="20" height="20" alt="Logout">
                        </a>
                        <!-- End Logout Icon Button -->
                        
                    </div>
                </header>
                <!-- End of Topbar -->


                <!-- Logout Confirmation Modal -->
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <!-- modal-dialog-centered keeps it perfectly in the middle, modal-sm makes it compact -->
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <!-- Light Deep Charcoal Background applied here -->
                        <div class="modal-content border-0 shadow-lg" style="background-color: #535C68; color: #ffffff;">
                            <div class="modal-body text-center p-4">
                                
                                <h5 class="mb-4 fw-semibold">Log Out Session?</h5>
                                
                                <div class="d-flex justify-content-center gap-3">
                                    <!-- Yes Button (Blue) -->
                                    <!-- Note: Change this button into an <form> submit button later when you connect your Laravel backend logout logic -->
                                    <button type="button" class="btn btn-primary px-4 fw-semibold">Yes</button>
                                    
                                    <!-- No Button (Red) - dismisses modal -->
                                    <button type="button" class="btn btn-danger px-4 fw-semibold" data-bs-dismiss="modal">No</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
<!-- End Logout Modal -->