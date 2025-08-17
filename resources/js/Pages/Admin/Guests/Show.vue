<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    event: Object,
    guests: Array,
});

// Hitung statistik menggunakan computed property
const totalRSVP = computed(() => props.guests.length);
const totalHadir = computed(() => props.guests.filter(g => g.attendance_status === 'present').length);
const totalBelumHadir = computed(() => totalRSVP.value - totalHadir.value);

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

        <div class="p-4 sm:p-6 lg:p-8">
        <Link
            :href="route('admin.events.index')"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
        >
            <font-awesome-icon :icon="faArrowLeft" class="mr-2" />
            Kembali ke Daftar Event
        </Link>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Tamu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Kehadiran</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Check-in</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="guest in guests" :key="guest.id">
                            <td class="px-6 py-4">{{ guest.name }}</td>
                            <td class="px-6 py-4">
                                <span :class="{
                                    'bg-green-100 text-green-800': guest.attendance_status === 'present',
                                    'bg-yellow-100 text-yellow-800': guest.attendance_status !== 'present'
                                }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                    {{ guest.attendance_status === 'present' ? 'Hadir' : 'Direncanakan' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ guest.check_in_time || '-' }}</td>
                            <td class="px-6 py-4">
                                <img v-if="guest.photo_path" :src="'/storage/' + guest.photo_path" alt="Foto Tamu" class="w-16 h-16 object-cover rounded-md">
                                <span v-else class="text-gray-400">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
