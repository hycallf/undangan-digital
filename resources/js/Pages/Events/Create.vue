<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import EventForm from './Partials/EventForm.vue';

const props = defineProps({
    themeDefaults: Object,
    themeOptions: Array,
    templateOptions: Array,
});
</script>

<template>
    <Head title="Buat Event Baru" />
    <AuthenticatedLayout>
        <template #header>Buat Event Baru</template>
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <EventForm :theme-options="themeOptions" :template-options="templateOptions"/>
        </div>
    </AuthenticatedLayout>
</template>
