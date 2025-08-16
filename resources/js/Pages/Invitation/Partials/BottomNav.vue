<script setup>

const props = defineProps({
    activeSection: String,
});
</script>
<template>
    <nav class="fixed bottom-0 left-0 right-0 max-w-lg mx-auto bg-white shadow-[0_-2px_10px_rgba(0,0,0,0.1)] h-16 z-50">
        <div class="h-full grid grid-cols-5 items-center text-center text-gray-600">
            <a href="#hero" v-scroll-to="'#hero'" class="nav-item" :class="{'active-nav': activeSection === 'hero'}">
                <font-awesome-icon icon="home" class="w-5 h-5" />
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="#couple" v-scroll-to="'#couple'" class="nav-item" :class="{'active-nav': activeSection === 'couple'}">
                <font-awesome-icon icon="heart" class="w-5 h-5" />
                <span class="text-xs mt-1">Mempelai</span>
            </a>
            <a href="#ceremony" v-scroll-to="'#ceremony'" class="nav-item" :class="{'active-nav': activeSection === 'ceremony'}">
                <font-awesome-icon icon="calendar-check" class="w-5 h-5" />
                <span class="text-xs mt-1">Acara</span>
            </a>
            <a href="#gallery" v-scroll-to="'#gallery'" class="nav-item" :class="{'active-nav': activeSection === 'gallery'}">
                <font-awesome-icon icon="images" class="w-5 h-5" />
                <span class="text-xs mt-1">Galeri</span>
            </a>
            <a href="#rsvp" v-scroll-to="'#rsvp'" class="nav-item" :class="{'active-nav': activeSection === 'rsvp'}">
                <font-awesome-icon icon="book-open" class="w-5 h-5" />
                <span class="text-xs mt-1">Ucapan</span>
            </a>
        </div>
    </nav>
</template>
<style scoped>
.nav-item {
    @apply flex flex-col items-center justify-center h-full text-gray-500 transition-colors duration-300;
}
/* Style untuk item yang aktif */
.nav-item.active-nav {
    /* Gunakan CSS Variable dari tema Anda */
    color: var(--color-primary);
    font-weight: 600;
}
</style>
