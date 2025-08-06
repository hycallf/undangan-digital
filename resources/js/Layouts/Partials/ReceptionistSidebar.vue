<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="flex flex-col space-y-2">
        <Link
            :href="route('receptionist.scanner')"
            :class="{ 'bg-gray-900': route().current('receptionist.scanner') }"
            class="block px-4 py-2.5 rounded-md hover:bg-gray-700 transition"
        >
            Scanner Check-in
        </Link>
        </nav>
</template>
