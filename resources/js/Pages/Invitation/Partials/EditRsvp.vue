<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    guest: Object, // Menerima data guest dari controller
});

// Inisialisasi form dengan data yang sudah ada dari props.guest
const form = useForm({
    name: props.guest.name,
    confirmation_status: props.guest.confirmation_status,
    message: props.guest.message || '', // Pastikan nilai awal tidak null
});

const submit = () => {
    // Gunakan method PUT untuk mengirim data update
    form.put(route('invitation.rsvp.update', props.guest.qr_code_token));
};
</script>

<template>
    <Head title="Edit Konfirmasi Kehadiran" />

    <section id="edit-rsvp" class="min-h-screen flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h2 class="font-heading text-4xl" style="color: var(--color-text-dark);">
                    Ubah Konfirmasi
                </h2>
                <p class="mt-2 font-body text-md" style="color: var(--color-text-muted);">
                    Silakan perbarui jawaban atau ucapan Anda.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="name" class="form-label">Nama Anda</label>
                    <input id="name" v-model="form.name" type="text" required class="form-input" />
                    <div v-if="form.errors.name" class="form-error">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="form-label">Konfirmasi Kehadiran</label>
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
                    <label for="message" class="form-label">Ucapan & Doa</label>
                    <textarea id="message" v-model="form.message" rows="4" class="form-input"></textarea>
                    <div v-if="form.errors.message" class="form-error">{{ form.errors.message }}</div>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary w-full">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<style scoped>
/* Anda bisa menggunakan style yang sama dengan RsvpSection */
.form-label {
    @apply block text-sm font-medium text-gray-700;
}
.form-input {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
.form-radio {
    @apply h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500;
}
.form-error {
    @apply text-sm text-red-500 mt-1;
}
.btn-primary {
    @apply px-6 py-3 text-sm font-semibold rounded-full shadow-lg transition-transform transform hover:scale-105 disabled:opacity-50 disabled:scale-100;
    background-color: var(--color-primary);
    color: var(--color-text-light);
}
</style>
