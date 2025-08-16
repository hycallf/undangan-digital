<script setup>
import { ref } from 'vue';

// Definisikan v-model untuk menerima dan mengirim file
const model = defineModel({
    type: [File, null],
    required: true,
});

defineProps({
    accept: {
        type: String,
        default: 'image/*',
    },
});

const input = ref(null);
const fileName = ref(null);

// Fungsi ini dipicu saat file dipilih
const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Update v-model dengan objek File
        model.value = file;
        // Simpan nama file untuk ditampilkan
        fileName.value = file.name;
    }
};

// Fungsi untuk memicu klik pada input yang tersembunyi
const triggerFileInput = () => {
    input.value.click();
};
</script>

<template>
    <div>
        <input
            type="file"
            :accept="accept"
            class="hidden"
            ref="input"
            @change="onFileChange"
        />

        <div class="flex items-center space-x-4">
            <button
                type="button"
                @click="triggerFileInput"
                class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition"
            >
                Pilih File
            </button>
            <span v-if="fileName" class="text-sm text-gray-600">{{ fileName }}</span>
            <span v-else class="text-sm text-gray-500">Belum ada file dipilih.</span>
        </div>
    </div>
</template>
