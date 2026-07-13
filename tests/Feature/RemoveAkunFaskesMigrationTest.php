<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class RemoveAkunFaskesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function testAkunFaskesTableAndNotificationFaskesColumnsAreRemoved(): void
    {
        $this->assertFalse(Schema::hasTable('akun_faskes'));
        $this->assertFalse(Schema::hasColumn('notifikasi_admin', 'kirim_ke_faskes'));
        $this->assertFalse(Schema::hasColumn('notifikasi_admin', 'kirim_semua_faskes'));
        $this->assertFalse(Schema::hasColumn('notifikasi_admin', 'akun_faskes_ids'));
    }
}
