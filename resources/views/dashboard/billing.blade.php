@extends('layouts.payment')

@section('title', 'Billing Records')

@section('billing-content')

<!-- Top Row -->
<div class="container-fluid p-0">
    <div class="row g-4 mb-4">
        <!-- Card 1 -->
        <div class="col-md-6">
            <div class="card h-100 border-0 bg-body-tertiary rounded-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h5 class="card-title text-dark mb-0 fs-5">PENDING PAYMENT</h5>
                </div>
            </div>
        </div> 
                            
        <!-- Card 2 -->
        <div class="col-md-6">
            <div class="card h-100 border-0 bg-body-tertiary rounded-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h5 class="card-title text-dark mb-0 fs-5">CONFIRMED PAYMENT</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="row g-4">
        <!-- Card 3 -->
        <div class="col-12">
            <div class="card border-0 bg-body-tertiary rounded-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h5 class="card-title text-dark mb-0 fs-5">BILLING PROCESS</h5>
                </div>
            </div>
        </div>
    </div>   
</div>       
@endsection       
