<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RedirectLivewireGetRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $this->isLivewireInternalRoute($request->path())) {
            $redirect = $request->headers->get('referer') ?: url('/admin');

            return redirect()->to($redirect);
        }

        return $next($request);
    }

    private function isLivewireInternalRoute(string $path): bool
    {
        return (bool) preg_match('#^livewire-[a-f0-9]+/(update|upload)$#', $path);
    }
}
