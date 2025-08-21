<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import draggable from 'vuedraggable';
import themeData from '@/Data/themeContents.js';
import CKEditor from '@/Components/CKEditor.vue'; // Impor editor baru kita
import PrimaryButton from '@/Components/PrimaryButton.vue'; // Impor tombol baru kita
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import FileInput from '@/Components/FileInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextareaInput from '@/Components/TextArea.vue';
import ImageSelect from '@/Components/ImageSelect.vue';
// Anda mungkin butuh ikon untuk tombol
// import { faPlus, faTrash } from '@fortawesome/free-solid-svg-icons';

// State untuk tab yang aktif
const activeTab = ref('infoDasar');
const galleryItems = ref([]);

const props = defineProps({
    event: {
        type: Object,
        default: null, // Default null untuk mode Create
    },
    themeOptions: Array,
    templateOptions: Array,
});

const isEditMode = computed(() => !!props.event);
const previews = ref({
    cover: null,
    groomPhoto: null,
    bridePhoto: null,
});

// --- FORM INITIALIZATION ---
const emptyEvent = {
    theme: 'general',
    groom: { name: '', nickname: '', photo: null, family_details: '<p>Putra ke-1<br> Bapak ... <br>&<br> Ibu ...</p>', social_media: { instagram: '', facebook: '', twitter: '' } },
    bride: { name: '', nickname: '', photo: null, family_details: '<p>Putri ke-1<br> Bapak ... <br>&<br> Ibu ...</p>', social_media: { instagram: '', facebook: '', twitter: '' } },
    details: {
        opening_text: '',
        story_text: `<p><strong>Love Story Timeline</strong><br>
        <em>(Silakan edit sesuai perjalanan cinta kalian, bisa tambahkan foto/video)</em></p>
        <ol>
          <li><strong>2017 – Pertama Bertemu</strong><br>
          Kami pertama kali bertemu saat acara ospek kampus.</li>
          <li><strong>2018 – Pertemanan yang Dekat</strong><br>
          Mulai sering ngobrol, belajar bareng, hingga akhirnya menjadi sahabat baik.</li>
          <li><strong>2019 – Jalan Berdua Pertama Kali</strong><br>
          Momen spesial kami di pantai, penuh tawa dan cerita indah.<br>
          <em>(bisa sisipkan foto/video di sini)</em></li>
          <li><strong>2021 – Lamaran</strong><br>
          Dengan restu orang tua, kami mengikat janji untuk melangkah bersama.</li>
          <li><strong>2025 – Hari Bahagia</strong><br>
          Akhirnya, perjalanan panjang ini membawa kami menuju pernikahan.</li>
        </ol>`,
        closing_text: '',
        music: null
    },
    ceremonies: [{ name: 'Akad Nikah', ceremony_date: '', start_time: '08:00', end_time: '10:00', location: '', address: '', maps_url: '', timezone: 'WIB', is_until_finished: false, use_previous_details: false }],
    quotes: [{ content: '', source: '' }],
    gallery_photos: [], // Penting: pastikan properti ini ada
};

const eventData = props.event ? JSON.parse(JSON.stringify(props.event)) : emptyEvent;

const form = useForm({
    theme: eventData.theme,
    cover_photo: null,
    groom: eventData.groom,
    bride: eventData.bride,
    details: eventData.details,
    ceremonies: eventData.ceremonies,
    quotes: eventData.quotes,
    gallery_photos: [], // Selalu kosong di awal, untuk file BARU
    gallery_items_order: [],
    deleted_gallery_ids: [],
    gallery_changed: false,
    event_template_id: props.event?.event_template_id ?? (props.templateOptions?.[0]?.id || null),

});

onMounted(() => {
    // 3. Pengecekan keamanan ganda di onMounted
    if (isEditMode.value && eventData.gallery_photos && eventData.gallery_photos.length > 0) {
        galleryItems.value = eventData.gallery_photos.map(photo => ({
            id: photo.id,
            file: null,
            preview: `/storage/${photo.photo_path}`,
            caption: photo.caption || ''
        }));
    }
});

// FUNCTIONS
const addQuote = () => form.quotes.push({ content: '', source: '' });
const removeQuote = (index) => form.quotes.splice(index, 1);
// Helper untuk membuat pratinjau gambar
const createPreview = (file, target) => {
    if (!file || !(file instanceof File)) {
        previews.value[target] = null;
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        previews.value[target] = e.target.result;
    };
    reader.readAsDataURL(file);
};

watch(() => form.cover_photo, (newFile) => createPreview(newFile, 'cover'));
watch(() => form.groom.photo, (newFile) => createPreview(newFile, 'groomPhoto'));
watch(() => form.bride.photo, (newFile) => createPreview(newFile, 'bridePhoto'));

const handleGalleryUpload = (event) => {
    const files = Array.from(event.target.files);

    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            // Buat objek baru untuk setiap file dan tambahkan ke array utama
            galleryItems.value.push({
                id: null,
                file: file, // Objek File
                preview: e.target.result, // Data URL untuk pratinjau
                caption: '' // Caption awal kosong
            });
        };
        reader.readAsDataURL(file);
    });

    // Reset input file agar bisa memilih file yang sama lagi
    event.target.value = null;
    form.gallery_changed = true;
};
const removeGalleryImage = (index) => {
    const item = galleryItems.value[index];

    // Jika ini gambar lama (punya ID), catat ID-nya untuk dihapus di backend
    if (item.id) {
        form.deleted_gallery_ids.push(item.id);
    }

    // Hapus dari tampilan frontend
    galleryItems.value.splice(index, 1);
    form.gallery_changed = true;
};

const onDragEnd = () => {
    form.gallery_changed = true; // Atau nama flag lain yang Anda gunakan
};

const addCeremony = () => form.ceremonies.push({ name: 'Resepsi', ceremony_date: '', start_time: '12:00', end_time: '21:00', location: '', address: '', maps_url: '',timezone: 'WIB', is_until_finished: false, use_previous_details: false });
const removeCeremony = (index) => form.ceremonies.splice(index, 1);

const handleUntilFinished = (ceremony) => {
    // Jika checkbox dicentang
    if (ceremony.is_until_finished) {
        // Kosongkan waktu selesai
        ceremony.end_time = '';
    }
};

const handleDuplicateDetails = (index) => {
    const currentCeremony = form.ceremonies[index];

    // Pastikan ini bukan acara pertama
    if (index > 0) {
        const previousCeremony = form.ceremonies[index - 1];

        // Jika checkbox dicentang, salin datanya
        if (currentCeremony.use_previous_details) {
            currentCeremony.ceremony_date = previousCeremony.ceremony_date;
            currentCeremony.location = previousCeremony.location;
            currentCeremony.address = previousCeremony.address;
            currentCeremony.maps_url = previousCeremony.maps_url;
        }
    }
};

const submit = () => {
    // Siapkan data galeri dan urutannya
    if (galleryItems.value.length > 0) {
        form.gallery_photos = galleryItems.value
        .filter(item => item.file !== null)
        .map(item => item.file);

        // 2. Siapkan "peta" urutan dan caption untuk SEMUA item (baru & lama)
        form.gallery_items_order = galleryItems.value.map(item => ({
            id: item.id,
            caption: item.caption
        }));
    }

    if (isEditMode.value) {
        // Mode Edit: Kirim request POST dengan method spoofing
        form.post(route('admin.events.update', props.event.id), {
             forceFormData: true, // Paksa Inertia menggunakan FormData untuk file
             onError: (errors) => console.log("Error saat update:", errors),
        });
    } else {
        // Mode Create
        form.post(route('admin.events.store'), {
            onError: (errors) => console.log("Error saat create:", errors),
        });
    }
};

const applyThemeContent = () => {
    const selectedTheme = form.theme;
    const content = themeData[selectedTheme]; // Ambil data dari objek yang diimpor

    if (content) {
        form.details.opening_text = content.opening_text;
        form.details.closing_text = content.closing_text;
        form.quotes = content.quotes.map(q => ({ ...q })); // Salin quotes agar reaktif
    }
};

</script>

<template>
    <div>
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'infoDasar'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'infoDasar'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Info Dasar
                    <!-- <font-awesome-icon v-if="isInfoDasarComplete" icon="check-circle" class="ml-2 text-green-500" /> -->
                </button>
                <button @click="activeTab = 'mempelai'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'mempelai'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Data Mempelai
                    <!-- <font-awesome-icon v-if="isMempelaiComplete" icon="check-circle" class="ml-2 text-green-500" /> -->
                </button>
                <button @click="activeTab = 'acara'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'acara'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Rangkaian Acara
                    <!-- <font-awesome-icon v-if="isAcaraComplete" icon="check-circle" class="ml-2 text-green-500" /> -->
                </button>
                <button @click="activeTab = 'konten'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'konten'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Konten Undangan
                    <!-- <font-awesome-icon v-if="isKontenComplete" icon="check-circle" class></font-awesome-icon> -->
                </button>
                <button @click="activeTab = 'galeri'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'galeri'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Galeri</button>
            </nav>
        </div>

        <form @submit.prevent="submit">
            <div v-show="activeTab === 'infoDasar'">
                <FormSection title="Informasi Dasar Event">
                    <div>
                        <InputLabel value="Foto Sampul Utama (Wajib)" />
                        <FileInput v-model="form.cover_photo" class="mt-1" />
                        <InputError class="mt-2" :message="form.errors.cover_photo" />

                        <div v-if="previews.cover" class="mt-4">
                            <p class="text-sm text-gray-500 mb-2">Pratinjau Baru:</p>
                            <img :src="previews.cover" class="w-full max-w-sm rounded-lg shadow-md" />
                        </div>
                        <div v-else-if="isEditMode && event.cover_photo_path" class="mt-4">
                            <p class="text-sm text-gray-500 mb-2">Foto Saat Ini:</p>
                            <img :src="`/storage/${event.cover_photo_path}`" class="w-full max-w-sm rounded-lg shadow-md" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="template" value="Template Desain" />
                        <ImageSelect
                            id="template"
                            v-model="form.event_template_id"
                            :options="templateOptions"
                            class="mt-1"
                        />
                        <InputError class="mt-2" :message="form.errors.event_template_id" />
                    </div>
                    <div>
                        <InputLabel for="theme" value="Tema Konten" />
                        <select
                            id="theme"
                            v-model="form.theme"
                            @change="applyThemeContent"
                            class="input-style mt-1"
                        >
                            <option v-for="theme in themeOptions" :key="theme" :value="theme">
                                {{ theme.charAt(0).toUpperCase() + theme.slice(1) }}
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Memilih tema akan mengisi beberapa konten secara otomatis.</p>
                    </div>
                </FormSection>
            </div>

            <div v-show="activeTab === 'mempelai'">
                <div class="space-y-10">
                    <FormSection title="Data Mempelai Pria" description="Isi semua informasi mengenai mempelai pria.">
                        <div>
                            <InputLabel value="Foto Mempelai Pria" />
                            <FileInput v-model="form.groom.photo" class="mt-1" />
                            <InputError class="mt-2" :message="form.errors['groom.photo']" />

                            <div class="mt-4">
                                <div v-if="previews.groomPhoto">
                                    <p class="text-sm text-gray-500 mb-2">Pratinjau Baru:</p>
                                    <img :src="previews.groomPhoto" class="w-40 h-40 object-cover rounded-lg shadow-md" />
                                </div>

                                <div v-else-if="isEditMode && event.groom.photo_path">
                                    <p class="text-sm text-gray-500 mb-2">Foto Saat Ini:</p>
                                    <img :src="`/storage/${event.groom.photo_path}`" class="w-40 h-40 object-cover rounded-lg shadow-md" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="groom_name" value="Nama Lengkap" />
                                <TextInput
                                    id="groom_name"
                                    v-model="form.groom.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors['groom.name']" />
                            </div>

                            <div>
                                <InputLabel for="groom_nickname" value="Nama Panggilan" />
                                <TextInput
                                    id="groom_nickname"
                                    v-model="form.groom.nickname"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors['groom.nickname']" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Detail Keluarga" />
                            <CKEditor v-model="form.groom.family_details" />
                            <InputError class="mt-2" :message="form.errors['groom.family_details']" />
                        </div>
                        <div>
                            <InputLabel value="Sosial Media" />
                            <div class="mt-1 grid grid-cols-1 sm:grid-cols-3 gap-4">

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <font-awesome-icon :icon="['fab', 'instagram']" class="text-gray-400" />
                                    </div>
                                    <TextInput
                                        v-model="form.groom.social_media.instagram"
                                        type="url"
                                        class="block w-full pl-10"
                                        placeholder="Instagram"
                                    />
                                </div>

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <font-awesome-icon :icon="['fab', 'facebook']" class="text-gray-400" />
                                    </div>
                                    <TextInput
                                        v-model="form.groom.social_media.facebook"
                                        type="url"
                                        class="block w-full pl-10"
                                        placeholder="Facebook"
                                    />
                                </div>

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <font-awesome-icon :icon="['fab', 'twitter']" class="text-gray-400" />
                                    </div>
                                    <TextInput
                                        v-model="form.groom.social_media.twitter"
                                        type="url"
                                        class="block w-full pl-10"
                                        placeholder="Twitter"
                                    />
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors['groom.social_media.instagram']" />
                            <InputError class="mt-2" :message="form.errors['groom.social_media.facebook']" />
                            <InputError class="mt-2" :message="form.errors['groom.social_media.twitter']" />
                        </div>
                    </FormSection>

                    <FormSection title="Data Mempelai Wanita" description="Isi semua informasi mengenai mempelai Wanita.">
                        <div>
                            <InputLabel value="Foto Mempelai Wanita" />
                            <FileInput v-model="form.bride.photo" class="mt-1" />
                            <InputError class="mt-2" :message="form.errors['bride.photo']" />

                            <div class="mt-4">
                                <div v-if="previews.bridePhoto">
                                    <p class="text-sm text-gray-500 mb-2">Pratinjau Baru:</p>
                                    <img :src="previews.bridePhoto" class="w-40 h-40 object-cover rounded-lg shadow-md" />
                                </div>

                                <div v-else-if="isEditMode && event.bride.photo_path">
                                    <p class="text-sm text-gray-500 mb-2">Foto Saat Ini:</p>
                                    <img :src="`/storage/${event.bride.photo_path}`" class="w-40 h-40 object-cover rounded-lg shadow-md" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="bride_name" value="Nama Lengkap" />
                                <TextInput
                                    id="bride_name"
                                    v-model="form.bride.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors['bride.name']" />
                            </div>

                            <div>
                                <InputLabel for="bride_nickname" value="Nama Panggilan" />
                                <TextInput
                                    id="bride_nickname"
                                    v-model="form.bride.nickname"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors['bride.nickname']" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Detail Keluarga" />
                            <CKEditor v-model="form.bride.family_details" />
                            <InputError class="mt-2" :message="form.errors['bride.family_details']" />
                        </div>
                        <div>
                            <InputLabel value="Sosial Media" />
                            <div class="mt-1 grid grid-cols-1 sm:grid-cols-3 gap-4">

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <font-awesome-icon :icon="['fab', 'instagram']" class="text-gray-400" />
                                    </div>
                                    <TextInput
                                        v-model="form.bride.social_media.instagram"
                                        type="url"
                                        class="block w-full pl-10"
                                        placeholder="Instagram"
                                    />
                                </div>

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <font-awesome-icon :icon="['fab', 'facebook']" class="text-gray-400" />
                                    </div>
                                    <TextInput
                                        v-model="form.bride.social_media.facebook"
                                        type="url"
                                        class="block w-full pl-10"
                                        placeholder="Facebook"
                                    />
                                </div>

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <font-awesome-icon :icon="['fab', 'twitter']" class="text-gray-400" />
                                    </div>
                                    <TextInput
                                        v-model="form.bride.social_media.twitter"
                                        type="url"
                                        class="block w-full pl-10"
                                        placeholder="Twitter"
                                    />
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors['bride.social_media.instagram']" />
                            <InputError class="mt-2" :message="form.errors['bride.social_media.facebook']" />
                            <InputError class="mt-2" :message="form.errors['bride.social_media.twitter']" />
                        </div>
                    </FormSection>
                </div>
            </div>

            <div v-show="activeTab === 'acara'">
                <FormSection
                    title="Rangkaian Acara"
                    description="Tambahkan satu atau lebih acara seperti Akad Nikah, Pemberkatan, Resepsi, dll."
                >
                    <div class="flex justify-end">
                        <button type="button" @click="addCeremony" class="px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">
                            + Tambah Acara
                        </button>
                    </div>

                    <div class="space-y-6">
                        <div v-for="(ceremony, index) in form.ceremonies" :key="index" class="p-4 border rounded-lg shadow-sm bg-white">

                            <div class="flex justify-between items-center border-b pb-3 mb-4">
                                <h4 class="text-md font-semibold text-gray-800">
                                    Acara #{{ index + 1 }}: {{ ceremony.name || 'Acara Baru' }}
                                </h4>
                                <DangerButton type="button" @click="removeCeremony(index)" class="!p-1.5 !leading-none">
                                    <font-awesome-icon icon="trash" class="w-4 h-4" />
                                </DangerButton>
                            </div>

                            <div v-if="index > 0" class="mb-4 p-3 bg-gray-50 rounded-md border">
                                <div class="flex items-center">
                                    <input
                                        :id="`duplicate_details_${index}`"
                                        type="checkbox"
                                        v-model="ceremony.use_previous_details"
                                        @change="handleDuplicateDetails(index)"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <label :for="`duplicate_details_${index}`" class="ml-2 block text-sm font-medium text-gray-900">
                                        Gunakan tanggal & lokasi yang sama dengan acara sebelumnya
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <InputLabel :for="`ceremony_name_${index}`" value="Nama Acara (e.g., Akad Nikah)" />
                                    <TextInput :id="`ceremony_name_${index}`" v-model="ceremony.name" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors[`ceremonies.${index}.name`]" />
                                </div>
                                <div>
                                    <InputLabel :for="`ceremony_date_${index}`" value="Tanggal" />
                                    <TextInput :id="`ceremony_date_${index}`" v-model="ceremony.ceremony_date" type="date" class="mt-1 block w-full" :disabled="ceremony.use_previous_details" />
                                    <InputError class="mt-2" :message="form.errors[`ceremonies.${index}.ceremony_date`]" />
                                </div>
                                <div>
                                    <InputLabel :for="`start_time_${index}`" value="Waktu Mulai" />
                                    <TextInput :id="`start_time_${index}`" v-model="ceremony.start_time" type="time" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors[`ceremonies.${index}.start_time`]" />
                                </div>
                                <div>
                                    <InputLabel :for="`end_time_${index}`" value="Waktu Selesai (Opsional)" />
                                    <TextInput :id="`end_time_${index}`" v-model="ceremony.end_time" type="time" class="mt-1 block w-full" :disabled="ceremony.is_until_finished" />
                                    <div class="mt-2 flex items-center">
                                        <input :id="`until_finished_${index}`" type="checkbox" v-model="ceremony.is_until_finished" @change="handleUntilFinished(ceremony)" class="h-4 w-4 rounded border-gray-300" />
                                        <label :for="`until_finished_${index}`" class="ml-2 block text-sm text-gray-900">Sampai selesai</label>
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <InputLabel :for="`location_${index}`" value="Nama Tempat / Gedung" />
                                    <TextInput :id="`location_${index}`" v-model="ceremony.location" type="text" class="mt-1 block w-full" :disabled="ceremony.use_previous_details" />
                                    <InputError class="mt-2" :message="form.errors[`ceremonies.${index}.location`]" />
                                </div>
                                <div class="md:col-span-2">
                                    <InputLabel :for="`address_${index}`" value="Alamat Lengkap" />
                                    <TextareaInput :id="`address_${index}`" v-model="ceremony.address" rows="3" class="mt-1 block w-full" :disabled="ceremony.use_previous_details" />
                                    <InputError class="mt-2" :message="form.errors[`ceremonies.${index}.address`]" />
                                </div>
                                <div>
                                    <InputLabel :for="`maps_url_${index}`" value="URL Google Maps" />
                                    <TextInput :id="`maps_url_${index}`" v-model="ceremony.maps_url" type="url" class="mt-1 block w-full" :disabled="ceremony.use_previous_details" />
                                </div>
                                <div>
                                    <InputLabel :for="`timezone_${index}`" value="Zona Waktu" />
                                    <select :id="`timezone_${index}`" v-model="ceremony.timezone" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                        <option>WIB</option>
                                        <option>WITA</option>
                                        <option>WIT</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors[`ceremonies.${index}.timezone`]" />
                                </div>
                            </div>
                        </div>
                    </div>
                </FormSection>
            </div>

            <div v-show="activeTab === 'konten'">
                <FormSection title="Konten & Teks Undangan">
                    <div>
                        <InputLabel value="Kata Pembuka (Opening)" />
                        <CKEditor v-model="form.details.opening_text" />
                    </div>

                    <div class="p-4 border rounded-lg space-y-4">
                        <div class="flex justify-between items-center">
                            <h4 class="font-medium">Kutipan / Ayat (Seret untuk mengubah urutan)</h4>
                            <button type="button" @click="addQuote" class="btn-secondary">+ Tambah Kutipan</button>
                        </div>
                        <draggable v-model="form.quotes" item-key="index" tag="div" class="space-y-4" handle=".handle">
                            <template #item="{ element: quote, index }">
                                <div class="flex items-start space-x-3 p-2 rounded-md bg-gray-50">
                                    <div class="handle cursor-move pt-7 text-gray-400 hover:text-gray-600">
                                        <font-awesome-icon icon="bars" />
                                    </div>
                                    <div class="flex-grow border-l pl-3">
                                        <InputLabel :for="`quote_content_${index}`" value="Isi Kutipan / Ayat" />
                                        <TextareaInput :id="`quote_content_${index}`" v-model="quote.content" rows="3" />
                                        <InputLabel :for="`quote_source_${index}`" value="Sumber (Opsional)" class="mt-2" />
                                        <TextInput :id="`quote_source_${index}`" v-model="quote.source" type="text" />
                                    </div>
                                    <DangerButton type="button" @click="removeQuote(index)" class="mt-6 !p-2">
                                        <font-awesome-icon icon="trash" />
                                    </DangerButton>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <div>
                        <InputLabel value="Love Story (Bisa embed video/musik)" />
                        <CKEditor v-model="form.details.story_text" />
                    </div>

                    <div>
                        <InputLabel value="Kata Penutup (Ending)" />
                        <CKEditor v-model="form.details.closing_text" />
                    </div>

                    <div>
                        <InputLabel value="Musik Latar (.mp3)" />
                        <FileInput v-model="form.details.music" accept=".mp3,audio/*" class="mt-1" />
                    </div>
                </FormSection>
            </div>

            <div v-show="activeTab === 'galeri'">
                <FormSection
                    title="Galeri Foto Pre-wedding"
                    description="Upload foto, lalu seret (drag) untuk mengubah urutan."
                >
                    <div>
                        <InputLabel value="Tambah Foto ke Galeri" />
                        <input
                            type="file"
                            @change="handleGalleryUpload"
                            multiple
                            class="input-style-file mt-1"
                            accept="image/*"
                        />
                        <InputError class="mt-2" :message="form.errors.gallery_photos" />
                    </div>

                    <div v-if="galleryItems.length > 0" class="mt-6">
                        <h4 class="text-md font-medium text-gray-800 border-b pb-2 mb-4">Pratinjau & Caption (Seret untuk ubah urutan)</h4>

                        <draggable
                            v-model="galleryItems"
                            item-key="preview"
                            tag="div"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                            handle=".handle"
                            @end="onDragEnd"
                        >
                            <template #item="{ element: item, index }">
                                <div class="border rounded-lg overflow-hidden shadow-sm flex flex-col">

                                    <div class="relative group">
                                        <div class="handle absolute top-2 left-2 cursor-move text-white text-opacity-50 group-hover:text-opacity-100 transition z-10 p-1 bg-black bg-opacity-20 rounded-full">
                                            <font-awesome-icon icon="bars" />
                                        </div>

                                        <button
                                            type="button"
                                            @click="removeGalleryImage(index)"
                                            class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-700 transition z-10 opacity-0 group-hover:opacity-100"
                                            title="Hapus gambar ini"
                                        >
                                            <font-awesome-icon icon="trash" class="w-4 h-4" />
                                        </button>

                                        <img :src="item.preview" class="w-full h-48 object-cover" />
                                    </div>

                                    <div class="p-4 bg-white flex-grow">
                                        <InputLabel :for="`caption_${index}`" value="Caption Foto" />
                                        <TextInput
                                            :id="`caption_${index}`"
                                            v-model="item.caption"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="Contoh: Momen bahagia..."
                                        />
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </FormSection>
            </div>

            <div class="mt-8 pt-5 border-t flex items-center gap-4">
                <PrimaryButton :disabled="!form.isDirty || form.processing">
                    {{ isEditMode ? 'Update Event' : 'Simpan Event' }}
                </PrimaryButton>

                <transition
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                    class="transition ease-in-out duration-300"
                >
                    <div>
                        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">
                            Berhasil disimpan.
                        </p>
                        <p v-if="isEditMode && !form.isDirty && !form.processing" class="text-sm text-gray-500">
                            Tidak ada perubahan untuk disimpan.
                        </p>
                    </div>
                </transition>
            </div>
        </form>
    </div>
</template>
