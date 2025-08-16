<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

// Terima props dari controller jika ada
// defineProps({ totalEvents: Number, totalGuests: Number });
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Total Events</h3>
                <p class="text-4xl font-bold text-gray-800 mt-2">12</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Total Tamu Hadir</h3>
                <p class="text-4xl font-bold text-gray-800 mt-2">1,204</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Konfirmasi RSVP</h3>
                <p class="text-4xl font-bold text-gray-800 mt-2">85%</p>
            </div>
        </div>

        </AuthenticatedLayout>
</template>
