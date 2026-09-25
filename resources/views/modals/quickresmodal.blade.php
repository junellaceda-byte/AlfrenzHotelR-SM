<div class="modal fade" id="res-modal" tabindex="-1" aria-labelledby="modal-title" aria-hidden="true">
 <div class="modal-dialog">
    
    <div class="modal-content">
        
        <div class="modal-header">
            <h5 class="modal-title" id="modal-title"> Create New Reservations</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        
        <form action="{{route('formsubmitted')}}" method="post">
            @csrf
            
            <div class="modal-body">
                
                <div class="col-12">
                    <div class="mb-3">
                        <label for="firstName" class="form-label">Guest's First Name:</label>
                        <input type="text" class="form-control" id="fname" name="fname" placeholder="e.g Juan" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Guest's Last Name:</label>
                        <input type="text" class="form-control" id="lname" name="lname" placeholder="e.g De La Cruz" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Guest's Phone Number</label>
                        <input type="number" class="form-control" id="phone" name="phone" placeholder="e.g 09123456789" required>
                    </div>

                    <!-- Check-in (Calendar) -->
                    <div class="md-3">
                        <label for="checkin" class="form-label">Check-in Date</label>
                        <input type="date" class="form-control" id="checkin" name="checkin" required>
                    </div>
                    
                    <!-- Check-out (Calendar) -->
                    <div class="md-3">
                        <label for="checkout" class="form-label">Check-out Date</label>
                        <input type="date" class="form-control" id="checkout" name="checkout" required>
                    </div>
                    
                    <!-- Room Number -->
                    <div class="md-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" placeholder="e.g. 3" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
            
        </form> 
    </div>
 </div>
</div>