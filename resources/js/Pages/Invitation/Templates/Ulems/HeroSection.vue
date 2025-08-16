<script setup>
import { computed } from 'vue';

const props = defineProps({
    event: Object,
    guestName: {
        type: String,
        default: null,
    },
    isInvitationOpen: Boolean,
});

// Event untuk memberitahu komponen induk saat tombol diklik
const emit = defineEmits(['open-invitation']);

// Helper untuk mengambil tanggal dari acara pertama
const eventDate = computed(() => {
    if (props.event.ceremonies && props.event.ceremonies.length > 0) {
        return new Date(props.event.ceremonies[0].ceremony_date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }
    return 'Tanggal belum diatur';
});

const openInvitation = () => {
    emit('open-invitation');
};
</script>

<template>
    <div
        class="h-screen w-full bg-cover bg-center flex flex-col items-center justify-center text-white relative"
        :style="{ backgroundImage: `url(/storage/${event.cover_photo_path})` }"
        data-aos="fade-in"
    >

        <div class="relative z-10 text-center p-4" data-aos="fade-up" data-aos-delay="500">

            <p class="font-body text-lg tracking-wider">Undangan Pernikahan</p>
            <h1 class="font-display text-5xl md:text-7xl my-4">
                {{ event.groom.nickname }} & {{ event.bride.nickname }}
            </h1>
            <p class="font-semibold">{{ eventDate }}</p>


            <button v-if="!isInvitationOpen"
                @click="openInvitation"
                class="mt-8 px-6 py-3 bg-white text-gray-800 font-semibold rounded-full shadow-lg hover:bg-gray-200 transition-transform transform hover:scale-105"
            >

                Buka Undangan
            </button>
            <a
                    v-else
                    :href="route('invitation.save-calendar', event.slug)"
                    class="inline-block px-6 py-3 bg-white/80 text-gray-800 font-semibold rounded-full shadow-lg hover:bg-gray-200 transition-transform transform hover:scale-105"
                ><font-awesome-icon icon="calendar-check" class="w-5 h-5" />
                    Simpan ke Kalender
                </a>

        </div>
        <div v-if="guestName" class="absolute bottom-24 left-0 right-0 z-10 text-center px-4">
            <div class="inline-block bg-black bg-opacity-30 backdrop-blur-sm p-3 rounded-lg border border-white/20">
                <p class="font-body text-sm text-white/80 mb-1">Kepada Yth. Bapak/Ibu/Saudara/i:</p>
                <p class="font-heading text-2xl text-white">{{ guestName }}</p>
            </div>
        </div>
        <div v-if="isInvitationOpen" class="absolute bottom-10 left-1/2 -translate-x-1/2 z-10">
                <div class="w-8 h-12 border-2 border-slate-300 rounded-full flex justify-center pt-2">
                    <div class="w-1 h-2 bg-slate-300 rounded-full animate-bounce">
                    </div>
                </div>

            </div>
        <div class="absolute inset-0 bg-black opacity-50"></div>
    </div>
</template>
