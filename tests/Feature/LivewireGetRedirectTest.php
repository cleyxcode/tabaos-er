<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class LivewireGetRedirectTest extends TestCase
{
    public function testGetLivewireUpdateRouteRedirectsToAdmin(): void
    {
        $response = $this->get('/livewire-74e9ffa2/update');

        $response->assertRedirect('/admin');
    }

    public function testGetLivewireUpdateRouteRedirectsToRefererWhenPresent(): void
    {
        $response = $this
            ->withHeader('Referer', url('/admin/peta-realtime'))
            ->get('/livewire-74e9ffa2/update');

        $response->assertRedirect('/admin/peta-realtime');
    }
}
