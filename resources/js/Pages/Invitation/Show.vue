<script setup>
import { Head } from '@inertiajs/vue3';
// Impor semua komponen bagian
import HeroSection from './Templates/Ulems/HeroSection.vue';
import CoupleSection from './Partials/CoupleSection.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    event: Object,
});

const existingToken = ref(null);
onMounted(() => {
    existingToken.value = localStorage.getItem(`rsvp_token_${props.event.id}`);
});

// State untuk mengontrol apakah undangan sudah "dibuka"
const isInvitationOpen = ref(false);
const audioPlayer = ref(null); // Ref untuk elemen audio

const handleOpenInvitation = () => {
    isInvitationOpen.value = true;

    // Putar musik jika ada
    if (audioPlayer.value) {
        audioPlayer.value.play();
    }

    // Scroll ke bagian selanjutnya (opsional)
    // document.getElementById('couple-section').scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <Head :title="`Undangan Pernikahan ${event.groom.nickname} & ${event.bride.nickname}`" />

    <audio v-if="event.details.music_path" :src="`/storage/${event.details.music_path}`" autoplay loop></audio>

    <main>
        <HeroSection v-if="!isInvitationOpen" :event="event" @open-invitation="handleOpenInvitation" />

        <div v-if="isInvitationOpen">
            <CoupleSection id="couple-section" :event="event" />

        </div>
        <!-- <RsvpSection :event="event" />
        <GuestBookSection :guests="event.guests" /> -->
    </main>
</template>
