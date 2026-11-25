<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
        $this->assertStringNotContainsString('https://unpkg.com', $csp);
        $this->assertStringNotContainsString('https://cdn.jsdelivr.net', $csp);
        $this->assertStringNotContainsString('https://api.qrserver.com', $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString('frame-ancestors \'self\'', $csp);

        $this->assertStringContainsString(
            'camera=(self)',
            (string) $response->headers->get('Permissions-Policy')
        );
    }

    public function test_halaman_aplikasi_tidak_memuat_library_yang_membutuhkan_eval(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('profile.edit'));

        $response->assertOk()
            ->assertSee('js/ui.js', false)
            ->assertDontSee('alpine.min.js', false);
    }

    public function test_halaman_pindai_qr_tetap_dapat_dibuka_siswa(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('transaksi.scan'))
            ->assertOk();
    }

    public function test_csp_tidak_mengizinkan_script_dan_style_inline(): void
    {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        preg_match('/script-src([^;]+)/', $csp, $script);
        preg_match('/style-src([^;]+)/', $csp, $style);

        $this->assertNotEmpty($script);
        $this->assertNotEmpty($style);

        $this->assertStringNotContainsString("'unsafe-inline'", $script[1]);
        $this->assertStringNotContainsString('data:', $script[1]);
        $this->assertStringNotContainsString("'unsafe-inline'", $style[1]);
        $this->assertStringNotContainsString('data:', $style[1]);
    }

    public function test_tidak_ada_atribut_style_inline_pada_view(): void
    {
        $temuan = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $isi = (string) file_get_contents($file->getPathname());

            if (preg_match('/style\s*=\s*["\']/', $isi)) {
                $temuan[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $temuan, 'Masih ada inline style di: '.implode(', ', $temuan));
    }

    public function test_tidak_ada_inline_script_dan_event_handler_pada_view(): void
    {
        $temuan = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $isi = (string) file_get_contents($file->getPathname());
            $nama = $file->getRelativePathname();

            if (preg_match('/<script(?![^>]*\bsrc=)(?![^>]*type="application\/json")/i', $isi)) {
                $temuan[] = $nama.' (script inline)';
            }

            if (preg_match('/\son(click|change|submit|input|load|error|keyup|keydown)\s*=/i', $isi)) {
                $temuan[] = $nama.' (event handler inline)';
            }
        }

        $this->assertSame([], $temuan, 'Masih ada inline script/handler di: '.implode(', ', $temuan));
    }
}
