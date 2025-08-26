<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Services\FonnteService;
use App\Enums\EventTheme;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    private FonnteService $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    /**
     * Kirim undangan WhatsApp ke multiple guests
     */
    public function sendBulkInvitations(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'guest_ids' => 'required|array|min:1',
            'guest_ids.*' => 'required|integer|exists:guests,id',
            'custom_message' => 'nullable|string|max:4096',
        ]);

        try {
            $event->load(['groom', 'bride', 'ceremonies']);

            if (!$event->theme) {
                return redirect()->back()->withErrors(['error' => 'Gagal mengirim: Event belum memiliki tema.']);
            }
            if ($event->ceremonies->isEmpty()) {
                return redirect()->back()->withErrors(['error' => 'Gagal mengirim: Event belum memiliki jadwal acara.']);
            }

            $mainCeremony = $event->ceremonies->sortBy('ceremony_date')->first();
            $eventData = $this->prepareEventData($event, $mainCeremony);

            $guests = Guest::whereIn('id', $request->input('guest_ids'))->where('event_id', $event->id)->get();
            $sentCount = 0;
            $failedCount = 0;

            DB::beginTransaction();

            foreach ($guests as $guest) {
                if (empty($guest->phone) || !$this->fonnteService->validatePhoneNumber($guest->phone)) {
                    $guest->update(['invitation_status' => 'failed']);
                    $failedCount++;
                    continue;
                }

                // Hasilkan link unik untuk tamu ini
                $eventData['invitation_link'] = route('invitation.show', ['event' => $event->slug, 'to' => $guest->unique_identifier]);

                // Hasilkan pesan berdasarkan template atau custom message
                $message = $this->generateMessage($request->input('custom_message'), $event, $eventData);
                $personalizedMessage = "Kepada Yth.\nBapak/Ibu/Saudara/i\n*{$guest->name}*\n\n" . $message;

                // Kirim pesan
                $result = $this->fonnteService->sendMessage($guest->phone, $personalizedMessage);

                if ($result['success']) {
                    $guest->update(['invitation_status' => 'sent', 'invitation_sent_at' => now()]);
                    $sentCount++;
                } else {
                    $guest->update(['invitation_status' => 'failed']);
                    $failedCount++;
                }
                sleep(1); // Jeda 1 detik antar pesan
            }

            DB::commit();

            return redirect()->back()->with('success', "Proses pengiriman selesai. {$sentCount} berhasil, {$failedCount} gagal.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk invitation sending failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Gagal mengirim undangan: Terjadi error internal.']);
        }
    }

    /**
     * Generate message berdasarkan custom message atau template default
     */
    private function generateMessage(Request $request, Event $event, array $eventData): string
    {
        $customMessage = $request->input('custom_message');

        if ($customMessage && !empty(trim(strip_tags($customMessage)))) {
            return $this->replacePlaceholders($customMessage, $eventData);
        }

        $eventTheme = $event->theme; // Ambil enum langsung dari model
        return $eventTheme->getWhatsAppTemplate($eventData);
    }

    /**
     * Replace placeholder dalam custom message
     */
    private function replacePlaceholders(string $message, array $eventData): string
    {
        $placeholders = [
            '{groom_name}' => $eventData['groom_name'],
            '{groom_nickname}' => $eventData['groom_nickname'],
            '{bride_name}' => $eventData['bride_name'],
            '{bride_nickname}' => $eventData['bride_nickname'],
            '{ceremony_date}' => $eventData['ceremony_date'],
            '{ceremony_time}' => $eventData['ceremony_time'],
            '{ceremony_location}' => $eventData['ceremony_location'],
            '{ceremony_address}' => $eventData['ceremony_address'],
            '{invitation_link}' => $eventData['invitation_link'],
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $message);
    }

    /**
     * Get template message berdasarkan theme untuk CKEditor
     */
    public function getMessageTemplate(Event $event)
    {
        $this->authorize('view', $event);

        try {
            $event->load(['groom', 'bride', 'ceremonies']);

            // Validasi 1: Pastikan event punya tema
            if (!$event->theme) {
                return response()->json(['success' => false, 'message' => 'Event ini belum memiliki tema. Silakan atur tema di halaman edit event.'], 422);
            }
            // Validasi 2: Pastikan event punya jadwal acara
            if ($event->ceremonies->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Event belum memiliki jadwal acara. Silakan tambahkan ceremony terlebih dahulu.'], 422);
            }

            $mainCeremony = $event->ceremonies->sortBy('ceremony_date')->first();
            $eventData = $this->prepareEventData($event, $mainCeremony);

            $eventTheme = $event->theme;
            $template = $eventTheme->getWhatsAppTemplate($eventData);


            return response()->json([
                'success' => true,
                'template' => $template,
                'placeholders' => [
                    '{groom_name}' => $eventData['groom_name'],
                    '{groom_nickname}' => $eventData['groom_nickname'],
                    '{bride_name}' => $eventData['bride_name'],
                    '{bride_nickname}' => $eventData['bride_nickname'],
                    '{ceremony_date}' => $eventData['ceremony_date'],
                    '{ceremony_time}' => $eventData['ceremony_time'],
                    '{ceremony_location}' => $eventData['ceremony_location'],
                    '{ceremony_address}' => $eventData['ceremony_address'],
                    '{invitation_link}' => 'Link undangan personal untuk setiap tamu',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Message Template Error', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi error internal saat memuat template.'], 500);
        }
    }

    /**
     * Prepare event data untuk template
     */
    private function prepareEventData(Event $event, $ceremony): array
    {
        // Format tanggal dalam Bahasa Indonesia
        $ceremonyDate = Carbon::parse($ceremony->ceremony_date)->locale('id');
        $ceremonyTime = Carbon::parse($ceremony->start_time)->format('H:i');

        if ($ceremony->end_time) {
            $endTime = Carbon::parse($ceremony->end_time)->format('H:i');
            $ceremonyTime .= ' - ' . $endTime;
        }
        $ceremonyTime .= ' WIB';

        return [
            'groom_name' => $event->groom->name,
            'groom_nickname' => $event->groom->nickname,
            'bride_name' => $event->bride->name,
            'bride_nickname' => $event->bride->nickname,
            'ceremony_date' => $ceremonyDate->translatedFormat('l, d F Y'),
            'ceremony_time' => $ceremonyTime,
            'ceremony_location' => $ceremony->location,
            'ceremony_address' => $ceremony->address,
            'invitation_link' => '', // Will be filled per guest
        ];
    }

    /**
     * Generate unique invitation link untuk guest menggunakan unique_identifier
     */
    private function generateInvitationLink(Event $event, Guest $guest): string
    {
        // Gunakan unique_identifier jika ada, jika tidak gunakan nama
        $identifier = $guest->unique_identifier ?: $guest->name;

        return route('invitation.show', [
            'event' => $event->slug,
            'to' => $identifier
        ]);
    }

    /**
     * Personalize message dengan nama tamu
     */
    private function personalizeMessage(string $message, Guest $guest): string
    {
        // Tambahkan sapaan personal di awal
        $greeting = "Kepada Yth.\n*{$guest->name}*\n\n";

        return $greeting . $message;
    }

    /**
     * Preview message untuk testing
     */
    public function previewMessage(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $request->validate([
            'guest_id' => 'required|integer|exists:guests,id',
            'custom_message' => 'nullable|string|max:2000',
        ]);

        try {
            $event->load(['groom', 'bride', 'ceremonies']);
            $guest = Guest::findOrFail($request->input('guest_id'));

            if ($guest->event_id !== $event->id) {
                return response()->json(['error' => 'Guest tidak belongs ke event ini'], 400);
            }

            if ($event->ceremonies->isEmpty()) {
                return response()->json(['error' => 'Event belum memiliki ceremony'], 400);
            }

            $mainCeremony = $event->ceremonies->first();
            $eventData = $this->prepareEventData($event, $mainCeremony);
            $eventData['invitation_link'] = $this->generateInvitationLink($event, $guest);

            $message = $this->generateMessage($request, $event, $eventData);
            $personalizedMessage = $this->personalizeMessage($message, $guest);

            return response()->json([
                'success' => true,
                'message' => $personalizedMessage,
                'guest_name' => $guest->name,
                'guest_phone' => $guest->phone,
                'phone_valid' => $this->fonnteService->validatePhoneNumber($guest->phone ?? ''),
                'formatted_phone' => $this->fonnteService->getFormattedPhoneNumber($guest->phone ?? '')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Fonnte balance
     */
    public function checkBalance()
    {
        try {
            $balance = $this->fonnteService->checkBalance();

            return response()->json($balance);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
