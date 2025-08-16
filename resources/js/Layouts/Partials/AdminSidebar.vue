<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="flex flex-col space-y-2">
        <Link
            :href="route('admin.dashboard')"
            :class="{ 'bg-gray-900': route().current('admin.dashboard') }"
            class="block px-4 py-2.5 rounded-md hover:bg-gray-700 transition"
        ><font-awesome-icon icon="tachometer-alt" class="w-5 h-5 mr-3" />
            Dashboard
        </Link>
        <Link
            :href="route('admin.events.index')"
            :class="{ 'bg-gray-900': route().current('admin.events.*') }"
            class="block px-4 py-2.5 rounded-md hover:bg-gray-700 transition"
        ><font-awesome-icon icon="calendar-alt" class="w-5 h-5 mr-3" />
            Kelola Events
        </Link>
        </nav>
</template>
