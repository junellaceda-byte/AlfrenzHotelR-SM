<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use  App\Http\Controllers\ReserveController;

// Gets
// Redirect root "/" to the First page
Route::get('/', function () {
    return redirect('/dashboard');
});

// Show the Login page 
Route::get('/login',function(){
    return view('dashboard.login');
});

// Dashboard 
Route::get('/dashboard', function () {
    return view('dashboard.receptionist');
});
//Reservation Records
Route::get('/reservation', function () {
    return view('dashboard.reservation');
});

// Billing Records
Route::get('/billing', function () {
    return view('dashboard.billing');
});

// Posts
Route::post('/reservation', function(Request $request){

    // Secured Validate
    $request->validate([
        'fname' => 'required|min:5|max:30',
        'lname' => 'required|min:5|max:30',
        'phone' => 'required|min:11|max:11',
    ]);
    // Getting Input
    $fName = $request->input('fname');
    $lName = $request->input('lname');
    $phone = $request->input('phone');
    
})->name("formsubmitted");

//Controller for Resource
Route::resource('reserve', ReserveController::class);