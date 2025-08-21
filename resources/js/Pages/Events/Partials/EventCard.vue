<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { faEye, faPencilAlt, faTrash, faLink, faQrcode, faEllipsisV } from '@fortawesome/free-solid-svg-icons';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    event: Object,
});

const emit = defineEmits(['delete']);
const user = usePage().props.auth.user;
</script>

<template>
    <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:scale-[1.02] hover:shadow-xl relative">
        <div class="relative">
            <img
                :src="event.cover_photo_path ? `/storage/${event.cover_photo_path}` : 'https://via.placeholder.com/400x200?text=No+Image'"
                alt="Cover Event"
                class="w-full h-40 object-cover"
            />
            <div v-if="user.role === 'admin'" class="absolute top-2 right-2">
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="p-2 rounded-full bg-white/70 hover:bg-white/90 transition">
                            <font-awesome-icon :icon="faEllipsisV" class="text-gray-500" />
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('admin.events.edit', event.id)">
                            <font-awesome-icon :icon="faPencilAlt" class="mr-2 w-4" />
                            Edit Event
                        </DropdownLink>

                        <DropdownLink href="#" as="button">
                            <font-awesome-icon :icon="faPencilAlt" class="mr-2 w-4" />
                            Live Edit (Segera)
                        </DropdownLink>

                        <button @click="emit('delete', event.id)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-700 hover:bg-red-100 focus:outline-none focus:bg-red-100 transition duration-150 ease-in-out">
                            <font-awesome-icon :icon="faTrash" class="mr-2 w-4" />
                            Hapus
                        </button>
                    </template>
                </Dropdown>
            </div>
        </div>

        <div class="p-4 flex flex-col flex-grow">
            <div>
                <h3 v-if="event.groom && event.bride" class="text-lg font-semibold text-gray-800 truncate" :title="`${event.groom.name} & ${event.bride.name}`">
                    {{ event.groom.nickname }} & {{ event.bride.nickname }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ event.ceremonies && event.ceremonies.length > 0 ? event.ceremonies [0].ceremony_date : 'Tanggal belum diatur' }}
                </p>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <Link :href="route('checkin.scanner', event.id)" as="button" class="btn-reception">
                        <font-awesome-icon :icon="faQrcode" class="mr-2" />
                        Check-in
                    </Link>
                    <Link :href="route('invitation.show', event.slug)" target="_blank" as="button" class="btn-reception-alt">
                        <font-awesome-icon :icon="faLink" class="mr-1" />
                        Lihat Undangan
                    </Link>
                </div>
                <Link :href="route('admin.events.guests.show', event.id)" as="button" class="btn-reception-outline w-full">
                    <font-awesome-icon :icon="faEye" class="mr-1" />
                    Daftar Tamu
                </Link>
            </div>
        </div>
    </div>
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
</style>
