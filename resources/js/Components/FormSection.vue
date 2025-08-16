<script setup>
defineProps({
    title: String,
    description: String,
});
</script>
<template>
    <div class="p-4 sm:p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-gray-600">{{ description }}</p>
        <div class="mt-4 space-y-4">
            <slot />
        </div>
    </div>
</template>
