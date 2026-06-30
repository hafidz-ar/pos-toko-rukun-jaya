<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/inventory', function () {
    return Inertia::render('Inventory');
})->name('inventory');

Route::get('/kasir', function () {
    return Inertia::render('Auth/Kasir');
})->name('kasir');

