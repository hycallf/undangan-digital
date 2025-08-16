<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { faArrowUp } from '@fortawesome/free-solid-svg-icons';

const showButton = ref(false);

const handleScroll = () => {
    // Tampilkan tombol jika user scroll lebih dari 300px
    showButton.value = window.scrollY > 300;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform translate-y-4"
        enter-to-class="opacity-100 transform translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 transform translate-y-0"
        leave-to-class="opacity-0 transform translate-y-4"
    >
        <button
            v-if="showButton"
            @click="scrollToTop"
            class="fixed bottom-6 right-6 z-50 p-3 bg-gray-800 text-white rounded-full shadow-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            aria-label="Kembali ke atas"
        >
            <font-awesome-icon :icon="faArrowUp" class="w-5 h-5" />
        </button>
    </transition>
</template>
