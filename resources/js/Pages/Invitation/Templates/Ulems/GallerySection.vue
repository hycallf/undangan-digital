<script setup>
import { ref, computed } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps({
    event: Object,
});

// State untuk mengontrol lightbox
const lightboxVisible = ref(false);
const lightboxIndex = ref(0);

// Format data galeri agar sesuai dengan yang dibutuhkan oleh library lightbox
const images = computed(() => {
    if (!props.event.gallery_photos) return [];
    return props.event.gallery_photos.map(photo => ({
        src: `/storage/${photo.photo_path}`,
        title: photo.caption || '' // Gunakan caption sebagai judul
    }));
});

// Fungsi untuk menampilkan lightbox saat gambar diklik
const showLightbox = (index) => {
    lightboxIndex.value = index;
    lightboxVisible.value = true;
};

// Fungsi untuk menyembunyikan lightbox
const handleHide = () => {
    lightboxVisible.value = false;
};
</script>

<template>
    <section id="gallery" class="p-8 sm:p-12 bg-white" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-display text-5xl mt-2" style="color: var(--color-text-dark);">
                Gallery
            </h2>
            <p class="mt-2 font-body text-md" style="color: var(--color-text-muted);">
                Galeri kenangan indah dalam perjalanan cinta kami.
            </p>
        </div>

        <div v-if="images.length > 0" class="space-y-8">
            <div
                v-for="(image, index) in images"
                :key="index"
                class="w-full space-y-4"
                data-aos="fade-up"
            >
                <p v-if="image.title && index % 2 === 0" class="font-body text-center text-md" style="color: var(--color-text-muted);">
                    {{ image.title }}
                </p>

                <div
                    class="overflow-hidden rounded-lg cursor-pointer group"
                    @click="showLightbox(index)"
                >
                    <img
                        :src="image.src"
                        :alt="image.title"
                        class="w-full h-auto object-cover"
                    />
                </div>

                <p v-if="image.title && index % 2 !== 0" class="font-body text-center text-md" style="color: var(--color-text-muted);">
                    {{ image.title }}
                </p>
            </div>
        </div>
        <div v-else class="text-center text-[--color-text-muted]">
            <p>Galeri akan segera diperbarui.</p>
        </div>

        <vue-easy-lightbox
            :visible="lightboxVisible"
            :imgs="images"
            :index="lightboxIndex"
            @hide="handleHide"
            :moveDisabled="false"
        ></vue-easy-lightbox>
    </section>
</template>
