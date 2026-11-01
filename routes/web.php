<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PinjamController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

// Jalur untuk menampilkan form register
Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    Route::get('/pinjam/buat', [PinjamController::class, 'create'])->name('pinjam.create');
    Route::post('/pinjam', [PinjamController::class, 'store'])->name('pinjam.store');

    Route::get('/pinjam/bukti', [TransaksiController::class, 'create'])->name('pinjam.bukti');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/transaksi/scan', [TransaksiController::class, 'scanner'])->name('transaksi.scan');
    Route::post('/transaksi/proses', [TransaksiController::class, 'proses'])->name('transaksi.proses');
    Route::post('/transaksi/cari-kode', [TransaksiController::class, 'cariKode'])->name('transaksi.cariKode');

    Route::get('/admin/approval', [ApprovalController::class, 'index'])->name('approval.index');
    Route::post('/admin/approval/proses/{id}', [ApprovalController::class, 'proses'])->name('approval.proses');
});

require __DIR__.'/auth.php';

