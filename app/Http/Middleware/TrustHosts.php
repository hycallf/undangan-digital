<?php
// app/Http/Middleware/TrustHosts.php
// Buat middleware ini jika belum ada

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
            // Tambahkan untuk ngrok
            '*.ngrok-free.app',
            '*.ngrok.io',
            '*.ngrok.app',
            // Untuk development
            'localhost',
            '127.0.0.1',
        ];
    }
}
