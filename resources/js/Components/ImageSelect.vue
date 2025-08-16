<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    options: {
        type: Array,
        required: true,
    },
    modelValue: [String, Number, null],
});
const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);

const selectedOption = computed(() => {
    return props.options.find(option => option.id == props.modelValue) || null;
});

const selectOption = (option) => {
    emit('update:modelValue', option.id);
    isOpen.value = false;
};

</script>

<template>
    <div class="relative w-full">
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="relative w-full cursor-default rounded-md bg-white py-3 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm sm:leading-6"
        >
            <span v-if="selectedOption" class="flex items-center">
                <img
                    v-if="selectedOption.thumbnail_path"
                    :src="`/${selectedOption.thumbnail_path}`"
                    alt="thumbnail"
                    style="height: 40px;" class="w-auto object-contain flex-shrink-0 rounded-md mr-3">
                 <span>{{ selectedOption.name }}</span>
            </span>
            <span v-else class="text-gray-500">-- Pilih Template --</span>
        </button>

        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <ul v-if="isOpen" class="absolute z-10 mt-1 max-h-56 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                <li
                    v-for="option in options"
                    :key="option.id"
                    @click="selectOption(option)"
                    class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-600 hover:text-white"
                >
                    <div class="flex items-center">
                        <img
                            v-if="option.thumbnail_path"
                            :src="`/${option.thumbnail_path}`"
                            alt="thumbnail"
                            style="height: 40px;" class="w-auto object-contain flex-shrink-0 rounded-md mr-3">
                        <span>{{ option.name }}</span>
                    </div>
                </li>
            </ul>
        </transition>
    </div>
</template>
