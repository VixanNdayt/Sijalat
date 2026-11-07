<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_not_access_admin_routes(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get('/admin/alat')
            ->assertForbidden();

        $this->actingAs($student)
            ->get('/admin/analisis')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/alat')
            ->assertOk();
    }
}
