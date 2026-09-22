<?php

use Illuminate\Support\Facades\Route;

// Redirect root "/" to the First page
Route::get('/', function () {
    return redirect('/dashboard');
});

// Show the First page 
Route::get('/login',function(){
    return view('dashboard.login');
});

// Dashboard 
Route::get('/dashboard', function () {
    return view('dashboard.receptionist');
});
