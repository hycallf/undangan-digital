<script setup>
defineProps({
    event: Object,
});
</script>

<template>
    <section id="closing" class="p-8 sm:p-12 text-center" style="background-color: var(--color-secondary);" data-aos="fade-up">
        <div
            v-if="event.details.closing_text"
            class="prose max-w-none"
            style="color: var(--color-text-muted); font-family: var(--font-body);"
            v-html="event.details.closing_text"
        >
        </div>
        <div class="mt-8">
            <p class="font-body text-md" style="color: var(--color-text-dark);">Hormat kami yang berbahagia,</p>
            <h3 class="mt-4 text-4xl" style="font-family: var(--font-display); color: var(--color-primary);">
                {{ event.groom.nickname }} & {{ event.bride.nickname }}
            </h3>
        </div>
    </section>
</template>
