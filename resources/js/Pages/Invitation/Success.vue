<script setup>
import { Head, Link } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';
import { ref } from 'vue'

const props = defineProps({
    guest: Object,

});

const showQr = ref(false)
</script>W

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

            <div @click="showQr = true"
                 class="my-8 inline-block p-4 bg-white rounded-lg shadow-lg cursor-pointer hover:scale-105 transition">
                <qrcode-vue :value="guest.qr_code_token" :size="200" level="H" />
                <p class="mt-2 text-xs text-gray-500">Klik untuk memperbesar</p>
            </div>

            <p class="text-sm text-gray-500">
                Sampai jumpa di hari bahagia kami!
            </p>

            <div class="mt-8">
                <Link
                    :href="`${route('invitation.show', guest.event.slug)}?to=${encodeURIComponent(guest.unique_identifier)}`"
                    class="px-5 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-full hover:bg-gray-200 transition"
                >
                    Kembali ke Undangan untuk {{ guest.name }}
                </Link>
            </div>
        </div>
    </section>

    <transition name="zoom">
        <div v-if="showQr"
             class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
             @click.self="showQr = false">
            <div class="relative p-6 bg-white rounded-2xl shadow-xl">
                <!-- Tombol Close -->
                <button @click="showQr = false"
                        class="absolute -top-3 -right-3 bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-md hover:bg-red-600">
                    ✕
                </button>

                <!-- QR Besar -->
                <qrcode-vue :value="guest.qr_code_token" :size="400" level="H" />
            </div>
        </div>
    </transition>
</template>

<style scoped>
.font-display {
    font-family: 'Playfair Display', serif; /* Sesuaikan dengan font Anda */
}
/* Animasi Zoom */
.zoom-enter-active, .zoom-leave-active {
  transition: all 0.3s ease;
}
.zoom-enter-from, .zoom-leave-to {
  opacity: 0;
  transform: scale(0.8);
}
</style>
