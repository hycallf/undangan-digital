<script setup>
import { ref, watch, nextTick} from 'vue';
import { Head } from '@inertiajs/vue3';
import InvitationLayout from '@/Layouts/InvitationLayout.vue';
import BottomNav from '../../Partials/BottomNav.vue';

import { faMusic, faPause } from '@fortawesome/free-solid-svg-icons';
// Komponen Bagian Undangan (Partials)
import HeroSection from './HeroSection.vue';
import CoupleSection from './CoupleSection.vue';
import QuoteSection from './QuoteSection.vue';
import StorySection from './StorySection.vue';
import CeremonySection from './CeremonySection.vue';
import GallerySection from './GallerySection.vue';
import ClosingSection from './ClosingSection.vue';
import RsvpSection from '../../Partials/RsvpSection.vue';
import GuestBookSection from '../../Partials/GuestBookSection.vue';
import Footer from '../../Partials/Footer.vue';
import SectionSeparator from './SectionSeparator.vue';

const props = defineProps({
    event: Object,
    themeStyle: Object,
    guestName: String,
    existingGuest: Object,
});
// --- STATE MANAGEMENT ---
const isInvitationOpen = ref(false);
const audioPlayer = ref(null);
const isPlaying = ref(false);
const activeSection = ref('hero'); // Default section aktif


// --- FUNGSI-FUNGSI ---
const handleOpenInvitation = () => {
    isInvitationOpen.value = true;
    if (audioPlayer.value) {
        audioPlayer.value.play();
        isPlaying.value = true;
    }
};

const toggleMusic = () => {
    if (!audioPlayer.value) return;
    if (audioPlayer.value.paused) {
        audioPlayer.value.play();
        isPlaying.value = true;
    } else {
        audioPlayer.value.pause();
        isPlaying.value = false;
    }
};

watch(isInvitationOpen, (isNowOpen) => {
    // Jalankan hanya saat undangan DIBUKA (nilainya menjadi true)
    if (isNowOpen) {
        // nextTick() akan menunggu hingga Vue selesai memperbarui DOM
        // (yaitu, setelah semua section di dalam v-if ditampilkan)
        nextTick(() => {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            activeSection.value = entry.target.id;
                        }
                    });
                },
                // Opsi: section dianggap aktif jika 40% bagiannya terlihat
                { threshold: 0.4 }
            );

            // Ambil semua elemen <section> yang memiliki ID dan amati mereka
            const sections = document.querySelectorAll('section[id]');
            sections.forEach((section) => observer.observe(section));
        });
    }
});

</script>

<template>
    <InvitationLayout :event="event" :themeStyle="themeStyle">
        <Head :title="`Undangan Pernikahan ${event.groom.nickname} & ${event.bride.nickname}`" />

        <audio ref="audioPlayer" v-if="event.details.music_path" :src="`/storage/${event.details.music_path}`" loop></audio>

        <main>
            <section id="hero">
                <HeroSection
                    :event="event"
                    :guest-name="guestName"
                    :is-invitation-open="isInvitationOpen"
                    @open-invitation="handleOpenInvitation"
                />
            </section>

            <div v-if="isInvitationOpen">
                <section id="couple"><CoupleSection :event="event" />
                    <SectionSeparator :imageName="'Separator.jpeg'" />
                    <QuoteSection :event="event" />
                    <SectionSeparator :imageName="'Separator.jpeg'" :flip="true" />
                    <StorySection :event="event" />
                </section>
                <section id="ceremony"><CeremonySection :event="event" /></section>
                <section id="gallery"><GallerySection :event="event" /></section>
                <section id="rsvp">
                    <RsvpSection :event="event" :guest-name="guestName" :existing-guest="existingGuest"/>
                    <GuestBookSection :guests="event.guests" />
                    <ClosingSection :event="event" />
                </section>
                <Footer />
            </div>
        </main>

        <div v-if="isInvitationOpen">
            <div v-if="event?.details?.music_path" class="fixed bottom-20 right-4 z-topmost">
                 <button @click="toggleMusic" class="w-12 h-12 bg-gray-800 bg-opacity-50 text-white rounded-full flex items-center justify-center shadow-lg">
                    <font-awesome-icon :icon="isPlaying ? faPause : faMusic" />
                </button>
            </div>
            <BottomNav :active-section="activeSection" class="z-topmost" />
        </div>
    </InvitationLayout>
</template>
