<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';

const props = defineProps({
    html: {
        type: String,
        default: ''
    }
});

const sanitizedHtml = computed(() => {
    // Konfigurasi DOMPurify untuk mengizinkan tag video
    const clean = DOMPurify.sanitize(props.html, {
        ADD_TAGS: ['iframe', 'oembed', 'figure'],
        ADD_ATTR: ['allow', 'allowfullscreen', 'frameborder', 'scrolling', 'url'],
    });
    return clean;
});
</script>

<template>
    <div v-html="sanitizedHtml"></div>
</template>
