<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use App\Enums\EventTheme;

class Event extends Model
{
    use HasFactory;
    protected $guarded = []; // Atau gunakan $fillable

    // Relasi ke Pemilik
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    // Relasi ke Template Desain
    public function template(): BelongsTo { return $this->belongsTo(EventTemplate::class, 'event_template_id'); }

    // Relasi ke Mempelai Pria
    public function groom(): BelongsTo { return $this->belongsTo(Partner::class, 'groom_partner_id'); }

    // Relasi ke Mempelai Wanita
    public function bride(): BelongsTo { return $this->belongsTo(Partner::class, 'bride_partner_id'); }

    // Relasi ke Detail Konten (One-to-One)
    public function details(): HasOne { return $this->hasOne(EventDetail::class); }

    // Relasi ke Rangkaian Acara (One-to-Many)
    public function ceremonies(): HasMany { return $this->hasMany(Ceremony::class); }

    // Relasi ke Foto Galeri (One-to-Many)
    public function galleryPhotos(): HasMany { return $this->hasMany(GalleryPhoto::class); }

    // Relasi ke Tamu (One-to-Many)
    public function guests(): HasMany { return $this->hasMany(Guest::class); }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->orderBy('order');
    }

    protected $casts = [
        'theme' => EventTheme::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($event) {
            // Hapus seluruh folder event dari storage
            if ($event->storage_path) {
                Storage::disk('public')->deleteDirectory($event->storage_path);
            }

            // Hapus juga data partner terkait jika tidak digunakan oleh event lain
            // (Logika ini bisa ditambahkan jika perlu)
            // $event->groom()->delete();
            // $event->bride()->delete();
        });
    }
}
