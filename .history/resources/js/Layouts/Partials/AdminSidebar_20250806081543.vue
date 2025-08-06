<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="flex flex-col space-y-2">
        <Link
            :href="route('admin.dashboard')"
            :class="{ 'bg-gray-900': route().current('admin.dashboard') }"
            class="block px-4 py-2.5 rounded-md hover:bg-gray-700 transition"
        >
            Dashboard
        </Link>
        <Link
            :href="route('admin.events.index')"
            :class="{ 'bg-gray-900': route().current('admin.events.*') }"
            class="block px-4 py-2.5 rounded-md hover:bg-gray-700 transition"
        >
            Kelola Events
        </Link>
        </nav>
</template>
