<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'unique_identifier',
        'name',
        'phone',
        'photo_path',
        'message',
        'qr_code_token',
        'confirmation_status',
        'confirmed_at',
        'invitation_status',
        'invitation_sent_at',
        'attendance_status',
        'check_in_time',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'invitation_sent_at' => 'datetime',
        'check_in_time' => 'datetime',
    ];

    // Relasi ke Event
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // Boot method untuk auto-generate unique_identifier dan qr_code_token
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($guest) {
            // Generate QR code token jika belum ada
            if (empty($guest->qr_code_token)) {
                $guest->qr_code_token = Str::uuid()->toString();
            }

            // Generate unique_identifier jika belum ada
            if (empty($guest->unique_identifier)) {
                $guest->unique_identifier = static::generateUniqueIdentifier($guest->event_id, $guest->name, $guest->phone);
            }
        });

        static::updating(function ($guest) {
            // Regenerate unique_identifier jika nama atau phone berubah
            if ($guest->isDirty(['name', 'phone']) && $guest->unique_identifier) {
                $newIdentifier = static::generateUniqueIdentifier($guest->event_id, $guest->name, $guest->phone, $guest->id);
                if ($newIdentifier !== $guest->unique_identifier) {
                    $guest->unique_identifier = $newIdentifier;
                }
            }
        });
    }

    /**
     * Generate unique identifier untuk guest dengan best practices mengatasi nama sama
     */
    public static function generateUniqueIdentifier(int $eventId, string $name, ?string $phone = null, ?int $excludeId = null): string
    {
        // 1. Bersihkan nama: hapus karakter khusus, convert ke lowercase, replace spasi dengan dash
        $cleanName = Str::slug($name);

        // 2. Jika ada phone, ambil 4 digit terakhir sebagai suffix
        $phoneSuffix = '';
        if ($phone) {
            $cleanPhone = preg_replace('/[^\d]/', '', $phone);
            if (strlen($cleanPhone) >= 4) {
                $phoneSuffix = '-' . substr($cleanPhone, -4);
            }
        }

        // 3. Buat base identifier
        $baseIdentifier = $cleanName . $phoneSuffix;

        // 4. Check apakah sudah unique di dalam event ini
        $query = static::where('event_id', $eventId)
                      ->where('unique_identifier', $baseIdentifier);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if (!$query->exists()) {
            return $baseIdentifier;
        }

        // 5. Jika belum unique, tambahkan counter
        $counter = 2;
        do {
            $identifier = $baseIdentifier . '-' . $counter;

            $query = static::where('event_id', $eventId)
                          ->where('unique_identifier', $identifier);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $identifier;
            }

            $counter++;
        } while ($counter <= 999); // Safety limit

        // 6. Jika masih tidak unique, tambahkan random string
        return $baseIdentifier . '-' . Str::random(6);
    }

    /**
     * Scope untuk mencari guest berdasarkan identifier atau nama
     */
    public function scopeByIdentifier($query, int $eventId, string $identifier)
    {
        return $query->where('event_id', $eventId)
                    ->where(function($q) use ($identifier) {
                        $q->where('unique_identifier', $identifier)
                          ->orWhere('name', $identifier);
                    });
    }

    /**
     * Find guest by identifier dengan fallback ke nama
     */
    public static function findByIdentifier(int $eventId, string $identifier): ?self
    {
        return static::byIdentifier($eventId, $identifier)->first();
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        // Format untuk tampilan Indonesia
        $phone = preg_replace('/[^\d+]/', '', $this->phone);

        if (str_starts_with($phone, '+62')) {
            $number = substr($phone, 3);
            return '0' . $number;
        }

        return $phone;
    }

    /**
     * Get international phone number format
     */
    public function getInternationalPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $this->phone);

        if (str_starts_with($phone, '08')) {
            return '+62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8') && !str_starts_with($phone, '+62')) {
            return '+62' . $phone;
        }

        if (str_starts_with($phone, '62') && !str_starts_with($phone, '+62')) {
            return '+' . $phone;
        }

        return $phone;
    }

    /**
     * Check if guest has valid phone number
     */
    public function hasValidPhone(): bool
    {
        if (!$this->phone) {
            return false;
        }

        $phone = $this->international_phone;
        return preg_match('/^\+62\d{8,13}$/', $phone) === 1;
    }

    /**
     * Get display name with identifier for disambiguation
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;

        // Jika ada duplicate names di event yang sama, tampilkan dengan phone suffix
        $duplicateCount = static::where('event_id', $this->event_id)
                               ->where('name', $this->name)
                               ->where('id', '!=', $this->id)
                               ->count();

        if ($duplicateCount > 0 && $this->phone) {
            $phone = preg_replace('/[^\d]/', '', $this->phone);
            if (strlen($phone) >= 4) {
                $name .= ' (...' . substr($phone, -4) . ')';
            }
        }

        return $name;
    }

    /**
     * Scope untuk filter guest dengan status tertentu
     */
    public function scopeWithConfirmationStatus($query, string $status)
    {
        return $query->where('confirmation_status', $status);
    }

    public function scopeWithAttendanceStatus($query, string $status)
    {
        return $query->where('attendance_status', $status);
    }

    public function scopeWithInvitationStatus($query, string $status)
    {
        return $query->where('invitation_status', $status);
    }

    /**
     * Scope untuk guest yang sudah check-in
     */
    public function scopeCheckedIn($query)
    {
        return $query->where('attendance_status', 'present')
                    ->whereNotNull('check_in_time');
    }

    /**
     * Scope untuk guest yang punya nomor telepon valid
     */
    public function scopeWithValidPhone($query)
    {
        return $query->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->whereRaw("phone REGEXP '^(\+62|62|08)[0-9]{8,13}");
    }

    /**
     * Get invitation URL
     */
    public function getInvitationUrlAttribute(): string
    {
        $identifier = $this->unique_identifier ?: $this->name;

        return route('invitation.show', [
            'event' => $this->event->slug,
            'to' => $identifier
        ]);
    }

    /**
     * Get success URL (after RSVP)
     */
    public function getSuccessUrlAttribute(): string
    {
        return route('invitation.success', [
            'guest' => $this->qr_code_token
        ]);
    }

    /**
     * Get edit RSVP URL
     */
    public function getEditUrlAttribute(): string
    {
        return route('invitation.rsvp.edit', [
            'guest' => $this->qr_code_token
        ]);
    }

    /**
     * Mark as confirmed
     */
    public function markAsConfirmed(): bool
    {
        return $this->update([
            'confirmation_status' => 'confirmed',
            'confirmed_at' => now(),
            'attendance_status' => 'planned'
        ]);
    }

    /**
     * Mark as declined
     */
    public function markAsDeclined(): bool
    {
        return $this->update([
            'confirmation_status' => 'declined',
            'confirmed_at' => now(),
            'attendance_status' => 'absent'
        ]);
    }

    /**
     * Mark as checked in
     */
    public function checkIn(): bool
    {
        return $this->update([
            'attendance_status' => 'present',
            'check_in_time' => now()
        ]);
    }

    /**
     * Check if guest can be checked in
     */
    public function canCheckIn(): bool
    {
        return $this->attendance_status !== 'present';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->confirmation_status) {
            'confirmed' => 'green',
            'declined' => 'red',
            'pending' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get attendance status color for UI
     */
    public function getAttendanceColorAttribute(): string
    {
        return match($this->attendance_status) {
            'present' => 'green',
            'planned' => 'yellow',
            'absent' => 'red',
            default => 'gray'
        };
    }
}
