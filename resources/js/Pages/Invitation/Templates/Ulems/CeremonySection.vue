<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    event: Object,
});

// State untuk countdown timer
const days = ref('00');
const hours = ref('00');
const minutes = ref('00');
const seconds = ref('00');

let countdownInterval;

// Helper untuk format tanggal & waktu
const formatFullDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
};

const formatTime = (timeString) => {
    if (!timeString) return '';
    // Konversi 'HH:mm:ss' ke 'HH:mm'
    const [h, m] = timeString.split(':');
    return `${h}:${m}`;
};

const updateCountdown = () => {
    // Ambil tanggal dari acara pertama
    const firstCeremony = props.event.ceremonies[0];
    if (!firstCeremony) return;

    const targetDate = new Date(`${firstCeremony.ceremony_date}T${firstCeremony.start_time}`);
    const now = new Date();
    const distance = targetDate.getTime() - now.getTime();

    if (distance < 0) {
        clearInterval(countdownInterval);
        return;
    }

    days.value = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
    hours.value = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
    minutes.value = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
    seconds.value = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');
};

onMounted(() => {
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    clearInterval(countdownInterval);
});
</script>

<template>
    <section id="ceremony" class="p-8 sm:p-12 bg-white" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-display text-5xl mt-2 text-[--color-text-dark]">
                Moment Bahagia
            </h2>
        </div>

        <div class="flex justify-center space-x-2 sm:space-x-4 text-center mb-12">
            <div class="countdown-box">
                <span class="countdown-number">{{ days }}</span>
                <span class="countdown-label">Hari</span>
            </div>
            <div class="countdown-box">
                <span class="countdown-number">{{ hours }}</span>
                <span class="countdown-label">Jam</span>
            </div>
            <div class="countdown-box">
                <span class="countdown-number">{{ minutes }}</span>
                <span class="countdown-label">Menit</span>
            </div>
            <div class="countdown-box">
                <span class="countdown-number">{{ seconds }}</span>
                <span class="countdown-label">Detik</span>
            </div>
        </div>

        <div class="space-y-8 max-w-md mx-auto">
            <div v-for="ceremony in event.ceremonies" :key="ceremony.id" class="text-center" data-aos="fade-up" data-aos-delay="200">
                <h3 class="font-display text-4xl text-[--color-primary]">{{ ceremony.name }}</h3>
                <p class="font-semibold text-lg mt-2 text-[--color-text-dark]">
                    {{ formatFullDate(ceremony.ceremony_date) }}
                </p>
                <p class="text-md text-[--color-text-muted]">
                    Pukul {{ formatTime(ceremony.start_time) }} - {{ ceremony.end_time ? formatTime(ceremony.end_time) : 'Selesai' }}
                </p>
                <div class="mt-4 text-sm text-[--color-text-dark]">
                    <p class="font-bold">{{ ceremony.location }}</p>
                    <p>{{ ceremony.address }}</p>
                </div>

                <a
                    v-if="ceremony.maps_url"
                    :href="ceremony.maps_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-block mt-4 px-5 py-2 bg-[--color-primary] text-[--color-text-light] text-sm font-semibold rounded-full shadow-lg hover:opacity-90 transition"
                >
                    Lihat Peta Lokasi
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.countdown-box {
    @apply flex flex-col items-center justify-center bg-[--color-secondary] w-20 h-20 rounded-lg shadow-md;
}
.countdown-number {
    @apply font-display text-3xl font-bold text-[--color-primary];
}
.countdown-label {
    font-family: var(--font-body, inherit);
    font-size: 0.75rem; /* text-xs */
    color: var(--color-text-muted);
}
</style>
