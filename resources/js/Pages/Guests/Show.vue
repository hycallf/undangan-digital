<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Dropdown from '@/Components/Dropdown.vue'; // <-- Impor Dropdown
import DropdownLink from '@/Components/DropdownLink.vue'; // <-- Impor DropdownLink

import { faArrowLeft, faQrcode, faFileImport, faFileExport, faSearch, faTimes, faCaretDown } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    event: Object,
    guests: Array,
});

// Hitung statistik menggunakan computed property
const totalRSVP = computed(() => props.guests.length);
const totalHadir = computed(() => props.guests.filter(g => g.attendance_status === 'present').length);
const totalBelumHadir = computed(() => totalRSVP.value - totalHadir.value);

// State untuk modal import
const confirmingImport = ref(false);
const importForm = useForm({
    file: null,
    mode: 'add',
});

const openImportModal = () => {
    importForm.reset();
    confirmingImport.value = true;
};

const importGuests = () => {
    importForm.post(route('admin.events.guests.import', props.event.id), {
        onSuccess: () => {
            confirmingImport.value = false;
            importForm.reset();
        }
    });
};

const handleExport = (type) => {
    // Buat URL export
    const exportUrl = route('admin.events.guests.export', {
        event: props.event.id,
        type: type
    });

    console.log('Export URL:', exportUrl); // Debug

    // Method 1: Gunakan window.location untuk download
    window.location.href = exportUrl;

    // Alternative method jika method 1 gagal:
    // window.open(exportUrl, '_blank');
};

const headers = [
    { text: "Nama Tamu", value: "name", sortable: true },
    { text: "No. Telepon", value: "phone", sortable: true },
    // { text: "Status Konfirmasi", value: "confirmation_status" },
    { text: "Status Kehadiran", value: "attendance_status" },
    { text: "Waktu Check-in", value: "check_in_time", sortable: true },
    { text: "Foto", value: "photo_path" },
];
const searchValue = ref('');

</script>

<template>
    <Head :title="'Tamu ' + event.groom?.nickname + ' & ' + event.bride?.nickname" />
    <AuthenticatedLayout>
        <template #header>
            Daftar Tamu: {{ event.groom?.nickname }} & {{ event.bride?.nickname }}
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <h3 class="text-lg font-semibold text-gray-600">Total RSVP</h3>
                <p class="text-4xl font-bold text-gray-800 mt-2">{{ totalRSVP }}</p>
            </div>
            <div class="bg-green-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-lg font-semibold text-green-700">Sudah Hadir</h3>
                <p class="text-4xl font-bold text-green-800 mt-2">{{ totalHadir }}</p>
            </div>
            <div class="bg-yellow-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-lg font-semibold text-yellow-700">Belum Hadir</h3>
                <p class="text-4xl font-bold text-yellow-800 mt-2">{{ totalBelumHadir }}</p>
            </div>
        </div>
        <div class="my-6">
            <Link :href="route('admin.events.index')" class="btn-secondary">
                <font-awesome-icon :icon="faArrowLeft" class="mr-2" />
                    Daftar Event
            </Link>
        </div>


        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="sm:p-6 lg:p-8 grid grid-cols-4 gap-2">
                <Link :href="route('checkin.scanner', event.id)" as="button" class="btn-reception">
                    <font-awesome-icon :icon="faQrcode" class="mr-2" />
                    Check-in
                </Link>

                <Dropdown align="right">
                    <template #trigger>
                        <button class="btn-secondary">
                            <font-awesome-icon :icon="faFileImport" class="mr-2" />
                            <span>Import</span>
                            <font-awesome-icon :icon="faCaretDown" class="ml-2" />
                        </button>
                    </template>
                    <template #content>
                        <button @click="openImportModal" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            Import dari File
                        </button>
                    </template>
                </Dropdown>

                <Dropdown align="right">
                    <template #trigger>
                        <button class="btn-primary">
                            <font-awesome-icon :icon="faFileExport" class="mr-2" />
                            <span>Export</span>
                            <font-awesome-icon :icon="faCaretDown" class="ml-2" />
                        </button>
                    </template>
                    <template #content>
                        <!-- PERBAIKAN: Gunakan button dengan @click instead of DropdownLink -->
                        <button @click="handleExport('xlsx')" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            Export ke Excel (.xlsx)
                        </button>
                        <button @click="handleExport('pdf')" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            Export ke PDF (.pdf)
                        </button>
                    </template>
                </Dropdown>

                <div class="relative">
                    <TextInput
                        v-model="searchValue"
                        type="text"
                        class="block w-full pl-10"
                        placeholder="Cari nama, no. telp, dll..."
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <font-awesome-icon :icon="faSearch" class="text-gray-400" />
                    </div>
                </div>
            </div>
            <div class="bg-white border-b border-gray-200">
                <EasyDataTable
                        :headers="headers"
                        :items="guests"
                        :search-value="searchValue"
                        theme-color="#4F46E5"
                        buttons-pagination
                        alternating
                        show-index
                    >
                        <template #item-attendance_status="{ attendance_status }">
                            <span :class="{
                                'bg-green-100 text-green-800': attendance_status === 'present',
                                'bg-yellow-100 text-yellow-800': attendance_status === 'planned',
                                'bg-red-100 text-red-800': attendance_status === 'absent',
                            }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{
                                    attendance_status === 'present' ? 'Hadir'
                                    : attendance_status === 'planned' ? 'Direncanakan'
                                    : attendance_status === 'absent' ? 'Tidak Hadir'
                                    : '-'
                                }}
                            </span>
                        </template>

                    <template #item-check_in_time="{ check_in_time }">
                        {{ check_in_time || '-' }}
                    </template>

                    <template #item-photo="item">
                        <img v-if="item.photo_path" :src="'/storage/' + item.photo_path" alt="Foto Tamu" class="w-16 h-16 object-cover rounded-md">
                           <span v-else class="text-gray-400">-</span>
                    </template>

                </EasyDataTable>
                <Modal :show="confirmingImport" @close="confirmingImport = false">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            Import Daftar Tamu
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Pilih file Excel (.xlsx), CSV, atau Kontak (.vcf).
                        </p>

                        <div class="mt-6">
                            <label class="block font-medium text-sm text-gray-700">Mode Import</label>
                            <div class="flex items-center space-x-4 mt-2">
                                <label class="flex items-center">
                                    <input type="radio" v-model="importForm.mode" value="add" class="form-radio">
                                    <span class="ml-2 text-sm text-gray-600">Tambah ke Daftar (<font-awesome-icon :icon="faPlus"/>)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" v-model="importForm.mode" value="replace" class="form-radio">
                                    <span class="ml-2 text-sm text-gray-600">Hapus & Ganti Semua (<font-awesome-icon :icon="faRetweet"/>)</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block font-medium text-sm text-gray-700">Pilih File</label>
                            <input
                                type="file"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                @input="importForm.file = $event.target.files[0]"
                                accept=".xlsx,.xls,.csv,.vcf"
                            />
                            <InputError :message="importForm.errors.file" class="mt-2" />
                            <InputError :message="importForm.errors.mode" class="mt-2" />
                        </div>

                        <div class="mt-6 flex justify-end">
                            <SecondaryButton @click="confirmingImport = false">Batal</SecondaryButton>
                            <PrimaryButton @click="importGuests" class="ml-3" :class="{ 'opacity-25': importForm.processing }" :disabled="importForm.processing">
                                {{ importForm.processing ? 'Mengimpor...' : 'Import Tamu' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.btn-reception {
    @apply text-sm px-3 py-2 bg-emerald-500 text-white rounded-md hover:bg-emerald-600 flex items-center justify-center;
}
.btn-reception-alt {
    @apply text-sm px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 flex items-center justify-center;
}
.btn-reception-outline {
    @apply text-sm px-3 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 flex items-center justify-center;
}

.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300; }
.btn-success { @apply px-4 py-2 bg-emerald-500 text-white rounded-md hover:bg-emerald-600; }

</style>
