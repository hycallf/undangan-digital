<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

import AdminSidebar from '@/Layouts/Partials/AdminSidebar.vue';
import ReceptionistSidebar from '@/Layouts/Partials/ReceptionistSidebar.vue';
import TopNavbar from '@/Layouts/Partials/TopNavBar.vue';
import Swal from 'sweetalert2';
import BackToTopButton from '@/Components/UI/BackToTopButton.vue';
// State untuk mengontrol visibilitas sidebar di mode mobile
const sidebarOpen = ref(false);

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

// Pantau objek flash secara keseluruhan, bukan hanya 'success'
watch(
    () => page.props.flash,
    (flash) => {
        // Hanya jalankan jika objek flash dan properti success ada isinya
        if (flash && flash.success) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: flash.success
            });
        }
        // Anda bisa menambahkan 'else if (flash && flash.error)' di sini untuk notifikasi error
    },
    { deep: true } // Opsi ini penting untuk memantau perubahan di dalam objek
);

</script>

<template>
    <div class="relative min-h-screen lg:flex">
        <div @click="sidebarOpen = false" v-show="sidebarOpen" class="fixed inset-0 z-20 bg-black opacity-50 lg:hidden"></div>

        <aside
            :class="{ '-translate-x-full': !sidebarOpen }"
            class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 text-white p-5 transform transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0"
        >
            <div class="mb-10 text-center">
                <Link :href="route('admin.dashboard')">
                    <h1 class="text-2xl font-bold hover:text-gray-300">WeddingGuest</h1>
                </Link>
            </div>

            <AdminSidebar v-if="userRole === 'admin'" />
            <ReceptionistSidebar v-if="userRole === 'receptionist'" />

            </aside>

        <div class="flex-1 flex flex-col">
            <TopNavbar @toggle-sidebar="sidebarOpen = !sidebarOpen">
                <template #header-title>
                    <slot name="header" />
                </template>
            </TopNavbar>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <slot />
            </main>
        </div>
        <BackToTopButton />
    </div>
</template>
