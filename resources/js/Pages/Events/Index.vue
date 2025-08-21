<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';
import EventCard from './Partials/EventCard.vue';
import TextInput from '@/Components/TextInput.vue';
import { faPlus, faSearch, faCalendarDay } from '@fortawesome/free-solid-svg-icons';
import { debounce } from 'lodash';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    events: Array,
    filters: Object,
});

const user = usePage().props.auth.user;

const search = ref(props.filters?.search || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const applyFilters = () => {
    // Hanya filter jika kedua tanggal sudah terisi
    if (startDate.value && endDate.value) {
        router.get(
            route('events.index'),
            {
                search: search.value,
                start_date: startDate.value,
                end_date: endDate.value
            },
            { preserveState: true, replace: true }
        );
    }
};
// Watcher untuk melakukan pencarian saat user berhenti mengetik
watch(search, (value) => {
    router.get(route('events.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

watch(search, debounce(applyFilters, 300));
watch([startDate, endDate], applyFilters);

const deleteEvent = (eventId) => {
    // Gunakan SweetAlert untuk konfirmasi
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Event yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        // Jika user menekan tombol "Ya, hapus!"
        if (result.isConfirmed) {
            router.delete(route('admin.events.destroy', eventId));
        }
    });
};
</script>

<template>
    <Head title="Kelola Events" />

    <AuthenticatedLayout>
        <template #header>
            Kelola Events
        </template>

        <div class="mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <Link v-if="user.role === 'admin'" :href="route('admin.events.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 w-full sm:w-auto">
                <font-awesome-icon :icon="faPlus" class="mr-2" />
                Buat Event Baru
            </Link>

            <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                <div class="flex items-center gap-2 w-full">
                    <div>
                        <InputLabel for="start_date" value="From" class="text-xs text-gray-500 mb-1" />
                        <TextInput id="start_date" v-model="startDate" type="date" class="block w-full" />
                    </div>
                    <div>
                        <InputLabel for="end_date" value="To" class="text-xs text-gray-500 mb-1" />
                        <TextInput id="end_date" v-model="endDate" type="date" class="block w-full" />
                    </div>
                    <div class="relative w-full sm:w-64">
                        <InputLabel for="search" value="Search" class="text-xs text-gray-500 mb-1">&nbsp;</InputLabel>
                        <div class="absolute inset-y-0 left-0 pl-3 pt-5 flex items-center pointer-events-none">
                            <font-awesome-icon :icon="faSearch" class="text-gray-400" />
                        </div>
                        <TextInput
                            id="search"
                            v-model="search"
                            type="text"
                            class="block w-full pl-10"
                            placeholder="Cari nama mempelai..."
                        />
                    </div>
                </div>


            </div>
        </div>


        <div v-if="events.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <EventCard
                v-for="event in events"
                :key="event.id"
                :event="event"
                @delete="deleteEvent"
            />
        </div>
        <div v-else class="text-center text-gray-500 bg-white p-6 rounded-lg shadow-sm">
            <p>Tidak ada event yang ditemukan.</p>
        </div>
    </AuthenticatedLayout>
</template>
