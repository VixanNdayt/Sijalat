<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('data-custom-select', false);
        $response->assertSee('js/custom-select.js', false);
    }

    public function test_new_users_can_register_with_school_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'nis' => '123456',
            'kelas' => 'XI SIJA A',
            'email' => 'test@student.stembayo.sch.id',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_can_not_register_with_non_school_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'nis' => '654321',
            'kelas' => 'XI SIJA A',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_users_can_not_register_with_kelas_di_luar_daftar(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'nis' => '654322',
            'kelas' => 'Kelas Palsu',
            'email' => 'test3@student.stembayo.sch.id',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('kelas');
        $this->assertGuest();
    }

    public function test_users_can_not_register_with_nis_terlalu_panjang(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'nis' => str_repeat('1', 31),
            'kelas' => 'XI SIJA A',
            'email' => 'test4@student.stembayo.sch.id',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('nis');
        $this->assertGuest();
    }

    public function test_new_user_can_open_dashboard_without_email_verification(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'nis' => '123457',
            'kelas' => 'XI SIJA A',
            'email' => 'test2@student.stembayo.sch.id',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertAuthenticated();
        $this->assertNull(auth()->user()->email_verified_at);

        $this->get('/dashboard')->assertOk();
    }

    public function test_email_verification_routes_are_not_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/verify-email')->assertNotFound();
    }
}
