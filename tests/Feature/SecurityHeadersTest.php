<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_keamanan_dipasang_pada_respons(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('https://unpkg.com', $csp);
        $this->assertStringContainsString('https://api.qrserver.com', $csp);
        $this->assertStringContainsString('frame-ancestors \'self\'', $csp);

        $this->assertStringContainsString(
            'camera=(self)',
            (string) $response->headers->get('Permissions-Policy')
        );
    }

    public function test_halaman_pindai_qr_tetap_dapat_dibuka_siswa(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('transaksi.scan'))
            ->assertOk();
    }
}
