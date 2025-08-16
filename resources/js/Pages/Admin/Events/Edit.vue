<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import EventForm from './Partials/EventForm.vue';

const props = defineProps({
    event: Object,
    themeOptions: Array,
    templateOptions: Array,
});
</script>

<template>
    <Head title="Edit Event" />
    <AuthenticatedLayout>
        <template #header>
            Edit Event: {{ event.groom?.nickname }} & {{ event.bride?.nickname }}
        </template>
        <div class="bg-white p-6 rounded-lg shadow-sm">
             <EventForm
                :event="event"
                :theme-options="themeOptions"
                :template-options="templateOptions"
            />
        </div>
    </AuthenticatedLayout>
</template>
