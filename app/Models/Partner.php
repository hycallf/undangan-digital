<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Relasi ke Event Induknya
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected $casts = [
        'social_media' => 'array',
    ];
}
