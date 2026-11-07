<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        unset($data['profile_photo'], $data['remove_photo']);

        $user->fill($data);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')
                ->store('profile-photos', 'public');
        } elseif ($request->boolean('remove_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Hapus berkas foto profil agar tidak meninggalkan file yatim.
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Jangan izinkan akun dihapus selama masih ada peminjaman aktif.
        // Tanpa cek ini, foreign key cascade akan ikut menghapus riwayat
        // dan membuat data alat tidak sinkron.
        $pinjamanAktif = $user->pinjams()
            ->whereIn('status', ['pending', 'disetuji', 'dipinjam'])
            ->count();

        if ($pinjamanAktif > 0) {
            return Redirect::route('profile.edit')->withErrors([
                'akun' => 'Akun tidak bisa dihapus karena masih memiliki '
                    . $pinjamanAktif
                    . ' peminjaman aktif. Selesaikan atau batalkan peminjaman terlebih dahulu.',
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
