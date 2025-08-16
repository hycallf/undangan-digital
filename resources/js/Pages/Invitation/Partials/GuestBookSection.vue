<script setup>
defineProps({
    guests: Array,
});
</script>

<template>
    <section id="guestbook" class="p-8 sm:p-12" style="background-color: var(--color-secondary);" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-heading text-4xl mt-2" style="color: var(--color-text-dark);">
                Buku Tamu
            </h2>
            <p class="mt-2 font-body text-md" style="color: var(--color-text-muted);">
                Ucapan dan doa dari sahabat dan kerabat.
            </p>
        </div>

        <div class="max-w-md mx-auto h-96 overflow-y-auto space-y-4 p-4 bg-white rounded-lg shadow-inner">
            <div v-if="guests && guests.length > 0">
                <div v-for="guest in guests" :key="guest.id" class="p-4 border-b">
                    <div class="flex items-center justify-between">
                        <p class="font-body font-bold text-md" style="color: var(--color-primary);">{{ guest.name }}</p>

                        <span v-if="guest.confirmation_status === 'attending'" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Hadir
                        </span>
                        <span v-else class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                            Tidak Hadir
                        </span>
                    </div>

                    <p class="font-body text-sm mt-2" style="color: var(--color-text-dark);">{{ guest.message }}</p>
                </div>
            </div>
            <div v-else class="flex items-center justify-center h-full">
                <p class="text-gray-400">Jadilah yang pertama memberikan ucapan!</p>
            </div>
        </div>
    </section>
</template>
