<script setup>
import { computed, onMounted  } from 'vue';
import SafeHtml from '@/Components/SafeHtml.vue';

const props = defineProps({
    event: Object,
});
// --- LOGIKA PARSING HTML ---

const parsedDocument = computed(() => {
  if (!props.event.details.story_text) return null
  return new DOMParser().parseFromString(props.event.details.story_text, 'text/html')
})

// Ambil media utama
const mainMediaHtml = computed(() => {
  if (!parsedDocument.value) return ''
  const mediaElement = parsedDocument.value.querySelector('figure.media')
  return mediaElement ? mediaElement.outerHTML : ''
})

// Ambil semua list, baik <ol> maupun <ul>
const timelineItems = computed(() => {
  if (!parsedDocument.value) return []
  const listItems = parsedDocument.value.querySelectorAll('ol > li, ul > li')
  return Array.from(listItems).map((li) => li.innerHTML)
})
</script>

<template>
    <section id="story" class="p-8 sm:p-12" style="background-color: var(--color-secondary);" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-display text-4xl mt-2" style="color: var(--color-text-dark);">
                Kisah Cinta
            </h2>
        </div>

        <!-- WRAPPER STORY -->
        <div class="max-w-3xl mx-auto bg-white/60 backdrop-blur-sm rounded-2xl shadow-lg p-6 sm:p-10 ">

            <!-- Video -->
            <div
                v-if="mainMediaHtml"
                class="w-full mb-12 overflow-hidden rounded-lg flex justify-center"
            >
                <div class="aspect-w-16 aspect-h-9 w-full">
                    <SafeHtml :html="mainMediaHtml" />
                </div>
            </div>

            <!-- Timeline -->
            <div class="relative border-l-2 border-gray-200" style="border-color: var(--color-primary-light, #e5e7eb);">
                <div v-for="(item, index) in timelineItems" :key="index" class="mb-5 ml-6">
                    <span class="absolute flex items-center justify-center w-6 h-6 bg-white rounded-full -left-3 ring-4 ring-white" :style="{ borderColor: 'var(--color-primary)' }">
                        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: 'var(--color-primary)' }"></span>
                    </span>

                    <div
                        class="prose max-w-none font-body text-sm"
                        style="color: var(--color-text-dark);"
                        data-aos="fade-left"
                        :data-aos-delay="index * 200"
                    >
                        <SafeHtml :html="item" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Supaya video dari CKEditor selalu full width responsif */
.media-wrapper iframe {
  width: 100% !important;
  height: 400px;
  border: none;
  border-radius: 0.75rem;
}

/* Styling list biar rapi */
.prose :where(p) {
  margin-top: 0.2em;
  margin-bottom: 0.2em;
}
.prose :where(strong) {
  color: var(--color-text-dark)
}
</style>
