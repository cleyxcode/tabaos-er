<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\FcmV1Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FcmV1ClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fcm.project_id' => 'tabaos-test',
            'services.fcm.credentials' => base_path('tests/fixtures/firebase-service-account.json'),
        ]);

        Cache::flush();
    }

    public function testSendToDeviceUsesFcmHttpV1(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://fcm.googleapis.com/v1/projects/tabaos-test/messages:send' => Http::response([
                'name' => 'projects/tabaos-test/messages/abc123',
            ]),
        ]);

        $client = new FcmV1Client;
        $sent = $client->sendToDevice(
            token: 'device-token-123',
            title: 'Judul Test',
            body: 'Isi pesan test',
            data: ['type' => 'pesan_admin'],
        );

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://fcm.googleapis.com/v1/projects/tabaos-test/messages:send') {
                return false;
            }

            return $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request['message']['token'] === 'device-token-123'
                && $request['message']['notification']['title'] === 'Judul Test'
                && $request['message']['android']['notification']['channel_id'] === 'tabaos_admin';
        });
    }

    public function testSendToDeviceSkipsWhenCredentialsMissing(): void
    {
        config(['services.fcm.credentials' => null]);

        Http::fake();

        $client = new FcmV1Client;
        $sent = $client->sendToDevice('token', 'Title', 'Body');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }
}
