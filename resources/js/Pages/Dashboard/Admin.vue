<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head,usePage } from '@inertiajs/vue3';
import BarChart from '@/Components/Charts/BarChart.vue';
import { ref, computed } from 'vue';
// import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'; // Jika Anda membuatnya

import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";

import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
} from '@headlessui/vue'

const isOpen = ref(false)
const selectedEvent = ref({})

const openModal = (event) => {
  selectedEvent.value = event.event
  isOpen.value = true
}
const closeModal = () => {
  isOpen.value = false
}

const props = defineProps({
    totalActiveEvents: Number,
    guestCountChartData: Object,
    attendanceChartData: Object,
    totalGuest:Number,
    calendarEvents: Array,
});



const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: "dayGridMonth",
  headerToolbar: {
    left: "prev,next today",
    center: "title",
    right: "dayGridMonth,timeGridWeek,timeGridDay",
  },
  events: props.calendarEvents,
  eventClick: openModal,
});

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-600">Event Aktif</h3>
                        <p class="text-4xl font-bold text-gray-800 mt-2">{{ totalActiveEvents }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-600">Total Tamu Aktif</h3>
                        <p class="text-4xl font-bold text-gray-800 mt-2">{{ totalGuest }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                    <h3 class="text-xl font-bold mb-4">Timeline Event Aktif</h3>
                    <FullCalendar :options="calendarOptions" />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mt-5">
                    <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-md">
                        <h3 class="font-semibold mb-4">Jumlah Tamu per Event</h3>
                        <div class="h-80">
                            <BarChart :chart-data="guestCountChartData" />
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                         <h3 class="font-semibold mb-4">Ringkasan Kehadiran</h3>
                         <div class="h-80">
                             <BarChart :chart-data="attendanceChartData" />
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <div class="fixed inset-0 bg-black/30" />

            <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <DialogTitle class="text-lg font-medium text-gray-900">
                {{ selectedEvent?.title }}
                </DialogTitle>
                <p class="mt-2 text-sm text-gray-500">
                {{ selectedEvent?.extendedProps.description }}
                </p>
                <p class="mt-2 text-sm text-gray-500">
                Waktu: {{ selectedEvent?.startStr }} → {{ selectedEvent?.endStr }}
                </p>

                <div class="mt-4">
                <button @click="closeModal"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                    Tutup
                </button>
                </div>
            </DialogPanel>
            </div>
        </Dialog>
        </TransitionRoot>
    </AuthenticatedLayout>
</template>
