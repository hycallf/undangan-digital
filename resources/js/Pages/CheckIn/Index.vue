<script setup>
import { ref, reactive } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import axios from 'axios';
import { onMounted } from 'vue';

const props = defineProps({
  event: Object,
});

const scanResult = ref(null);
const errorMessage = ref('');
const isLoading = ref(false);
const isTakingPhoto = ref(false);
const capturedImage = ref(null);
const guestIdForPhoto = ref(null);
const videoRef = ref(null);
const canvasRef = ref(null);

onMounted(() => {
  // Request camera access on mount (optional, browser might prompt on first scan attempt)
  navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
      if (videoRef.value) {
        videoRef.value.srcObject = stream;
      }
    })
    .catch(error => {
      errorMessage.value = 'Akses kamera ditolak atau tidak tersedia.';
      console.error('Error accessing camera:', error);
    });
});

const onDecode = async (decodedString) => {
  if (isLoading.value || isTakingPhoto.value) return;

  isLoading.value = true;
  errorMessage.value = '';
  scanResult.value = null;
  capturedImage.value = null;
  guestIdForPhoto.value = null;

  try {
    const response = await axios.post('/api/check-in/scan', {
      qr_code_token: decodedString,
    });

    scanResult.value = response.data;
    if (response.data.guest?.photo_required) {
      guestIdForPhoto.value = response.data.guest.guest_id;
      isTakingPhoto.value = true;
    }

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan.';
    if (error.response?.data?.guest) {
      scanResult.value = error.response.data;
    }
  } finally {
    isLoading.value = false;
  }
};

const capturePhoto = () => {
  if (videoRef.value && canvasRef.value) {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    capturedImage.value = canvas.toDataURL('image/png');
  }
};

const uploadPhoto = async () => {
  if (!capturedImage.value || !guestIdForPhoto.value) {
    errorMessage.value = 'Silakan ambil foto terlebih dahulu.';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const formData = new FormData();
    formData.append('guest_id', guestIdForPhoto.value);
    formData.append('photo', dataURLtoFile(capturedImage.value, `guest_${guestIdForPhoto.value}_${Date.now()}.png`));

    const response = await axios.post('/api/check-in/upload-photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    scanResult.value = response.data;
    isTakingPhoto.value = false;
    capturedImage.value = null;
    guestIdForPhoto.value = null;
    // Optionally reset scanResult after successful upload
    setTimeout(() => {
      scanResult.value = null;
    }, 3000);

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengunggah foto.';
  } finally {
    isLoading.value = false;
  }
};

// Helper function to convert data URL to File object
function dataURLtoFile(dataurl, filename) {
  const arr = dataurl.split(',');
  const mime = arr.at(0).match(/:(.*?);/)?.[1];
  const bstr = atob(arr.at(1));
  let n = bstr.length;
  const u8arr = new Uint8Array(n);
  while (n--) {
    u8arr[n] = bstr.charCodeAt(n);
  }
  return new File([u8arr], filename, { type: mime });
}

const resetCameraAndFeedback = () => {
  isTakingPhoto.value = false;
  capturedImage.value = null;
  scanResult.value = null;
  errorMessage.value = '';
  guestIdForPhoto.value = null;
};
</script>

<template>
  <div>
    <h1>Buku Tamu Digital: {{ event.groom_name }} & {{ event.bride_name }}</h1>
    <p v-if="!isTakingPhoto">Arahkan QR Code tamu ke kamera.</p>

    <div class="scanner-container" v-if="!isTakingPhoto">
      <qrcode-stream @decode="onDecode" :paused="isLoading"></qrcode-stream>
    </div>

    <div v-if="isLoading" class="feedback">
      <p>Memproses...</p>
    </div>

    <div v-if="scanResult && scanResult.status && !isTakingPhoto" class="feedback" :class="scanResult.status">
      <h3 v-if="scanResult.status === 'success'">{{ scanResult.message }}</h3>
      <h3 v-if="scanResult.status === 'warning'">{{ scanResult.message }}</h3>
      <h3 v-if="scanResult.status === 'error'">{{ scanResult.message }}</h3>
      <h2 v-if="scanResult.guest">{{ scanResult.guest.name }}</h2>
      <p v-if="scanResult.guest?.check_in_time">
        Telah Check-in pada: {{ scanResult.guest.check_in_time }}
      </p>
    </div>

    <div v-if="errorMessage && !isTakingPhoto" class="feedback error">
      <h3>Gagal!</h3>
      <p>{{ errorMessage }}</p>
    </div>

    <div v-if="isTakingPhoto">
      <h2>Ambil Foto Kehadiran</h2>
      <div class="camera-preview">
        <video ref="videoRef" autoplay playsinline></video>
        <canvas ref="canvasRef" style="display: none;"></canvas>
      </div>
      <button @click="capturePhoto">Ambil Gambar</button>
      <div v-if="capturedImage" class="captured-image">
        <img :src="capturedImage" alt="Captured Photo" style="max-width: 300px;">
        <button @click="uploadPhoto" :disabled="isLoading">Unggah Foto</button>
        <button @click="resetCameraAndFeedback">Batal & Scan Ulang</button>
      </div>
      <div v-else-if="errorMessage" class="feedback error">
        <p>{{ errorMessage }}</p>
      </div>
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

.feedback.success {
  background-color: #d4edda;
  color: #155724;
}

.feedback.warning {
  background-color: #fff3cd;
  color: #856404;
}

.feedback.error {
  background-color: #f8d7da;
  color: #721c24;
}

.camera-preview {
  position: relative;
  width: 400px;
  height: 300px;
  overflow: hidden;
  margin: 20px auto;
}

.camera-preview video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.captured-image {
  margin-top: 20px;
  text-align: center;
}
</style>
