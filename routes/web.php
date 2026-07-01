<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/inventaris', function () {
    return Inertia::render('Inventaris');
})->name('inventaris');

Route::get('/kasir', function () {
    return Inertia::render('Auth/Kasir');
})->name('kasir');

Route::get('/penjualan', function () {
    return Inertia::render('Penjualan');
})->name('penjualan');

Route::get('/pengaturan', function () {
    return Inertia::render('Pengaturan');
})->name('pengaturan');

Route::get('/laporan', function () {
    return Inertia::render('Laporan');
})->name('laporan');

