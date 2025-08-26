<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InvitationLayout from '@/Layouts/InvitationLayout.vue';

const props = defineProps({
    guest: Object,
});

// Computed untuk mendapatkan theme style dari event template
const themeStyle = computed(() => {
    return props.guest.event?.template?.default_options || {};
});

const form = useForm({
    name: props.guest.name || '',
    phone: props.guest.phone || '',
    confirmation_status: props.guest.confirmation_status || '',
    message: props.guest.message || '',
});

const submit = () => {
    form.put(route('invitation.rsvp.update', props.guest.qr_code_token), {
        preserveScroll: true,
        onSuccess: () => {
            // Will redirect to success page
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
            return 'Akan Hadir';
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
    <InvitationLayout :event="guest.event" :themeStyle="themeStyle">
        <Head :title="`Edit RSVP - ${guest.event.event_name}`" />

        <main class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
              style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">

            <div class="max-w-md w-full space-y-8">
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-amber-800 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-extrabold text-black">
                        Edit Konfirmasi
                    </h2>
                    <p class="mt-2 text-sm text-black text-opacity-80">
                        {{ guest.event.event_name }}
                    </p>
                </div>

                <div class="bg-sky-400 bg-opacity-20 backdrop-blur-sm rounded-xl p-6 border border-white border-opacity-20">
                    <h3 class="text-lg font-medium text-black mb-4 text-center">Status Saat Ini</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-black text-opacity-80">Nama:</span>
                            <span class="text-black font-medium">{{ guest.name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-black text-opacity-80">Telepon:</span>
                            <span class="text-black font-medium">{{ guest.phone || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-black text-opacity-80">Status:</span>
                            <span :class="getConfirmationBadgeClass(guest.confirmation_status)">
                                {{ getConfirmationText(guest.confirmation_status) }}
                            </span>
                        </div>
                        <div v-if="guest.message" class="pt-2 border-t border-black border-opacity-20">
                            <span class="text-black text-opacity-80 text-sm">Ucapan:</span>
                            <p class="text-black text-sm mt-1 italic">"{{ guest.message }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="bg-white bg-opacity-95 backdrop-blur-sm rounded-xl shadow-xl p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Success Message -->
                        <div v-if="form.wasSuccessful" class="p-4 bg-green-100 text-green-800 rounded-lg border border-green-200">
                            ✅ Perubahan berhasil disimpan! Anda akan dialihkan...
                        </div>

                        <!-- Name Field -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="form-input"
                                placeholder="Masukkan nama lengkap"
                            />
                            <div v-if="form.errors.name" class="text-sm text-red-500 mt-1">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Phone Field -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Nomor Telepon
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="form-input"
                                placeholder="Contoh: +62812345678"
                            />
                            <div v-if="form.errors.phone" class="text-sm text-red-500 mt-1">
                                {{ form.errors.phone }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Format: +62 diikuti nomor HP</p>
                        </div>

                        <!-- Confirmation Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Konfirmasi Kehadiran <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                       :class="form.confirmation_status === 'confirmed' ? 'border-green-500 bg-green-50' : 'border-gray-200'">
                                    <input
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

                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                       :class="form.confirmation_status === 'declined' ? 'border-red-500 bg-red-50' : 'border-gray-200'">
                                    <input
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
                            <div v-if="form.errors.confirmation_status" class="text-sm text-red-500 mt-2">
                                {{ form.errors.confirmation_status }}
                            </div>
                        </div>

                        <!-- Message Field -->
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                Ucapan & Doa
                            </label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="4"
                                class="form-input"
                                placeholder="Tulis ucapan selamat dan doa terbaik untuk kami... (opsional)"
                            ></textarea>
                            <div v-if="form.errors.message" class="text-sm text-red-500 mt-1">
                                {{ form.errors.message }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Maksimal 1000 karakter</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-4 pt-6">
                            <Link :href="route('invitation.success', guest.qr_code_token)"
                                  class="flex-1 px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors text-center">
                                🔙 Kembali
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-6 py-3 text-sm font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:scale-100 disabled:cursor-not-allowed text-center"
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
                                    Menyimpan...
                                </span>
                                <span v-else>
                                    💾 Simpan Perubahan
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer Info -->
                <div class="text-center">
                    <p class="text-white text-opacity-80 text-xs">
                        Perubahan akan tersimpan dan QR Code tetap dapat digunakan
                    </p>
                </div>
            </div>
        </main>
    </InvitationLayout>
</template>

<style scoped>
.form-input {
    @apply mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors px-4 py-3;
}

.form-radio {
    @apply h-4 w-4 border-gray-300 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

/* Background pattern */
main {
    background-image:
        radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
}

/* Glass morphism effect */
.backdrop-blur-sm {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/* Hover effects */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Animation for form */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.max-w-md {
    animation: fadeInUp 0.6s ease-out;
}

/* Radio button focus styles */
input[type="radio"]:checked {
    @apply bg-current border-current;
}

input[type="radio"]:focus {
    @apply ring-2 ring-offset-2;
}

/* Custom scrollbar for textarea */
textarea::-webkit-scrollbar {
    width: 6px;
}

textarea::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
