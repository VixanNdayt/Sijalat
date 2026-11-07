<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
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
        $response->assertRedirect(route('verification.notice', absolute: false));
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
}
