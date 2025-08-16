<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $guarded = [];
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
