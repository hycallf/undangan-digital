<script setup>
import { Link } from '@inertiajs/vue3';
import { faEye, faPencilAlt, faTrash } from '@fortawesome/free-solid-svg-icons';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    event: Object,
});

const emit = defineEmits(['delete']);
</script>

<template>
    <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:scale-105 hover:shadow-xl">
        <img
            :src="event.cover_photo_path ? `/storage/${event.cover_photo_path}` : 'https://via.placeholder.com/400x200?text=No+Image'"
            alt="Cover Event"
            class="w-full h-40 object-cover"
        />

        <div class="p-4">
            <h3 v-if="event.groom && event.bride" class="text-lg font-semibold ...">
                {{ event.groom.nickname }} & {{ event.bride.nickname }}
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                {{ event.ceremonies[0]?.ceremony_date || 'Tanggal belum diatur' }}
            </p>

            <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between space-x-2">
                <Link
                    :href="route('admin.checkin.scanner', event.id)"
                    as="button"
                    class="col-span-2 text-sm px-3 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-center flex items-center justify-center"
                >
                    <font-awesome-icon :icon="faQrcode" class="mr-2" />
                    Check-in Tamu
                </Link>
                <Link
                    :href="route('invitation.show', event.slug)"
                    target="_blank"
                    as="button"
                    class="col-span-2 text-sm px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-center"
                >
                    <font-awesome-icon :icon="faLink" class="mr-1" />
                    Lihat Undangan
                </Link>
                <Link :href="route('admin.events.guests.show', event.id)" as="button" class="text-sm px-3 py-1.5 bg-green-500 text-white rounded-md hover:bg-green-600 flex-1 text-center">
                    <font-awesome-icon :icon="faEye" class="mr-1" />
                    Tamu
                </Link>
                <template v-if="user.role === 'admin'">
                    <Link :href="route('admin.events.edit', event.id)" as="button" class="btn-secondary">
                        <font-awesome-icon :icon="faPencilAlt" class="mr-1" />
                        Edit Event
                    </Link>
                    <DangerButton @click="emit('delete', event.id)" class="text-sm !px-3 !py-1.5 justify-center w-full">
                        <font-awesome-icon :icon="faTrash" class="mr-1" />
                        Hapus
                    </DangerButton>
                </template>
            </div>
        </div>
    </div>
</template>
