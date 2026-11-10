<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Bukti transaksi lama sempat disimpan di disk publik sehingga dapat diakses
 * langsung lewat /storage. Pindahkan semuanya ke disk privat agar hanya bisa
 * dibuka melalui route admin yang sudah dilindungi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $publik = Storage::disk('public');
        $privat = Storage::disk('local');

        foreach ($publik->allFiles('bukti_transaksi') as $path) {
            if (! $privat->exists($path)) {
                $privat->put($path, $publik->get($path));
            }

            $publik->delete($path);
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan ke disk publik karena alasan keamanan.
    }
};
