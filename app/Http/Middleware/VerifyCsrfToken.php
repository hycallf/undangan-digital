<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '*',
    ];

    protected function inExceptArray($request)
    {
        // Check if using ngrok
        if (str_contains($request->url(), 'ngrok')) {
            // Temporarily disable CSRF for ngrok requests
            foreach ($this->except as $except) {
                if ($request->is($except)) {
                    return true;
                }
            }
        }

        return parent::inExceptArray($request);
    }
}
