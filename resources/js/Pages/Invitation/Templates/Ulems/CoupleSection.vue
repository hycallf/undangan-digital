<script setup>
defineProps({
    event: Object,
});
</script>

<template>
    <section id="couple" class="p-8 sm:p-12" style="background-color: var(--color-secondary);">
        <div class="text-center mb-8">
            <div
                v-if="event.details.opening_text"
                class="prose max-w-none"
                style="color: var(--color-text-muted); font-family: var(--font-body);"
                v-html="event.details.opening_text"
            ></div>

            <h2 class="mt-4 text-4xl" style="font-family: var(--font-display); color: var(--color-text-dark);">
                Pasangan Mempelai
            </h2>
        </div>

        <div class="space-y-10">
            <div class="text-center" data-aos="fade-right">
                <img :src="event.groom.photo_path ? `/storage/${event.groom.photo_path}` : 'https://via.placeholder.com/150'" :alt="event.groom.name" class="w-40 h-40 object-cover rounded-full mx-auto shadow-lg border-4 border-white" />
                <h3 class="mt-4 text-3xl" style="font-family: var(--font-display); color: var(--color-primary);">
                    {{ event.groom.name }}
                </h3>
                <div
                    class="mt-2 text-sm text-[--color-text-muted] prose max-w-none"
                    style="font-family: var(--font-body);"
                    v-html="event.groom.family_details"
                ></div>
            </div>

            <div class="text-4xl text-center" style="font-family: var(--font-display); color: var(--color-primary);" data-aos="fade-up">
                &
            </div>

            <div class="text-center" data-aos="fade-left">
                <img :src="event.bride.photo_path ? `/storage/${event.bride.photo_path}` : 'https://via.placeholder.com/150'" :alt="event.bride.name" class="w-40 h-40 object-cover rounded-full mx-auto shadow-lg border-4 border-white" />
                <h3 class="mt-4 text-3xl" style="font-family: var(--font-display); color: var(--color-primary);">
                    {{ event.bride.name }}
                </h3>
                <div
                    class="mt-2 text-sm text-[--color-text-muted] prose max-w-none"
                    style="font-family: var(--font-body);"
                    v-html="event.bride.family_details"
                ></div>
            </div>
        </div>
    </section>
</template>
