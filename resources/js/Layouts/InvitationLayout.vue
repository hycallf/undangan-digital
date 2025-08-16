<script setup>
import { computed } from 'vue';

const props = defineProps({
    event: Object,
    themeStyle: Object, // Terima prop style dari controller
});

// Ubah objek JSON dari database menjadi CSS Variables
const cssVars = computed(() => {
    // Pengecekan keamanan jika themeStyle tidak ada
    if (!props.themeStyle?.colors || !props.themeStyle?.fonts) return {};

    return {
        '--font-display': props.themeStyle.fonts.display,
        '--font-body': props.themeStyle.fonts.body,
        '--color-primary': props.themeStyle.colors.primary,
        '--color-secondary': props.themeStyle.colors.secondary,
        '--color-accent': props.themeStyle.colors.accent,
        '--color-text-dark': props.themeStyle.colors.text_dark,
        '--color-text-light': props.themeStyle.colors.text_light,
        '--color-text-muted': props.themeStyle.colors.text_muted,
    };
});
</script>

<template>
    <div
        :style="cssVars"
        class="bg-[--color-secondary] text-[--color-text-dark] max-w-lg mx-auto shadow-2xl relative"
        style="font-family: var(--font-body)"
    >
        <slot />
    </div>
</template>

<style>
/* Definisikan class font di sini agar bisa digunakan di seluruh template undangan.
  Ini tidak akan mempengaruhi dashboard admin Anda.
*/
.font-display {
    font-family: var(--font-display);
}
.font-body {
    font-family: var(--font-body);
}
</style>
