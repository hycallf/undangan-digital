<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    /**
     * Get the user that owns the event.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guests for the event.
     */
    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * Generate a unique slug for the event.
     */
    public function generateSlug()
    {
        $this->slug = Str::slug($this->groom_name . '-dan-' . $this->bride_name . '-' . uniqid());
        $this->save();
    }

    /**
     * Get the cover image URL.
     */
    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image_path ? asset('storage/' . $this->cover_image_path) : null;
    }

    /**
     * Get the primary color for the event.
     */
    public function getPrimaryColorAttribute()
    {
        return $this->primary_color ?: '#4a4a4a'; // Default color if not set
    }

    /**
     * Get the music URL for the event.
     */
    public function getMusicUrlAttribute()
    {
        return $this->music_url ? asset('storage/' . $this->music_url) : null;
    }
}
