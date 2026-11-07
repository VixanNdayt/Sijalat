<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pinjam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'nis' => $user->nis,
                'kelas' => $user->kelas,
                'email' => 'test@student.stembayo.sch.id',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@student.stembayo.sch.id', $user->email);
    }

    public function test_student_can_not_change_email_to_non_school_domain(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Test User',
                'nis' => $user->nis,
                'kelas' => $user->kelas,
                'email' => 'test@example.com',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'nis' => $user->nis,
                'kelas' => $user->kelas,
                'email' => $user->email,
                'profile_photo' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        $this->assertStringStartsWith('profile-photos/', $user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'profile_photo_path' => 'profile-photos/old-avatar.jpg',
        ]);

        Storage::disk('public')->put('profile-photos/old-avatar.jpg', 'dummy');

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'nis' => $user->nis,
                'kelas' => $user->kelas,
                'email' => $user->email,
                'remove_photo' => '1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNull($user->profile_photo_path);
        Storage::disk('public')->assertMissing('profile-photos/old-avatar.jpg');
    }

    public function test_user_uses_initials_when_no_profile_photo_exists(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'profile_photo_path' => null,
        ]);

        $this->assertNull($user->profilePhotoUrl());
        $this->assertSame('BS', $user->initials());

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('user-avatar-initials', false)
            ->assertSee('BS');
    }

    public function test_profile_photo_must_be_a_valid_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'nis' => $user->nis,
                'kelas' => $user->kelas,
                'email' => $user->email,
                'profile_photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('profile_photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_account_can_not_be_deleted_while_it_has_active_loans(): void
    {
        $user = User::factory()->create();

        $alat = Alat::create([
            'code' => 'ALT-PROFILE-1',
            'nama' => 'Switch Profil',
            'jumlah_stok' => 2,
            'description' => 'Alat untuk pengujian hapus akun',
            'jenis' => 'switch',
            'kondisi_baik' => 2,
            'kondisi_pengecekan' => 0,
            'kondisi_rusak' => 0,
            'kondisi_maintenance' => 0,
        ]);

        Pinjam::create([
            'kode_pinjam' => 'SJL-' . strtoupper(Str::random(6)),
            'qr_ambil_token' => Str::random(48),
            'qr_kembali_token' => Str::random(48),
            'user_id' => $user->id,
            'alat_id' => $alat->id,
            'dipinjam_saat' => now()->addHour(),
            'dikembalikan_saat' => now()->addDay(),
            'jumlah_minta' => 1,
            'deskripsi_minta' => 'Praktik jaringan',
            'ruangan' => 'Lab Jaringan',
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'akun')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
