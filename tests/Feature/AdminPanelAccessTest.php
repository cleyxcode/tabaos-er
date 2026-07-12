<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminPagesProvider(): array
    {
        return [
            'dashboard' => ['/admin'],
            'peta realtime' => ['/admin/peta-realtime'],
            'laporan bencana' => ['/admin/laporan-bencanas'],
            'relawan' => ['/admin/relawans'],
            'faskes' => ['/admin/faskes'],
            'wilayah' => ['/admin/wilayahs'],
            'zona rawan bencana' => ['/admin/zona-rawan-bencanas'],
            'petugas emergency' => ['/admin/petugas-emergencies'],
            'warga mobile app' => ['/admin/penggunas'],
            'pedoman bhd' => ['/admin/pedoman-bhds'],
            'notifikasi admin' => ['/admin/notifikasi-admins'],
            'kelola admin' => ['/admin/users'],
        ];
    }

    #[DataProvider('adminPagesProvider')]
    public function testAdminCanAccessMainPanelPages(string $url): void
    {
        $this->actingAs($this->admin)
            ->get($url)
            ->assertOk();
    }

    public function testGuestIsRedirectedFromAdminPanel(): void
    {
        $this->get('/admin/laporan-bencanas')
            ->assertRedirect('/admin/login');
    }

    public function testMergedResourcesAreHiddenFromNavigation(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Penugasan', false);
        $response->assertDontSee('Ambulans', false);
        $response->assertDontSee('Akun Relawan', false);
        $response->assertDontSee('Akun Faskes', false);
    }
}
