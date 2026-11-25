<?php

namespace App\Policies;

use App\Models\Pinjam;
use App\Models\User;

/**
 * Aturan akses per resource peminjaman. Dipakai oleh controller melalui
 * Gate agar kepemilikan dan hak admin diverifikasi di server.
 */
class PinjamPolicy
{
    /**
     * Admin boleh melihat semua transaksi; siswa hanya miliknya sendiri.
     */
    public function view(User $user, Pinjam $pinjam): bool
    {
        return $user->isAdmin() || (int) $pinjam->user_id === (int) $user->id;
    }

    /**
     * Siswa hanya boleh membatalkan pengajuannya sendiri yang masih pending.
     */
    public function delete(User $user, Pinjam $pinjam): bool
    {
        return (int) $pinjam->user_id === (int) $user->id
            && $pinjam->status === 'pending';
    }

    /**
     * Hanya admin/guru yang boleh menyetujui atau menolak pengajuan.
     */
    public function approve(User $user, Pinjam $pinjam): bool
    {
        return $user->isAdmin();
    }
}
