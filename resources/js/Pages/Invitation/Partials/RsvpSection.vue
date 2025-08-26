<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, ref } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    event: Object,
    guestName: String,
    existingGuest: {
        type: Object,
        default: null
    },
});

const showQr = ref(false)

const form = useForm({
    name: props.guestName || '',
    phone: '',
    confirmation_status: '',
    message: '',
});

// Watch untuk update form ketika guestName berubah
watch(() => props.guestName, (newName) => {
    form.name = newName;
});

// Jika ada existing guest, populate form dengan data existing
watch(() => props.existingGuest, (guest) => {
    if (guest) {
        form.name = guest.name || '';
        form.phone = guest.phone || '';
        form.confirmation_status = guest.confirmation_status || '';
        form.message = guest.message || '';
    }
}, { immediate: true });

const submit = () => {
    form.post(route('invitation.rsvp.store', props.event.slug), {
        preserveScroll: true,
        onSuccess: () => {
            // Kita tidak me-reset form di sini karena user akan di-redirect
            // ke halaman sukses dengan QR Code.
        },
    });
};

const getConfirmationBadgeClass = (status) => {
    switch(status) {
        case 'confirmed':
            return 'px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200';
        case 'declined':
            return 'px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 border border-red-200';
        case 'pending':
            return 'px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200';
        default:
            return 'px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200';
    }
};

const getConfirmationText = (status) => {
    switch(status) {
        case 'confirmed':
            return 'Hadir';
        case 'declined':
            return 'Tidak Hadir';
        case 'pending':
            return 'Pending';
        default:
            return 'Belum Dikonfirmasi';
    }
};
</script>

<template>
    <section id="rsvp" class="p-8 sm:p-12 bg-white" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-display text-5xl mt-2" style="color: var(--color-text-dark);">
                {{ existingGuest ? 'Konfirmasi Anda' : 'Konfirmasi Kehadiran' }}
            </h2>
            <p class="mt-2 font-body text-md" style="color: var(--color-text-muted);">
                {{ existingGuest ? 'Terima kasih telah melakukan konfirmasi.' : 'Kami sangat mengharapkan kehadiran Anda.' }}
            </p>
        </div>

        <!-- Tampilkan status jika ada existing guest -->
        <div v-if="existingGuest && existingGuest.confirmation_status === 'confirmed'" class="max-w-md mx-auto mb-8">
            <div class="bg-gray-50 rounded-lg p-6 text-center">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ existingGuest.name }}</h3>
                    <div class="flex justify-center mb-3">
                        <span :class="getConfirmationBadgeClass(existingGuest.confirmation_status)">
                            {{ getConfirmationText(existingGuest.confirmation_status) }}
                        </span>
                    </div>
                    <p v-if="existingGuest.phone" class="text-sm text-gray-600 mb-2">
                        📱 {{ existingGuest.phone }}
                    </p>
                    <p v-if="existingGuest.message" class="text-sm text-gray-700 italic bg-white p-3 rounded border">
                        "{{ existingGuest.message }}"
                    </p>
                </div>

                <!-- QR Code untuk guest yang confirmed -->
                <div v-if="existingGuest.confirmation_status === 'confirmed'" class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-3">QR Code untuk Check-in:</p>
                    <div @click="showQr = true" class="inline-block p-3 bg-white rounded-lg shadow-sm border cursor-pointer hover:shadow-md transition-shadow">
                        <qrcode-vue :value="existingGuest.qr_code_token" :size="150" level="H"/>
                        <p class="mt-2 text-xs text-gray-500">Klik untuk memperbesar</p>
                    </div>
                </div>

                <!-- Link edit -->
                <div class="mt-4 pt-4 border-t">
                    <a :href="route('invitation.rsvp.edit', existingGuest.qr_code_token)"
                       class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                        ✏️ Ubah Konfirmasi atau Ucapan
                    </a>
                </div>
            </div>
        </div>

        <!-- Form RSVP untuk guest baru atau yang belum konfirmasi -->
        <form v-else @submit.prevent="submit" class="max-w-md mx-auto space-y-4">
            <div v-if="form.wasSuccessful" class="p-4 bg-green-100 text-green-800 rounded-md">
                ✅ Terima kasih! Konfirmasi Anda telah terkirim. Anda akan segera dialihkan...
            </div>

            <div>
                <label for="name" class="block text-sm font-medium mb-1" style="color: var(--color-text-dark);">
                    Nama Lengkap *
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="form-input"
                    placeholder="Masukkan nama lengkap Anda"
                    :disabled="!!existingGuest"
                />
                <div v-if="form.errors.name" class="text-sm text-red-500 mt-1">{{ form.errors.name }}</div>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium mb-1" style="color: var(--color-text-dark);">
                    Nomor Telepon
                </label>
                <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    class="form-input"
                    placeholder="Contoh: +62812345678 (opsional)"
                    :disabled="!!existingGuest"
                />
                <div v-if="form.errors.phone" class="text-sm text-red-500 mt-1">{{ form.errors.phone }}</div>
                <p class="text-xs text-gray-500 mt-1">Format: +62 diikuti nomor HP</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--color-text-dark);">
                    Konfirmasi Kehadiran *
                </label>
                <div class="space-y-3">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                           :class="form.confirmation_status === 'confirmed' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                        <input
                            id="confirmed"
                            type="radio"
                            value="confirmed"
                            v-model="form.confirmation_status"
                            class="form-radio text-green-600"
                        />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">✅ Ya, saya akan hadir</span>
                            <p class="text-xs text-gray-600">Kami sangat menantikan kehadiran Anda</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                           :class="form.confirmation_status === 'declined' ? 'border-red-500 bg-red-50' : 'border-gray-300'">
                        <input
                            id="declined"
                            type="radio"
                            value="declined"
                            v-model="form.confirmation_status"
                            class="form-radio text-red-600"
                        />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">❌ Maaf, tidak bisa hadir</span>
                            <p class="text-xs text-gray-600">Doa dan ucapan Anda tetap bermakna untuk kami</p>
                        </div>
                    </label>
                </div>
                <div v-if="form.errors.confirmation_status" class="text-sm text-red-500 mt-1">
                    {{ form.errors.confirmation_status }}
                </div>
            </div>

            <div>
                <label for="message" class="block text-sm font-medium mb-1" style="color: var(--color-text-dark);">
                    Ucapan & Doa
                </label>
                <textarea
                    id="message"
                    v-model="form.message"
                    rows="4"
                    class="form-input"
                    placeholder="Tulis ucapan selamat dan doa terbaik untuk kami... (opsional)"
                ></textarea>
                <div v-if="form.errors.message" class="text-sm text-red-500 mt-1">{{ form.errors.message }}</div>
                <p class="text-xs text-gray-500 mt-1">Maksimal 1000 karakter</p>
            </div>

            <div class="pt-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full px-6 py-4 text-sm font-semibold rounded-full shadow-lg transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:scale-100 disabled:cursor-not-allowed"
                    :style="{
                        backgroundColor: 'var(--color-primary)',
                        color: 'var(--color-text-light)'
                    }"
                >
                    <span v-if="form.processing" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mengirim...
                    </span>
                    <span v-else>
                        📤 Kirim Konfirmasi
                    </span>
                </button>
            </div>
        </form>
    </section>

    <!-- QR Code Popup Modal -->
    <transition name="zoom">
        <div v-if="showQr"
             class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
             @click.self="showQr = false">
            <div class="relative p-6 bg-white rounded-2xl shadow-xl max-w-md mx-4">
                <!-- Tombol Close -->
                <button @click="showQr = false"
                        class="absolute -top-3 -right-3 bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg transition-colors">
                    ✕
                </button>

                <!-- Header -->
                <div class="text-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">QR Code Check-in</h3>
                    <p class="text-sm text-gray-600">{{ existingGuest?.name }}</p>
                </div>

                <!-- QR Code Besar -->
                <div class="flex justify-center p-4">
                    <qrcode-vue :value="existingGuest?.qr_code_token" :size="280" level="H" />
                </div>

                <!-- Footer -->
                <div class="text-center text-xs text-gray-500">
                    Tunjukkan QR Code ini saat check-in di lokasi acara
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.form-input {
    @apply mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors;
}

.form-radio {
    @apply h-4 w-4 border-gray-300 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

/* Animasi Zoom untuk QR popup */
.zoom-enter-active, .zoom-leave-active {
    transition: all 0.3s ease;
}
.zoom-enter-from, .zoom-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

/* Hover effects */
.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
