<script setup>
import { computed } from 'vue';

const props = defineProps({
    event: Object,
});

// --- LOGIKA PARSING HTML ---

// Computed property untuk memisahkan media utama (video/gambar)
const mainMediaHtml = computed(() => {
    if (!props.event.details.story_text) return '';
    const match = props.event.details.story_text.match(/<figure class="media">.*?<\/figure>/);
    return match ? match[0] : '';
});

// Computed property untuk mengambil setiap item <li> dari <ol>
const timelineItems = computed(() => {
    if (!props.event.details.story_text) return [];

    const olMatch = props.event.details.story_text.match(/<ol>(.*?)<\/ol>/s);
    if (!olMatch) return [];

    const liMatches = [...olMatch[1].matchAll(/<li.*?>(.*?)<\/li>/gs)];
    return liMatches.map(match => match[1]); // Ambil konten di dalam <li>
});
</script>

<template>
    <section id="story" class="p-8 sm:p-12" style="background-color: var(--color-secondary);" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-heading text-4xl mt-2" style="color: var(--color-text-dark);">
                Kisah Cinta
            </h2>
        </div>

        <div class="max-w-md mx-auto">
            <div
                v-if="mainMediaHtml"
                class="prose max-w-none mb-12 rounded-lg overflow-hidden shadow-lg"
                v-html="mainMediaHtml"
            ></div>

            <div class="relative border-l-2 border-gray-200" style="border-color: var(--color-primary-light, #e5e7eb);">
                <div v-for="(item, index) in timelineItems" :key="index" class="mb-10 ml-6">
                    <span class="absolute flex items-center justify-center w-6 h-6 bg-white rounded-full -left-3 ring-4 ring-white" :style="{ borderColor: 'var(--color-primary)' }">
                        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: 'var(--color-primary)' }"></span>
                    </span>

                    <div
                        class="prose max-w-none font-body text-sm"
                        style="color: var(--color-text-dark);"
                        v-html="item"
                        data-aos="fade-left"
                        :data-aos-delay="index * 150"
                    >
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Pastikan video dari oembed responsif dan tidak luber */
.prose :where(figure) {
    margin: 0;
}

/* Pastikan styling untuk konten dari CKEditor di dalam timeline rapi */
.prose :where(p) {
    margin-top: 0.25em;
    margin-bottom: 0.25em;
}
.prose :where(strong) {
    color: var(--color-text-dark);
}
</style>
