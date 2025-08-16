<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTemplate extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'default_options' => 'array',
    ];
    // Relasi ke Event Induknya
    // public function event(): BelongsTo
    // {
    //     return $this->belongsTo(Event::class);
    // }
}
