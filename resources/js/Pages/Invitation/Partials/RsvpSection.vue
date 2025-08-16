<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue'; // Impor watch
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    event: Object,
    guestName: String,
    existingGuest: { // Buat prop opsional
        type: Object,
        default: null
    },
});

const form = useForm({
    name: props.guestName || '',
    confirmation_status: 'attending',
    message: '',
});

watch(() => props.guestName, (newName) => {
    form.name = newName;
});

const submit = () => {
    form.post(route('invitation.rsvp.store', props.event.slug), {
        preserveScroll: true,
        onSuccess: () => {
            // Kita tidak me-reset form di sini karena user akan di-redirect
            // ke halaman sukses dengan QR Code.
        },
    });
};
</script>

<template>
    <section id="rsvp" class="p-8 sm:p-12 bg-white" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-display text-5xl mt-2" style="color: var(--color-text-dark);">
                {{ existingGuest ? 'QR Code Anda' : 'Konfirmasi Kehadiran' }}
            </h2>
            <p class="mt-2 font-body text-md" style="color: var(--color-text-muted);">
                {{ existingGuest ? 'Terima kasih telah melakukan konfirmasi. Simpan QR Code ini untuk check-in.' : 'Kami sangat mengharapkan kehadiran Anda.' }}
            </p>
        </div>

        <div v-if="existingGuest" class="flex flex-col items-center">
            <p class="font-body font-semibold text-lg" style="color: var(--color-primary);">{{ existingGuest.name }}</p>
            <div class="my-4 inline-block p-4 bg-white rounded-lg shadow-lg">
                <qrcode-vue :text="existingGuest.qr_code_token" :size="200" />
            </div>
             <a :href="route('invitation.rsvp.edit', existingGuest.qr_code_token)" class="text-sm text-indigo-600 hover:underline">
                Ubah Jawaban atau Ucapan?
            </a>
        </div>

        <form v-else @submit.prevent="submit" class="max-w-md mx-auto space-y-4">
            <div v-if="form.wasSuccessful" class="p-4 bg-green-100 text-green-800 rounded-md">
                Terima kasih! Konfirmasi Anda telah terkirim. Anda akan segera dialihkan...
            </div>

            <div>
                <label for="name" class="block text-sm font-medium" style="color: var(--color-text-dark);">Nama Anda</label>
                <input id="name" v-model="form.name" type="text" required class="form-input" />
                <div v-if="form.errors.name" class="text-sm text-red-500 mt-1">{{ form.errors.name }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium" style="color: var(--color-text-dark);">Konfirmasi Kehadiran</label>
                <div class="mt-2 flex space-x-4">
                    <div class="flex items-center">
                        <input id="attending" type="radio" value="attending" v-model="form.confirmation_status" class="form-radio">
                        <label for="attending" class="ml-2">Ya, saya akan hadir</label>
                    </div>
                    <div class="flex items-center">
                        <input id="not_attending" type="radio" value="not_attending" v-model="form.confirmation_status" class="form-radio">
                        <label for="not_attending" class="ml-2">Maaf, tidak bisa hadir</label>
                    </div>
                </div>
            </div>

            <div>
                <label for="message" class="block text-sm font-medium" style="color: var(--color-text-dark);">Ucapan & Doa</label>
                <textarea id="message" v-model="form.message" rows="4" class="form-input"></textarea>
                <div v-if="form.errors.message" class="text-sm text-red-500 mt-1">{{ form.errors.message }}</div>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full px-6 py-3 text-sm font-semibold rounded-full shadow-lg transition-transform transform hover:scale-105 disabled:opacity-50 disabled:scale-100"
                    :style="{ backgroundColor: 'var(--color-primary)', color: 'var(--color-text-light)' }"
                >
                    {{ form.processing ? 'Mengirim...' : 'Kirim Konfirmasi' }}
                </button>
            </div>
        </form>
    </section>
</template>

<style scoped>
.form-input {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
.form-radio {
    @apply h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500;
}
</style>
