<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $guarded = [];

    /**
     * Get the event that the guest belongs to.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Generate a unique QR code token for the guest.
     */
    public function generateQrCodeToken()
    {
        $this->qr_code_token = Str::uuid()->toString();
        $this->save();
    }
}
