<script setup>
const props = defineProps({
    imageName: {
        type: String,
        required: true
    },
    flip: {
        type: Boolean,
        default: false
    }
});

// Fungsi untuk mendapatkan path gambar dari folder Assets/Images
const getImageUrl = (name) => {
    // Path ini relatif terhadap folder Ulems tempat komponen ini berada
    return new URL(`./Assets/Images/${name}`, import.meta.url).href;
};
</script>

<template>
    <div class="w-full h-auto leading-0">
        <img
            :src="getImageUrl(imageName)"
            alt="Section Separator"
            class="w-full h-auto"
            :class="{ 'transform -scale-y-100': flip }"
        />
    </div>
</template>
