<script setup>
import { ref } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import axios from 'axios'; // Untuk memanggil API

const props = defineProps({
  event: Object,
});

const scanResult = ref(null);
const errorMessage = ref('');
const isLoading = ref(false);

// Fungsi yang dipanggil saat QR code terdeteksi
const onDecode = async (decodedString) => {
  if (isLoading.value) return; // Mencegah scan ganda

  isLoading.value = true;
  errorMessage.value = '';
  scanResult.value = null;

  try {
    const response = await axios.post('/api/check-in/scan', {
      qr_code_token: decodedString,
    });

    // Handle sukses
    scanResult.value = response.data;

  } catch (error) {
    // Handle error (tidak ditemukan, sudah check-in, dll)
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan.';
    if (error.response?.data?.guest) {
      scanResult.value = error.response.data; // Tampilkan juga data tamu jika ada
    }
  } finally {
    isLoading.value = false;
    // Jeda sebentar sebelum bisa scan lagi
    setTimeout(() => {
      scanResult.value = null;
      errorMessage.value = '';
    }, 5000); // Pesan akan hilang setelah 5 detik
  }
};
</script>

<template>
  <div>
    <h1>Buku Tamu Digital: {{ event.groom_name }} & {{ event.bride_name }}</h1>
    <p>Arahkan QR Code tamu ke kamera.</p>

    <div class="scanner-container">
        <qrcode-stream @decode="onDecode"></qrcode-stream>
    </div>

    <div v-if="isLoading" class="feedback">
        <p>Memproses...</p>
    </div>

    <div v-if="scanResult" class="feedback" :class="scanResult.status">
        <h3 v-if="scanResult.status === 'success'">Selamat Datang!</h3>
        <h3 v-if="scanResult.status === 'warning'">Perhatian!</h3>
        <h2>{{ scanResult.guest.name }}</h2>
        <p>{{ scanResult.message }}</p>
        <p v-if="scanResult.guest.check_in_time">
            Telah Check-in pada: {{ scanResult.guest.check_in_time }}
        </p>
    </div>

    <div v-if="errorMessage" class="feedback error">
        <h3>Gagal!</h3>
        <p>{{ errorMessage }}</p>
    </div>
  </div>
</template>

<style scoped>
.scanner-container {
    max-width: 500px;
    margin: 20px auto;
    border: 2px solid #ccc;
}
.feedback {
    margin-top: 20px;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
}
.feedback.success { background-color: #d4edda; color: #155724; }
.feedback.warning { background-color: #fff3cd; color: #856404; }
.feedback.error { background-color: #f8d7da; color: #721c24; }
</style>
