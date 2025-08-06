<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <aside class="w-64 flex-shrink-0 bg-gray-800 text-white p-4">
            <div class="mb-10">
                <h1 class="text-2xl font-bold">Admin Panel</h1>
            </div>
            <nav class="flex flex-col space-y-2">
                <Link
                    :href="route('admin.dashboard')"
                    :class="{ 'bg-gray-700': route().current('admin.dashboard') }"
                    class="px-4 py-2 rounded-md hover:bg-gray-700 transition"
                >
                    Dashboard
                </Link>
                <Link
                    :href="route('admin.events.index')"
                    :class="{ 'bg-gray-700': route().current('admin.events.*') }"
                    class="px-4 py-2 rounded-md hover:bg-gray-700 transition"
                >
                    Kelola Events
                </Link>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="px-4 py-2 rounded-md hover:bg-gray-700 text-left transition mt-auto"
                >
                    Logout
                </Link>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto p-6">
            <header class="mb-6">
                </header>

            <slot />
        </main>
    </div>
</template>
