<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import BarChart from '@/Components/Charts/BarChart.vue';
import { ref, computed } from 'vue';

import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";

import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
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
    totalGuest: Number,
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
  height: 'auto',
  eventDisplay: 'block',
  dayMaxEvents: 3,
  moreLinkClick: 'popover',
});

// Computed untuk statistik tambahan
const attendanceRate = computed(() => {
  if (props.totalGuest === 0) return 0;
  const present = props.attendanceChartData.datasets[0].data[0];
  return Math.round((present / props.totalGuest) * 100);
});

const upcomingEvents = computed(() => {
  const today = new Date();
  return props.calendarEvents.filter(event => new Date(event.start) >= today).length;
});
</script>

<template>
    <Head title="Dashboard Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">Dashboard Admin</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Hero Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Events Card -->
                    <div class="relative bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white bg-opacity-10 rounded-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-blue-100 text-sm font-medium">Event Aktif</p>
                                    <p class="text-3xl font-bold">{{ totalActiveEvents }}</p>
                                </div>
                            </div>
                            <div class="flex items-center text-blue-100 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ upcomingEvents }} mendatang
                            </div>
                        </div>
                    </div>

                    <!-- Total Guests Card -->
                    <div class="relative bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 rounded-xl shadow-lg text-white overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white bg-opacity-10 rounded-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-emerald-100 text-sm font-medium">Total Tamu</p>
                                    <p class="text-3xl font-bold">{{ totalGuest }}</p>
                                </div>
                            </div>
                            <div class="flex items-center text-emerald-100 text-sm">
                                <div class="w-2 h-2 bg-emerald-300 rounded-full mr-2"></div>
                                Terdaftar di sistem
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Rate Card -->
                    <div class="relative bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-xl shadow-lg text-white overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white bg-opacity-10 rounded-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-purple-100 text-sm font-medium">Tingkat Kehadiran</p>
                                    <p class="text-3xl font-bold">{{ attendanceRate }}%</p>
                                </div>
                            </div>
                            <div class="w-full bg-white bg-opacity-20 rounded-full h-2">
                                <div class="bg-white h-2 rounded-full transition-all duration-300" :style="`width: ${attendanceRate}%`"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="relative bg-gradient-to-br from-amber-500 to-orange-500 p-6 rounded-xl shadow-lg text-white overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-white bg-opacity-10 rounded-full"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-orange-100 text-sm font-medium mb-5">Quick Actions</p>
                                    <Link
                                    :href="route('admin.events.create')"
                                    class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-medium transition-all duration-200"
                                    >
                                    Buat Event
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Section -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-white">Timeline Event</h3>
                                <p class="text-indigo-100 text-sm">Jadwal acara yang akan datang</p>
                            </div>
                            <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <FullCalendar :options="calendarOptions" />
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Guest Count Chart -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-white">Jumlah Tamu per Event</h3>
                                    <p class="text-blue-100 text-sm">Distribusi tamu di setiap acara</p>
                                </div>
                                <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-80">
                                <BarChart :chart-data="guestCountChartData" />
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Summary -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-white">Ringkasan Kehadiran</h3>
                                    <p class="text-emerald-100 text-sm">Status check-in tamu</p>
                                </div>
                                <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="h-64 mb-4">
                                <BarChart :chart-data="attendanceChartData" />
                            </div>

                            <!-- Attendance Details -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-emerald-500 rounded-full mr-3"></div>
                                        <span class="text-sm font-medium text-emerald-900">Sudah Hadir</span>
                                    </div>
                                    <span class="text-sm font-bold text-emerald-700">{{ attendanceChartData.datasets[0].data[0] }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-amber-500 rounded-full mr-3"></div>
                                        <span class="text-sm font-medium text-amber-900">Belum Hadir</span>
                                    </div>
                                    <span class="text-sm font-bold text-amber-700">{{ attendanceChartData.datasets[0].data[1] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Insights -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">Insights & Analytics</h3>
                        <p class="text-gray-300 text-sm">Ringkasan performa event Anda</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Average Guests per Event -->
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 mb-2">
                                    {{ totalActiveEvents > 0 ? Math.round(totalGuest / totalActiveEvents) : 0 }}
                                </div>
                                <div class="text-sm text-gray-600">Rata-rata Tamu per Event</div>
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" :style="`width: ${Math.min(100, (totalGuest / totalActiveEvents / 100) * 100)}%`"></div>
                                </div>
                            </div>

                            <!-- Attendance Rate -->
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 mb-2">{{ attendanceRate }}%</div>
                                <div class="text-sm text-gray-600">Tingkat Kehadiran</div>
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="`width: ${attendanceRate}%`"></div>
                                </div>
                            </div>

                            <!-- Upcoming Events -->
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 mb-2">{{ upcomingEvents }}</div>
                                <div class="text-sm text-gray-600">Event Mendatang</div>
                                <div class="mt-2 flex justify-center">
                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Detail Modal -->
        <TransitionRoot appear :show="isOpen" as="template">
            <Dialog as="div" class="relative z-50" @close="closeModal">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                                <DialogTitle class="text-lg font-bold text-white">
                                    {{ selectedEvent?.title }}
                                </DialogTitle>
                            </div>

                            <!-- Modal Content -->
                            <div class="p-6 space-y-4">
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Deskripsi</p>
                                        <p class="text-sm text-gray-600">{{ selectedEvent?.extendedProps?.description || 'Tidak ada deskripsi' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Waktu</p>
                                        <p class="text-sm text-gray-600">
                                            {{ new Date(selectedEvent?.start).toLocaleDateString('id-ID', {
                                                weekday: 'long',
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric'
                                            }) }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ new Date(selectedEvent?.start).toLocaleTimeString('id-ID', {
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) }} -
                                            {{ new Date(selectedEvent?.end).toLocaleTimeString('id-ID', {
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="selectedEvent?.extendedProps?.location" class="flex items-start space-x-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Lokasi</p>
                                        <p class="text-sm text-gray-600">{{ selectedEvent?.extendedProps?.location }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                                <button @click="closeModal"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors duration-200">
                                    Tutup
                                </button>
                                <a
                                    :href="selectedEvent?.extendedProps?.maps"
                                    target="_blank"
                                    rel="noopener"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors duration-200 rounded-lg"
                                    >
                                    Open Maps
                                </a>

                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom styles untuk FullCalendar */
:deep(.fc-theme-standard td, .fc-theme-standard th) {
    border-color: #e5e7eb;
}

:deep(.fc-event) {
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 6px;
    margin: 1px;
}

:deep(.fc-event:hover) {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

:deep(.fc-daygrid-event) {
    border-radius: 6px;
    margin: 1px 2px;
}

:deep(.fc-header-toolbar) {
    margin-bottom: 1.5rem;
}

:deep(.fc-button-primary) {
    background: #4f46e5;
    border-color: #4f46e5;
    border-radius: 8px;
    font-weight: 500;
}

:deep(.fc-button-primary:hover) {
    background: #4338ca;
    border-color: #4338ca;
}

:deep(.fc-today-button) {
    background: #059669 !important;
    border-color: #059669 !important;
}

/* Animation untuk cards */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > div {
    animation: slideUp 0.6s ease-out;
}

/* Stagger animation untuk cards */
.grid > div:nth-child(1) { animation-delay: 0.1s; }
.grid > div:nth-child(2) { animation-delay: 0.2s; }
.grid > div:nth-child(3) { animation-delay: 0.3s; }
.grid > div:nth-child(4) { animation-delay: 0.4s; }

/* Hover effects */
.relative:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
}

/* Custom scrollbar untuk calendar */
:deep(.fc-scroller) {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

:deep(.fc-scroller::-webkit-scrollbar) {
    width: 6px;
}

:deep(.fc-scroller::-webkit-scrollbar-track) {
    background: #f7fafc;
}

:deep(.fc-scroller::-webkit-scrollbar-thumb) {
    background: #cbd5e0;
    border-radius: 3px;
}
</style>
