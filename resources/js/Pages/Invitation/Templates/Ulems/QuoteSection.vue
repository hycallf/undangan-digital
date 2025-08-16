<script setup>
defineProps({
    event: Object,
});
</script>

<template>
    <section id="quote" class="p-8 sm:p-12 bg-white" data-aos="fade-up" v-if="event.quotes && event.quotes.length > 0">
        <div class="max-w-md mx-auto text-center space-y-6">
            <div v-for="quote in event.quotes" :key="quote.id">
                <p
                    class="font-body text-md italic text-[--color-text-muted]"
                >
                    "{{ quote.content }}"
                </p>
                <p v-if="quote.source" class="font-body font-semibold text-md text-[--color-primary] mt-2">
                    - {{ quote.source }} -
                </p>
            </div>
        </div>
    </section>
</template>
