<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

use App\Models\Event;
use App\Models\EventDetail;
use App\Models\Partner;
use App\Models\GalleryPhoto;
use App\Models\Quote;
use App\Models\Guest;
use App\Models\User;
use App\Models\Ceremony;
use App\Enums\EventTheme;
use App\Models\EventTemplate;

class EventController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil event terbaru milik user yang sedang login
        $events = Auth::user()->events()
        // 1. TAMBAHKAN 'ceremonies' ke dalam eager loading
        ->with(['groom', 'bride', 'ceremonies'])

        // 2. UBAH LOGIKA FILTER TANGGAL
        ->when($request->input('date'), function ($query, $date) {
            // Filter event yang MEMILIKI ACARA (ceremonies) pada tanggal yang dipilih
            $query->whereHas('ceremonies', function ($subQuery) use ($date) {
                $subQuery->whereDate('ceremony_date', $date);
            });
        })

        // ... logika pencarian nama tidak berubah ...
        ->when($request->input('search'), function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('groom', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('nickname', 'like', "%{$search}%");
                })
                ->orWhereHas('bride', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('nickname', 'like', "%{$search}%");
                });
            });
        })

        ->latest()
        ->get();

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => $request->only(['search','date']), // Kirim kembali filter ke view
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Events/Create',[
            'themeOptions' => EventTheme::values(),
            'templateOptions' => EventTemplate::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi akan lebih kompleks, kita sederhanakan untuk contoh ini
        $request->validate([
            'cover_photo' => 'required|image|max:2048',
            'groom.name' => 'required|string',
            'groom.nickname' => 'required|string',
            'groom.phone_number' => 'nullable|string',
            'groom.email' => 'nullable|email',
            'groom.photo' => 'required|image|max:2048',
            'groom.family_details' => 'nullable|string',
            'groom.social_media' => 'nullable|array',
            'bride.name' => 'required|string',
            'bride.nickname' => 'required|string',
            'bride.phone_number' => 'nullable|string',
            'bride.email' => 'nullable|email',
            'bride.photo' => 'required|image|max:2048',
            'bride.family_details' => 'nullable|string',
            'bride.social_media' => 'nullable|array',
            'groom.social_media.instagram' => ['nullable', 'url'],
            'groom.social_media.facebook' => ['nullable', 'url'],
            'groom.social_media.twitter' => ['nullable', 'url'],
            'bride.social_media.instagram' => ['nullable', 'url'],
            'bride.social_media.facebook' => ['nullable', 'url'],
            'bride.social_media.twitter' => ['nullable', 'url'],
            'details.opening_text' => 'nullable|string',
            'details.story_text' => 'nullable|string',
            'details.closing_text' => 'nullable|string',
            'details.music' => 'nullable|file|max:5120', // Maksimal 5MB
            'ceremonies.*.name' => 'required|string', // Validasi array
            'ceremonies.*.ceremony_date' => 'required|date',
            'ceremonies.*.start_time' => 'required|date_format:H:i',
            'ceremonies.*.location' => 'required|string',
            'ceremonies.*.address' => 'required|string',
            'ceremonies.*.timezone' => 'required|string',
            'ceremonies.*.maps_url' => 'nullable|url',
            'gallery_photos' => 'nullable|array',
            'gallery_photos.*' => 'nullable|image|max:2048',
            'gallery_items_order' => ['nullable', 'array'],
            'gallery_items_order.*.caption' => ['nullable', 'string', 'max:255'],
            'gallery_items_order.*.id' => ['nullable', 'integer', 'exists:gallery_photos,id'],
            'quotes' => ['nullable', 'array'],
            'quotes.*.content' => ['required_with:quotes.*.source', 'string'],
            'theme' => ['required', 'string', 'in:islamic,general,christian,hindu,buddha,chinese'],
            'event_template_id' => ['required', 'integer', 'exists:event_templates,id'],
            // Tambahkan validasi lain sesuai kebutuhan
        ]);

        try {
            // Mulai Database Transaction
            DB::beginTransaction();

            // 1. BUAT NAMA FOLDER UNIK UNTUK EVENT INI
            $slug = Str::slug($request->input('groom.nickname') . '-dan-' . $request->input('bride.nickname'));
            $eventFolderPath = $slug . '-' . uniqid();

            // 2. Proses data Mempelai (Partner) dengan path baru
            $groomData = $request->input('groom');
            if ($request->hasFile('groom.photo')) {
                $groomFile = $request->file('groom.photo');
                $groomFileName = 'groom-' . uniqid() . '.' . $groomFile->extension();
                // Simpan ke: {eventFolderPath}/event/groom-xxxx.jpg
                $groomData['photo_path'] = $groomFile->storeAs($eventFolderPath . '/event', $groomFileName, 'public');
            }
            $groom = Partner::create($groomData);

            $brideData = $request->input('bride');
            if ($request->hasFile('bride.photo')) {
                $brideFile = $request->file('bride.photo');
                $brideFileName = 'bride-' . uniqid() . '.' . $brideFile->extension();
                $brideData['photo_path'] = $brideFile->storeAs($eventFolderPath . '/event', $brideFileName, 'public');
            }
            $bride = Partner::create($brideData);

            // 3. Simpan foto sampul utama dengan path baru
            $coverFile = $request->file('cover_photo');
            $coverFileName = 'cover-' . uniqid() . '.' . $coverFile->extension();
            $coverPhotoPath = $coverFile->storeAs($eventFolderPath . '/event', $coverFileName, 'public');

            // 4. Buat Event utama dan SIMPAN STORAGE_PATH
            $event = Event::create([
                'user_id' => Auth::id(),
                'groom_partner_id' => $groom->id,
                'bride_partner_id' => $bride->id,
                'slug' => $slug,
                'storage_path' => $eventFolderPath, // <-- SIMPAN PATH UNIKNYA
                'cover_photo_path' => $coverPhotoPath,
                'theme' => $request->input('theme'),
                'event_template_id' => $request->input('event_template_id'),
            ]);

            // 5. Buat Event Details dengan path baru untuk musik
            $detailData = $request->input('details');
            if ($request->hasFile('details.music')) {
                $musicFile = $request->file('details.music');
                $musicFileName = 'music-' . uniqid() . '.' . $musicFile->extension();
                $detailData['music_path'] = $musicFile->storeAs($eventFolderPath . '/event', $musicFileName, 'public');
            }
            $event->details()->create($detailData);

            // 6. Buat Ceremonies (tidak ada file, tidak berubah)
            foreach ($request->input('ceremonies', []) as $ceremonyData) {
                $filteredData = Arr::except($ceremonyData, ['use_previous_details', 'is_until_finished']);

                // Pastikan end_time adalah null jika kosong
                if (empty($filteredData['end_time'])) {
                    $filteredData['end_time'] = null;
                }

                $event->ceremonies()->create($filteredData);
            }

            foreach ($request->input('quotes', []) as $index => $quoteData) {
                if (!empty($quoteData['content'])) {
                    $event->quotes()->create([
                        'content' => $quoteData['content'],
                        'source' => $quoteData['source'],
                        'order' => $index,
                    ]);
                }
            }
            // 7. Buat Gallery Photos dengan path baru
            if ($request->hasFile('gallery_photos')) {
                // Kita sudah mengirim captions dalam gallery_items_order
                $galleryItems = $request->input('gallery_items_order', []);

                foreach ($request->file('gallery_photos') as $index => $file) {
                    $path = $file->store($event->storage_path . '/event/gallery', 'public');

                    $event->galleryPhotos()->create([
                        'photo_path' => $path,
                        // Ambil caption dari item yang sesuai dengan index
                        'caption' => $galleryItems[$index]['caption'] ?? null,
                        'order' => $index,
                    ]);
                }
            }

            // Jika semua berhasil, commit transaksi
            DB::commit();

        } catch (\Exception $e) {
            // Jika ada satu saja yang gagal, batalkan semua yang sudah tersimpan
            DB::rollBack();
            // Kembalikan ke halaman form dengan pesan error
            return back()->withErrors(['error' => 'Gagal menyimpan event: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.events.index')->with('success', 'Event baru berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        $event->load(['groom', 'bride', 'details', 'ceremonies', 'quotes', 'galleryPhotos']);

        // dd($event->galleryPhotos);

        return Inertia::render('Admin/Events/Edit', [
            'event' => $event,
            'themeOptions' => EventTheme::values(),
            'templateOptions' => EventTemplate::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'cover_photo' => 'nullable|image|max:2048',
            'groom.name' => 'required|string',
            'groom.nickname' => 'required|string',
            'groom.phone_number' => 'nullable|string',
            'groom.email' => 'nullable|email',
            'groom.photo' => 'nullable|image|max:2048',
            'groom.family_details' => 'nullable|string',
            'groom.social_media' => 'nullable|array',
            'bride.name' => 'required|string',
            'bride.nickname' => 'required|string',
            'bride.phone_number' => 'nullable|string',
            'bride.email' => 'nullable|email',
            'bride.photo' => 'nullable|image|max:2048',
            'bride.family_details' => 'nullable|string',
            'bride.social_media' => 'nullable|array',
            'groom.social_media.instagram' => ['nullable', 'url'],
            'groom.social_media.facebook' => ['nullable', 'url'],
            'groom.social_media.twitter' => ['nullable', 'url'],
            'bride.social_media.instagram' => ['nullable', 'url'],
            'bride.social_media.facebook' => ['nullable', 'url'],
            'bride.social_media.twitter' => ['nullable', 'url'],
            'details.opening_text' => 'nullable|string',
            'details.story_text' => 'nullable|string',
            'details.closing_text' => 'nullable|string',
            'details.music' => 'nullable|file|max:5120', // Maksimal 5MB
            'ceremonies.*.name' => 'required|string', // Validasi array
            'ceremonies.*.ceremony_date' => 'required|date',
            'ceremonies.*.start_time' => 'required|date_format:H:i,H:i:s',
            'ceremonies.*.end_time' => 'nullable|date_format:H:i,H:i:s',
            'ceremonies.*.location' => 'required|string',
            'ceremonies.*.address' => 'required|string',
            'ceremonies.*.timezone' => 'required|string',
            'ceremonies.*.maps_url' => 'nullable|url',
            'gallery_photos' => 'nullable|array',
            'gallery_photos.*' => 'nullable|image|max:2048',
            'gallery_items_order' => ['nullable', 'array'],
            'gallery_items_order.*.caption' => ['nullable', 'string', 'max:255'],
            'gallery_items_order.*.id' => ['nullable', 'integer', 'exists:gallery_photos,id'],
            'quotes' => ['nullable', 'array'],
            'quotes.*.content' => ['required_with:quotes.*.source', 'string'],
            'theme' => ['required', 'string', 'in:islamic,general,christian,hindu,buddha,chinese'],
            'event_template_id' => ['required', 'integer', 'exists:event_templates,id'],
            // Tambahkan validasi lain sesuai kebutuhan
        ]);

        try {
            DB::transaction(function () use ($request, $event) {
                // 1. HAPUS FOTO GALERI YANG DITANDAI
                if ($request->input('deleted_gallery_ids')) {
                    $photosToDelete = GalleryPhoto::whereIn('id', $request->input('deleted_gallery_ids'))->get();
                    foreach ($photosToDelete as $photo) {
                        Storage::disk('public')->delete($photo->photo_path);
                        $photo->delete();
                    }
                }

                // 2. UPDATE DATA MEMPELAI (PARTNERS)
                $groomData = $request->input('groom');
                if ($request->hasFile('groom.photo')) {
                    Storage::disk('public')->delete($event->groom->photo_path);
                    $groomData['photo_path'] = $request->file('groom.photo')->store($event->storage_path . '/event', 'public');
                }
                $event->groom->update($groomData);

                $brideData = $request->input('bride');
                if ($request->hasFile('bride.photo')) {
                    Storage::disk('public')->delete($event->bride->photo_path);
                    $brideData['photo_path'] = $request->file('bride.photo')->store($event->storage_path . '/event', 'public');
                }
                $event->bride->update($brideData);

                // 3. UPDATE EVENT UTAMA (TERMASUK FOTO SAMPUL)
                $eventData = $request->only(['theme', 'event_template_id']);
                if ($request->hasFile('cover_photo')) {
                    Storage::disk('public')->delete($event->cover_photo_path);
                    $eventData['cover_photo_path'] = $request->file('cover_photo')->store($event->storage_path . '/event', 'public');
                }
                $event->update($eventData);

                // 4. UPDATE EVENT DETAILS (TERMASUK MUSIK)
                $detailData = $request->input('details');
                if ($request->hasFile('details.music')) {
                    Storage::disk('public')->delete($event->details->music_path);
                    $detailData['music_path'] = $request->file('details.music')->store($event->storage_path . '/event', 'public');
                }
                $event->details()->updateOrCreate([], $detailData);

                // 5. UPDATE RELASI ONE-TO-MANY (HAPUS LAMA & BUAT BARU - CARA SEDERHANA)
                $event->ceremonies()->delete();
                foreach ($request->input('ceremonies', []) as $ceremonyData) {
                    $event->ceremonies()->create(Arr::except($ceremonyData, ['use_previous_details']));
                }

                $event->quotes()->delete();
                foreach ($request->input('quotes', []) as $index => $quoteData) {
                    if (!empty($quoteData['content'])) {
                        $event->quotes()->create(['order' => $index] + $quoteData);
                    }
                }

                // 6. UPDATE & TAMBAH FOTO GALERI BARU
                $galleryItems = $request->input('gallery_items_order', []);
                $newGalleryFiles = $request->file('gallery_photos', []);
                $newFileIndex = 0; // Index untuk melacak file baru

                foreach ($galleryItems as $order => $item) {
                    if (!empty($item['id'])) {
                        // Ini FOTO LAMA, update caption dan urutannya
                        GalleryPhoto::where('id', $item['id'])->update([
                            'caption' => $item['caption'],
                            'order' => $order,
                        ]);
                    } else {
                        // Ini FOTO BARU, buat entri baru di database
                        if (isset($newGalleryFiles[$newFileIndex])) {
                            $file = $newGalleryFiles[$newFileIndex];
                            $path = $file->store($event->storage_path . '/event/gallery', 'public');

                            $event->galleryPhotos()->create([
                                'photo_path' => $path,
                                'caption' => $item['caption'] ?? null,
                                'order' => $order,
                            ]);

                            $newFileIndex++; // Lanjut ke file baru berikutnya
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['db_error' => 'Gagal memperbarui event: ' . $e->getMessage()]);
        }
        return redirect()->route('admin.events.index')->with('success', 'Event baru berhasil dibuat!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);
        $event->delete();
        return redirect()->route('admin.events.index')->with('message', 'Event berhasil dihapus.');
    }
}
