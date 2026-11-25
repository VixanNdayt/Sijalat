<?php

namespace App\Policies;

use App\Models\User;

/**
 * Pengelolaan data master alat hanya untuk admin/guru.
 */
class AlatPolicy
{
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
