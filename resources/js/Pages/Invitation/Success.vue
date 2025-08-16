<script setup>
import { Head } from '@inertiajs/vue3';
import QrCode from 'vue-qrcode-component';

const props = defineProps({
    guest: Object,
});
</script>

<template>
    <Head :title="`Konfirmasi Berhasil - ${guest.event.groom.nickname} & ${guest.event.bride.nickname}`" />

    <section id="success-page" class="min-h-screen flex flex-col items-center justify-center text-center p-8 bg-white" data-aos="fade-in">
        <div class="max-w-md">
            <h2 class="text-3xl font-display text-gray-800 mb-4">Terima Kasih!</h2>
            <p class="text-gray-600">
                Konfirmasi kehadiran atas nama <strong>{{ guest.name }}</strong> telah kami terima.
            </p>
            <p class="mt-6 text-gray-700 font-semibold">
                Silakan tunjukkan QR Code di bawah ini di meja resepsionis sebagai akses masuk Anda.
            </p>

            <div class="my-8 inline-block p-4 bg-white rounded-lg shadow-lg">
                <QrCode :text="guest.qr_code_token" :size="200" />
            </div>

            <p class="text-sm text-gray-500">
                Sampai jumpa di hari bahagia kami!
            </p>
        </div>
    </section>
</template>

<style scoped>
.font-display {
    font-family: 'Playfair Display', serif; /* Sesuaikan dengan font Anda */
}
</style>
