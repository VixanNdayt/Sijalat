<?php

use App\Http\Controllers\AlatController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\PinjamController;
use App\Http\Controllers\QrTransaksiController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

// Halaman awal menampilkan form login.
Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/pinjam/buat', [PinjamController::class, 'create'])->name('pinjam.create');
    Route::post('/pinjam', [PinjamController::class, 'store'])->name('pinjam.store');
    Route::delete('/pinjam/{pinjam}', [PinjamController::class, 'destroy'])->name('pinjam.destroy');

    Route::get('/pinjam/bukti', [TransaksiController::class, 'create'])->name('pinjam.bukti');

    Route::get('/riwayat/{pinjam}/cetak', [RiwayatController::class, 'cetak'])->name('riwayat.cetak');
    Route::get('/riwayat/{pinjam}/struk', [RiwayatController::class, 'struk'])->name('riwayat.struk');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/pesan', [ChatController::class, 'pesan'])->name('chat.messages');
    Route::post('/chat', [ChatController::class, 'kirim'])->name('chat.kirim');

    Route::middleware('admin')->group(function () {
        Route::get('/admin/chat', [ChatController::class, 'adminIndex'])->name('admin.chat.index');
        Route::get('/admin/chat/{user}/pesan', [ChatController::class, 'adminPesan'])->name('admin.chat.messages');
        Route::post('/admin/chat/{user}', [ChatController::class, 'adminKirim'])->name('admin.chat.kirim');
        Route::get('/admin/chat/{user}', [ChatController::class, 'adminShow'])->name('admin.chat.show');
    });
});

Route::middleware('auth')->group(function () {
    // Profil tetap bisa diakses walau email belum terverifikasi agar siswa
    // dapat memperbaiki alamat email yang salah.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('verified')->group(function () {
        Route::get('/guide', [GuideController::class, 'index'])->name('guide');

        Route::get('/transaksi/scan', [TransaksiController::class, 'scanner'])->name('transaksi.scan');
        Route::post('/transaksi/proses', [TransaksiController::class, 'proses'])->name('transaksi.proses');
        Route::post('/transaksi/cari-kode', [TransaksiController::class, 'cariKode'])->name('transaksi.cariKode');

        Route::middleware('admin')->group(function () {
            Route::get('/admin/approval', [ApprovalController::class, 'index'])->name('approval.index');
            Route::post('/admin/approval/proses/{id}', [ApprovalController::class, 'proses'])->name('approval.proses');

            Route::get('/admin/alat', [AlatController::class, 'index'])->name('admin.alat.index');
            Route::post('/admin/alat', [AlatController::class, 'store'])->name('admin.alat.store');
            Route::put('/admin/alat/{id}', [AlatController::class, 'update'])->name('admin.alat.update');
            Route::delete('/admin/alat/{id}', [AlatController::class, 'destroy'])->name('admin.alat.destroy');

            Route::get('/admin/analisis', [App\Http\Controllers\AnalisisController::class, 'index'])->name('admin.analisis');
            Route::get('/admin/analisis/export/pdf', [App\Http\Controllers\AnalisisController::class, 'exportPdf'])->name('admin.analisis.pdf');
            Route::get('/admin/analisis/export/excel', [App\Http\Controllers\AnalisisController::class, 'exportExcel'])->name('admin.analisis.excel');

            Route::get('/admin/qr', [QrTransaksiController::class, 'index'])->name('admin.qr.index');
            Route::get('/admin/qr/{pinjam}/{jenis}', [QrTransaksiController::class, 'print'])->name('admin.qr.print');
        });
    });
});

require __DIR__.'/auth.php';
